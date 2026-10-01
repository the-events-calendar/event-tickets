<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use stdClass;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;

/**
 * TC-RSVP attendees travel the Tickets Commerce order pipeline, so they reach Promoter through the
 * Commerce observer rather than the legacy RSVP one. Without a type distinction every RSVP arrives
 * at Promoter labelled `ticket_purchased`, and a "RSVP Going" trigger configured on the Promoter
 * side never matches, so its message is never sent.
 */
class Promoter_Triggers_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Starts collecting dispatched trigger types from this point on, so the ones fired while
	 * building a fixture are not counted.
	 *
	 * Returns an object rather than an array because objects are shared by handle: the closure
	 * below and the caller then see the same list.
	 *
	 * @return stdClass An object whose `types` property collects the dispatched trigger types.
	 */
	private function record_triggers(): stdClass {
		$log        = new stdClass();
		$log->types = [];

		add_action(
			'tribe_tickets_promoter_trigger_attendee',
			static function ( $type ) use ( $log ) {
				$log->types[] = $type;
			}
		);

		return $log;
	}

	/**
	 * Builds a TC-RSVP order the way Order_Endpoint::process_rsvp_step() does: through the dedicated
	 * RSVP_Cart, then Pending -> Completed.
	 *
	 * @return array<int> The created attendee IDs.
	 */
	private function create_tc_rsvp_attendees( int $ticket_id, string $order_status ): array {
		/** @var RSVP_Cart $cart */
		$cart = tribe( RSVP_Cart::class );
		$cart->clear();
		$cart->upsert_item(
			$ticket_id,
			1,
			[
				'type'         => Constants::TC_RSVP_TYPE,
				'order_status' => $order_status,
			]
		);
		$cart->save();

		/** @var Order $orders */
		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart(
			tribe( Gateway::class ),
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Test Purchaser',
				'purchaser_first_name' => 'Test',
				'purchaser_last_name'  => 'Purchaser',
				'purchaser_email'      => 'attendee@example.com',
			],
			Constants::TC_RSVP_TYPE
		);

		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, Completed::SLUG );

		$cart->clear();

		return array_map(
			'intval',
			array_column( tribe( Module::class )->get_attendees_by_order_id( $order->ID ), 'attendee_id' )
		);
	}

	/**
	 * @test
	 */
	public function it_should_trigger_rsvp_going_when_an_attendee_rsvps_going(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$log = $this->record_triggers();
		$this->create_tc_rsvp_attendees( $ticket_id, 'yes' );

		$this->assertSame( [ 'rsvp_going' ], $log->types );
	}

	/**
	 * @test
	 */
	public function it_should_trigger_rsvp_not_going_when_an_attendee_rsvps_not_going(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$log = $this->record_triggers();
		$this->create_tc_rsvp_attendees( $ticket_id, 'no' );

		$this->assertSame( [ 'rsvp_not_going' ], $log->types );
	}

	/**
	 * @test
	 */
	public function it_should_still_trigger_ticket_purchased_for_a_regular_ticket(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$log = $this->record_triggers();
		$this->create_order( [ $ticket_id => 1 ] );

		$this->assertSame( [ 'ticket_purchased' ], $log->types );
	}

	/**
	 * @test
	 */
	public function it_should_trigger_rsvp_going_when_an_attendee_changes_to_going(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$attendees = $this->create_tc_rsvp_attendees( $ticket_id, 'no' );

		$log = $this->record_triggers();
		update_post_meta( $attendees[0], Constants::RSVP_STATUS_META_KEY, 'yes' );

		$this->assertSame( [ 'rsvp_going' ], $log->types );
	}

	/**
	 * @test
	 */
	public function it_should_trigger_rsvp_not_going_when_an_attendee_changes_to_not_going(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$attendees = $this->create_tc_rsvp_attendees( $ticket_id, 'yes' );

		$log = $this->record_triggers();
		update_post_meta( $attendees[0], Constants::RSVP_STATUS_META_KEY, 'no' );

		$this->assertSame( [ 'rsvp_not_going' ], $log->types );
	}
}
