<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP_V1;

/**
 * The Attendees screen's "Edit Attendee" modal offers a Going / Not going selector when the ticket has the
 * Not Going option enabled, and submits the choice as `attendee_status`. That name has to reach the RSVP
 * status meta, or the update reports success while the answer silently stays as it was.
 */
class Attendee_Status_Update_Test extends WPTestCase {
	use Ticket_Maker;
	use TC_Ticket_Maker;

	private function create_attendee( string $status ): array {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[
				'full_name'         => 'Status Attendee',
				'email'             => 'status@example.com',
				'send_ticket_email' => false,
			]
		);

		update_post_meta( $attendee->ID, Constants::RSVP_STATUS_META_KEY, $status );

		return [ 'attendee_id' => $attendee->ID, 'post_id' => $post_id, 'ticket_id' => $ticket_id ];
	}

	public function test_updating_an_attendee_to_not_going_persists(): void {
		$fixture = $this->create_attendee( 'yes' );

		tribe( RSVP_V1::class )->update_attendee(
			$fixture['attendee_id'],
			[
				'full_name'       => 'Status Attendee',
				'email'           => 'status@example.com',
				'attendee_status' => 'no',
			]
		);

		$this->assertSame(
			'no',
			get_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, true ),
			'A Not Going choice from the Edit Attendee modal must reach the RSVP status meta.'
		);
	}

	/**
	 * On a post with more than one ticket the Attendees screen resolves Tickets Commerce as the provider,
	 * so the update runs through `Module::update_attendee()` rather than the RSVP repository. That path
	 * has to persist the answer too.
	 */
	public function test_updating_through_tickets_commerce_persists(): void {
		$fixture = $this->create_attendee( 'yes' );

		tribe( Module::class )->update_attendee(
			$fixture['attendee_id'],
			[
				'full_name'       => 'Status Attendee',
				'email'           => 'status@example.com',
				'attendee_status' => 'no',
			]
		);

		$this->assertSame(
			'no',
			get_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, true ),
			'Tickets Commerce must persist a Not Going answer for a TC-RSVP attendee.'
		);
	}

	public function test_updating_a_non_rsvp_attendee_does_not_gain_rsvp_status(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[ 'full_name' => 'Plain Attendee', 'email' => 'plain@example.com', 'send_ticket_email' => false ]
		);

		tribe( Module::class )->update_attendee(
			$attendee->ID,
			[
				'full_name'       => 'Plain Attendee',
				'email'           => 'plain@example.com',
				'attendee_status' => 'no',
			]
		);

		$this->assertFalse(
			metadata_exists( 'post', $attendee->ID, Constants::RSVP_STATUS_META_KEY ),
			'A regular ticket attendee must not pick up RSVP status meta.'
		);
	}

	public function test_updating_an_attendee_back_to_going_persists(): void {
		$fixture = $this->create_attendee( 'no' );

		tribe( RSVP_V1::class )->update_attendee(
			$fixture['attendee_id'],
			[
				'full_name'       => 'Status Attendee',
				'email'           => 'status@example.com',
				'attendee_status' => 'yes',
			]
		);

		$this->assertSame(
			'yes',
			get_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, true )
		);
	}
}
