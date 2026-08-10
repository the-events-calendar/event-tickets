<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__RSVP as RSVP_V1;

/**
 * Under RSVP v2 the `tickets.attendee-repository.rsvp` binding is swapped to a repository that serves
 * `tec_tc_attendee` posts, but `Tribe__Tickets__RSVP` remains the module that surfaces RSVP tickets. Its
 * `get_attendee()` therefore fetches a v2 attendee and must accept it, instead of rejecting anything that
 * is not the v1 attendee post type.
 *
 * The visible symptom of getting this wrong is on the Attendees admin screen: the Manual Attendees edit
 * form nulls its attendee ID when `get_attendee()` returns false, and the JS then submits the form as an
 * "add" request, so every attempt to edit an attendee silently creates another one.
 */
class Legacy_Get_Attendee_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @return array{attendee_id: int, post_id: int, ticket_id: int}
	 */
	private function create_rsvp_attendee(): array {
		// The Attendees screen is an admin context; `get_attendee()` narrows to published attendees only
		// for users who cannot manage them.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		/*
		 * Build the order through RSVP_Cart, the way Order_Endpoint::process_rsvp_step() does. Creating a
		 * plain ticket and retyping it to TC-RSVP afterwards leaves the attendee outside what the RSVP
		 * repository will return, which is a different failure than the one under test.
		 */
		$cart = tribe( RSVP_Cart::class );
		$cart->clear();
		$cart->upsert_item(
			$ticket_id,
			1,
			[
				'type'         => Constants::TC_RSVP_TYPE,
				'order_status' => 'yes',
			]
		);
		$cart->save();

		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart(
			tribe( Gateway::class ),
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Test Attendee',
				'purchaser_first_name' => 'Test',
				'purchaser_last_name'  => 'Attendee',
				'purchaser_email'      => 'attendee@example.com',
			],
			Constants::TC_RSVP_TYPE
		);

		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, Completed::SLUG );
		$cart->clear();

		$attendees   = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$attendee_id = (int) $attendees[0]['attendee_id'];

		/*
		 * Order_Endpoint::process_rsvp_step() stamps this after the order completes, and the RSVP attendee
		 * repository scopes every query to attendees that carry it. Without it the attendee exists but no
		 * RSVP query will ever return it.
		 */
		update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'yes' );

		return [
			'attendee_id' => $attendee_id,
			'post_id'     => $post_id,
			'ticket_id'   => $ticket_id,
		];
	}

	public function test_it_should_return_a_tc_rsvp_attendee(): void {
		$fixture = $this->create_rsvp_attendee();

		$attendee = tribe( RSVP_V1::class )->get_attendee( $fixture['attendee_id'], $fixture['post_id'] );

		$this->assertIsArray(
			$attendee,
			'The RSVP module must resolve an attendee served by the repository it is bound to.'
		);
		$this->assertEquals( $fixture['attendee_id'], $attendee['attendee_id'] );
		$this->assertEquals( $fixture['ticket_id'], $attendee['product_id'] );
		$this->assertSame( 'attendee@example.com', $attendee['holder_email'] );
	}

	public function test_it_should_still_reject_a_post_that_is_not_an_attendee(): void {
		$fixture  = $this->create_rsvp_attendee();
		$not_an_attendee = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->assertFalse(
			tribe( RSVP_V1::class )->get_attendee( $not_an_attendee, $fixture['post_id'] ),
			'A post of an unrelated type must not be treated as an attendee.'
		);
	}
}
