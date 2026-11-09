<?php

namespace TEC\Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use ReflectionMethod;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\RSVP\V2\Constants;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Attendee_Maker;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Admin__Move_Tickets as Move_Tickets;
use Tribe__Tickets__Tickets as Tickets;

class Move_Tickets_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use Attendee_Maker;

	/**
	 * @test
	 */
	public function it_should_offer_only_rsvps_to_an_rsvp_attendee(): void {
		$source_id      = static::factory()->post->create();
		$target_id      = static::factory()->post->create();
		$source_rsvp_id = $this->create_tc_rsvp_ticket( $source_id );
		$target_rsvp_id = $this->create_tc_rsvp_ticket( $target_id );
		$this->create_tc_ticket( $target_id, 10 );
		$attendee_ids = $this->create_attendees( $source_rsvp_id, 1 );

		$matches = $this->get_ticket_type_matches( $target_id, $attendee_ids );

		$this->assertSame( [ $target_rsvp_id ], array_keys( $matches ) );
	}

	/**
	 * @test
	 */
	public function it_should_offer_only_tickets_to_a_ticket_attendee(): void {
		$source_id        = static::factory()->post->create();
		$target_id        = static::factory()->post->create();
		$source_ticket_id = $this->create_tc_ticket( $source_id, 10 );
		$target_ticket_id = $this->create_tc_ticket( $target_id, 10 );
		$this->create_tc_rsvp_ticket( $target_id );
		$attendee_ids = $this->create_attendees( $source_ticket_id, 1 );

		$matches = $this->get_ticket_type_matches( $target_id, $attendee_ids );

		$this->assertSame( [ $target_ticket_id ], array_keys( $matches ) );
	}

	/**
	 * @test
	 */
	public function it_should_offer_nothing_to_a_mix_of_rsvp_and_ticket_attendees(): void {
		$source_id        = static::factory()->post->create();
		$target_id        = static::factory()->post->create();
		$source_rsvp_id   = $this->create_tc_rsvp_ticket( $source_id );
		$source_ticket_id = $this->create_tc_ticket( $source_id, 10 );
		$this->create_tc_rsvp_ticket( $target_id );
		$this->create_tc_ticket( $target_id, 10 );
		$attendee_ids = array_merge(
			$this->create_attendees( $source_rsvp_id, 1 ),
			$this->create_attendees( $source_ticket_id, 1 )
		);

		$this->assertSame( [], $this->get_ticket_type_matches( $target_id, $attendee_ids ) );
	}

	/**
	 * @test
	 */
	public function it_should_move_a_going_attendee_to_another_rsvp_and_update_both_stocks(): void {
		$source_id      = static::factory()->post->create();
		$target_id      = static::factory()->post->create();
		$source_rsvp_id = $this->create_tc_rsvp_ticket( $source_id );
		$target_rsvp_id = $this->create_tc_rsvp_ticket( $target_id );
		$attendee_ids   = $this->create_attendees( $source_rsvp_id, 2 );
		$source_stock   = Tickets::load_ticket_object( $source_rsvp_id )->stock();
		$target_stock   = Tickets::load_ticket_object( $target_rsvp_id )->stock();

		$moved = tribe( Move_Tickets::class )->move_tickets( [ $attendee_ids[0] ], $target_rsvp_id, $source_id, $target_id );

		$this->assertSame( 1, $moved );
		$this->assertEquals( $target_rsvp_id, get_post_meta( $attendee_ids[0], Module::ATTENDEE_PRODUCT_KEY, true ) );
		$this->assertEquals( $target_id, get_post_meta( $attendee_ids[0], Module::ATTENDEE_EVENT_KEY, true ) );
		$this->assertEquals( $source_stock + 1, Tickets::load_ticket_object( $source_rsvp_id )->stock() );
		$this->assertEquals( $target_stock - 1, Tickets::load_ticket_object( $target_rsvp_id )->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_move_a_not_going_attendee_to_another_rsvp_without_changing_either_stock(): void {
		$source_id      = static::factory()->post->create();
		$target_id      = static::factory()->post->create();
		$source_rsvp_id = $this->create_tc_rsvp_ticket( $source_id );
		$target_rsvp_id = $this->create_tc_rsvp_ticket( $target_id );
		$this->create_attendees( $source_rsvp_id, 2 );
		$not_going_ids = $this->create_not_going_tc_rsvp_attendees( 1, $source_rsvp_id, $source_id );
		$source_stock  = Tickets::load_ticket_object( $source_rsvp_id )->stock();
		$target_stock  = Tickets::load_ticket_object( $target_rsvp_id )->stock();
		$source_sold   = Tickets::load_ticket_object( $source_rsvp_id )->qty_sold();
		$target_sold   = Tickets::load_ticket_object( $target_rsvp_id )->qty_sold();

		$moved = tribe( Move_Tickets::class )->move_tickets( $not_going_ids, $target_rsvp_id, $source_id, $target_id );

		$this->assertSame( 1, $moved );
		$this->assertSame( 'no', get_post_meta( $not_going_ids[0], Constants::RSVP_STATUS_META_KEY, true ) );
		$this->assertEquals( $source_stock, Tickets::load_ticket_object( $source_rsvp_id )->stock() );
		$this->assertEquals( $target_stock, Tickets::load_ticket_object( $target_rsvp_id )->stock() );
		$this->assertEquals( $source_sold, Tickets::load_ticket_object( $source_rsvp_id )->qty_sold() );
		$this->assertEquals( $target_sold, Tickets::load_ticket_object( $target_rsvp_id )->qty_sold() );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_an_rsvp_attendee_to_a_ticket(): void {
		$source_id        = static::factory()->post->create();
		$target_id        = static::factory()->post->create();
		$source_rsvp_id   = $this->create_tc_rsvp_ticket( $source_id );
		$target_ticket_id = $this->create_tc_ticket( $target_id, 10 );
		$attendee_ids     = $this->create_attendees( $source_rsvp_id, 1 );
		$target_stock     = Tickets::load_ticket_object( $target_ticket_id )->stock();

		$moved = tribe( Move_Tickets::class )->move_tickets( $attendee_ids, $target_ticket_id, $source_id, $target_id );

		$this->assertSame( 0, $moved );
		$this->assertEquals( $source_rsvp_id, get_post_meta( $attendee_ids[0], Module::ATTENDEE_PRODUCT_KEY, true ) );
		$this->assertEquals( $target_stock, Tickets::load_ticket_object( $target_ticket_id )->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_a_ticket_attendee_to_an_rsvp(): void {
		$source_id        = static::factory()->post->create();
		$target_id        = static::factory()->post->create();
		$source_ticket_id = $this->create_tc_ticket( $source_id, 10 );
		$target_rsvp_id   = $this->create_tc_rsvp_ticket( $target_id );
		$attendee_ids     = $this->create_attendees( $source_ticket_id, 1 );

		$moved = tribe( Move_Tickets::class )->move_tickets( $attendee_ids, $target_rsvp_id, $source_id, $target_id );

		$this->assertSame( 0, $moved );
		$this->assertEquals( $source_ticket_id, get_post_meta( $attendee_ids[0], Module::ATTENDEE_PRODUCT_KEY, true ) );
	}

	/**
	 * Creates attendees for a ticket through a completed order.
	 *
	 * @param int $ticket_id The ticket or RSVP ID.
	 * @param int $quantity  How many attendees to create.
	 *
	 * @return int[] The attendee IDs.
	 */
	private function create_attendees( int $ticket_id, int $quantity ): array {
		$order = $this->create_order( [ $ticket_id => $quantity ] );

		return get_posts(
			[
				'post_type'      => Attendee::POSTTYPE,
				'post_parent'    => $order->ID,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);
	}

	/**
	 * Lists the tickets the Move dialog offers on a post, as the dialog's AJAX request asks for them.
	 *
	 * @param int   $target_id    The post the attendees would move to.
	 * @param int[] $attendee_ids The attendees being moved.
	 *
	 * @return array<int,string> The offered tickets' names, by ticket ID.
	 */
	private function get_ticket_type_matches( int $target_id, array $attendee_ids ): array {
		$method = new ReflectionMethod( Move_Tickets::class, 'get_ticket_type_matches' );
		$method->setAccessible( true );

		// WordPress slashes request data, and the dialog sends the attendees' provider class as-is.
		return $method->invoke( tribe( Move_Tickets::class ), $target_id, addslashes( Module::class ), $attendee_ids );
	}
}
