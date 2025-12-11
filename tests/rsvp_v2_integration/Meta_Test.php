<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Meta;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Meta_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_should_get_and_set_rsvp_status(): void {
		$attendee_id = static::factory()->post->create( [ 'post_type' => 'tec_tc_attendee' ] );
		$meta        = tribe( Meta::class );

		// Default should be 'yes' (going).
		$this->assertSame( Meta::STATUS_GOING, $meta->get_rsvp_status( $attendee_id ) );

		// Set to 'no' (not going).
		$result = $meta->set_rsvp_status( $attendee_id, Meta::STATUS_NOT_GOING );
		$this->assertTrue( $result );
		$this->assertSame( Meta::STATUS_NOT_GOING, $meta->get_rsvp_status( $attendee_id ) );

		// Set back to 'yes'.
		$result = $meta->set_rsvp_status( $attendee_id, Meta::STATUS_GOING );
		$this->assertTrue( $result );
		$this->assertSame( Meta::STATUS_GOING, $meta->get_rsvp_status( $attendee_id ) );
	}

	public function test_should_reject_invalid_rsvp_status(): void {
		$attendee_id = static::factory()->post->create( [ 'post_type' => 'tec_tc_attendee' ] );
		$meta        = tribe( Meta::class );

		$result = $meta->set_rsvp_status( $attendee_id, 'invalid_status' );
		$this->assertFalse( $result );
	}

	public function test_should_identify_rsvp_ticket_by_pinged_column(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$meta      = tribe( Meta::class );

		// Initially not an RSVP ticket.
		$this->assertFalse( $meta->is_rsvp_ticket( $ticket_id ) );

		// Set the pinged column to tc-rsvp.
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			[ 'pinged' => Meta::TC_RSVP_TYPE ],
			[ 'ID' => $ticket_id ]
		);
		clean_post_cache( $ticket_id );

		$this->assertTrue( $meta->is_rsvp_ticket( $ticket_id ) );
	}

	public function test_should_identify_rsvp_ticket_by_type_meta(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$meta      = tribe( Meta::class );

		// Initially not an RSVP ticket.
		$this->assertFalse( $meta->is_rsvp_ticket( $ticket_id ) );

		// Set the _type meta.
		update_post_meta( $ticket_id, Meta::TYPE_META_KEY, Meta::TC_RSVP_TYPE );

		$this->assertTrue( $meta->is_rsvp_ticket( $ticket_id ) );
	}

	public function test_should_return_false_for_non_ticket_post_type(): void {
		$post_id = static::factory()->post->create();
		$meta    = tribe( Meta::class );

		$this->assertFalse( $meta->is_rsvp_ticket( $post_id ) );
	}

	public function test_should_identify_rsvp_attendee(): void {
		$attendee_id = static::factory()->post->create( [ 'post_type' => 'tec_tc_attendee' ] );
		$meta        = tribe( Meta::class );

		// Initially not an RSVP attendee (no RSVP status meta).
		$this->assertFalse( $meta->is_rsvp_attendee( $attendee_id ) );

		// Set the RSVP status meta.
		update_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, Meta::STATUS_GOING );

		$this->assertTrue( $meta->is_rsvp_attendee( $attendee_id ) );
	}

	public function test_should_get_and_set_show_not_going(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 0 );
		$meta      = tribe( Meta::class );

		// Default is false.
		$this->assertFalse( $meta->get_show_not_going( $ticket_id ) );

		// Set to true.
		$result = $meta->set_show_not_going( $ticket_id, true );
		$this->assertNotFalse( $result );
		$this->assertTrue( $meta->get_show_not_going( $ticket_id ) );

		// Set back to false.
		$result = $meta->set_show_not_going( $ticket_id, false );
		$this->assertNotFalse( $result );
		$this->assertFalse( $meta->get_show_not_going( $ticket_id ) );
	}

	public function test_should_get_and_set_show_attendees_list(): void {
		$post_id = static::factory()->post->create();
		$meta    = tribe( Meta::class );

		// Default is false.
		$this->assertFalse( $meta->get_show_attendees_list( $post_id ) );

		// Set to true.
		$result = $meta->set_show_attendees_list( $post_id, true );
		$this->assertTrue( $result );
		$this->assertTrue( $meta->get_show_attendees_list( $post_id ) );

		// Set back to false.
		$result = $meta->set_show_attendees_list( $post_id, false );
		$this->assertTrue( $result );
		$this->assertFalse( $meta->get_show_attendees_list( $post_id ) );
	}
}
