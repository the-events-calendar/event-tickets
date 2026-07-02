<?php

namespace TEC\Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\RSVP\V2\Constants;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;

class Ticket_Data_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @before
	 */
	public function set_up_ticketable_post_types(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );
	}

	/**
	 * @test
	 */
	public function get_posts_tickets_should_not_include_rsvp_v2_tickets(): void {
		$post_id = static::factory()->post->create();

		$rsvp_ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$ticket_data = new Ticket_Data();
		$ticket_ids  = [];

		foreach ( $ticket_data->get_posts_tickets( $post_id ) as $ticket ) {
			$ticket_ids[] = $ticket->ID;
		}

		$this->assertContains( $regular_ticket_id, $ticket_ids, 'Regular TC ticket should be included.' );
		$this->assertNotContains( $rsvp_ticket_id, $ticket_ids, 'TC-RSVP ticket should be excluded.' );
	}

	/**
	 * @test
	 */
	public function get_posts_tickets_should_return_empty_when_only_rsvp_v2_tickets_exist(): void {
		$post_id = static::factory()->post->create();

		$this->create_tc_rsvp_ticket( $post_id );

		$ticket_data = new Ticket_Data();
		$ticket_ids  = [];

		foreach ( $ticket_data->get_posts_tickets( $post_id ) as $ticket ) {
			$ticket_ids[] = $ticket->ID;
		}

		$this->assertEmpty( $ticket_ids, 'No tickets should be returned when only TC-RSVP tickets exist.' );
	}

	/**
	 * @test
	 */
	public function get_posts_rsvp_should_return_rsvp_v2_ticket(): void {
		$post_id = static::factory()->post->create();

		$rsvp_ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$ticket_data = new Ticket_Data();
		$rsvp        = $ticket_data->get_posts_rsvp( $post_id );

		$this->assertNotNull( $rsvp, 'RSVP should be found.' );
		$this->assertSame( $rsvp_ticket_id, $rsvp->ID, 'Should return the TC-RSVP ticket.' );
	}

	/**
	 * @test
	 */
	public function get_posts_rsvp_should_return_null_when_no_rsvp_exists(): void {
		$post_id = static::factory()->post->create();

		$this->create_tc_ticket( $post_id, 10 );

		$ticket_data = new Ticket_Data();
		$rsvp        = $ticket_data->get_posts_rsvp( $post_id );

		$this->assertNull( $rsvp, 'Should return null when no RSVP ticket exists.' );
	}

	/**
	 * @test
	 */
	public function get_posts_rsvps_data_should_count_sold_by_attendees_for_rsvp_v2(): void {
		$post_id = static::factory()->post->create();
		$user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );

		// Create as regular TC ticket first so order creation works via the cart.
		$ticket_id = $this->create_tc_ticket( $post_id, 23 );

		// Create 3 orders (one attendee each) to simulate 3 RSVPs.
		for ( $i = 0; $i < 3; $i++ ) {
			$this->create_order( [ $ticket_id => 1 ], [ 'purchaser_user_id' => $user_id ] );
		}

		// Retroactively mark the ticket as TC-RSVP.
		update_post_meta( $ticket_id, '_type', Constants::TC_RSVP_TYPE );

		$ticket_data = new Ticket_Data();
		$data        = $ticket_data->get_posts_rsvps_data( $post_id );

		$this->assertSame( 1, $data['ticket_count'], 'Should count 1 RSVP ticket.' );
		$this->assertArrayHasKey( $ticket_id, $data['availability'], 'Availability should include the RSVP ticket.' );
		$this->assertSame( 3, $data['availability'][ $ticket_id ]['sold'], 'Sold count should reflect attendees created through orders.' );
	}
}
