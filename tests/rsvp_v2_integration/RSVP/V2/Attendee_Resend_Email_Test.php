<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Ticket_Maker;

/**
 * The Attendees screen's "Edit Attendee" modal can resend the attendee's email. For a ticket that means the
 * ticket email, which carries a QR code for admission. An RSVP has nothing to admit anyone with, and the
 * message differs entirely depending on whether the person said they are coming, so it needs the Going or
 * Not Going email instead.
 *
 * Sending the ticket email to someone who just answered Not Going is the visible failure: they receive a
 * QR code for an event they told you they are skipping.
 */
class Attendee_Resend_Email_Test extends WPTestCase {
	use RSVP_Ticket_Maker;
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * Records the email types dispatched from this point on.
	 *
	 * @return \stdClass An object whose `slugs` property collects email slugs as they are sent.
	 */
	private function record_emails(): \stdClass {
		$sent        = new \stdClass();
		$sent->slugs = [];

		add_filter(
			'tec_tickets_emails_dispatcher',
			static function ( $dispatcher, $email ) use ( &$sent ) {
				if ( $email instanceof Email_Abstract ) {
					$sent->slugs[] = $email->slug;
				}

				return $dispatcher;
			},
			10,
			2
		);

		$this->set_fn_return( 'wp_mail', true, false );

		return $sent;
	}

	private function create_rsvp_attendee(): int {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[ 'full_name' => 'Resend Attendee', 'email' => 'resend@example.com', 'send_ticket_email' => false ]
		);

		return $attendee->ID;
	}

	public function test_a_not_going_answer_resends_the_not_going_email(): void {
		$attendee_id = $this->create_rsvp_attendee();
		$sent = $this->record_emails();

		tribe( Module::class )->update_attendee(
			$attendee_id,
			[
				'full_name'         => 'Resend Attendee',
				'email'             => 'resend@example.com',
				'attendee_status'   => 'no',
				'send_ticket_email' => true,
			]
		);

		$this->assertSame(
			[ 'rsvp-not-going' ],
			$sent->slugs,
			'Someone who answered Not Going must not be sent a ticket with a QR code.'
		);
	}

	public function test_a_going_answer_resends_the_rsvp_email(): void {
		$attendee_id = $this->create_rsvp_attendee();
		$sent = $this->record_emails();

		tribe( Module::class )->update_attendee(
			$attendee_id,
			[
				'full_name'         => 'Resend Attendee',
				'email'             => 'resend@example.com',
				'attendee_status'   => 'yes',
				'send_ticket_email' => true,
			]
		);

		$this->assertSame( [ 'rsvp' ], $sent->slugs );
	}

	public function test_a_regular_ticket_still_resends_the_ticket_email(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[ 'full_name' => 'Plain Attendee', 'email' => 'plain@example.com', 'send_ticket_email' => false ]
		);

		$sent = $this->record_emails();

		tribe( Module::class )->update_attendee(
			$attendee->ID,
			[
				'full_name'         => 'Plain Attendee',
				'email'             => 'plain@example.com',
				'send_ticket_email' => true,
			]
		);

		$this->assertNotContains( 'rsvp', $sent->slugs, 'A regular ticket must not send an RSVP email.' );
		$this->assertNotContains( 'rsvp-not-going', $sent->slugs );
	}
}
