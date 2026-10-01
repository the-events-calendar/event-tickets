<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use stdClass;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;

/**
 * An attendee flipping their response between "Going" and "Not Going" on the My Tickets page never
 * touches the order, so the `send_email` flag action — which only fires on an order status
 * transition — cannot cover it. These tests pin the email that `Frontend::update_attendee_data()`
 * sends instead; without it the response silently changes and the attendee is told nothing.
 */
class Status_Change_Emails_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use With_Uopz;

	/**
	 * Creates a user-owned TC-RSVP order with a single attendee at a known RSVP status, and logs
	 * that user in — `update_attendee_data()` only acts for the logged-in order purchaser.
	 *
	 * The ticket is created as a regular $0 TC ticket so the cart-based order creation works, then
	 * retyped to TC-RSVP, matching the fixture used by Frontend_Test.
	 *
	 * @return array{attendee_id: int, post_id: int, purchaser_email: string}
	 */
	private function create_rsvp_attendee( string $rsvp_status ): array {
		$user_id         = static::factory()->user->create( [ 'role' => 'subscriber' ] );
		$post_id         = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id       = $this->create_tc_ticket( $post_id, 0 );
		$purchaser_email = 'attendee@example.com';

		$order = $this->create_order(
			[ $ticket_id => 1 ],
			[
				'purchaser_user_id' => $user_id,
				'purchaser_email'   => $purchaser_email,
			]
		);

		update_post_meta( $ticket_id, '_type', Constants::TC_RSVP_TYPE );

		$attendees   = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$attendee_id = (int) $attendees[0]['attendee_id'];

		update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, $rsvp_status );

		wp_set_current_user( $user_id );

		return [
			'attendee_id'     => $attendee_id,
			'post_id'         => $post_id,
			'purchaser_email' => $purchaser_email,
		];
	}

	/**
	 * Starts recording emails from this point on, so the order-completion email sent while building
	 * the fixture is not counted.
	 *
	 * Records the email slug off the dispatcher rather than matching on subject text, so the
	 * assertions stay valid if the default copy is reworded.
	 *
	 * @return stdClass An object whose `sent` property collects `[slug, to]` pairs.
	 */
	private function record_emails(): stdClass {
		$log       = new stdClass();
		$log->sent = [];

		add_filter(
			'tec_tickets_emails_dispatcher',
			static function ( $dispatcher, $email ) use ( $log ) {
				if ( $email instanceof Email_Abstract ) {
					$log->sent[] = [
						'slug' => $email->slug,
						'to'   => $email->get_recipient(),
					];
				}

				return $dispatcher;
			},
			10,
			2
		);

		$this->set_fn_return( 'wp_mail', true, false );

		return $log;
	}

	public function test_changing_response_to_not_going_sends_the_not_going_email(): void {
		$fixture = $this->create_rsvp_attendee( 'yes' );
		$log     = $this->record_emails();

		do_action(
			'event_tickets_attendee_update',
			[ 'order_status' => 'not_going' ],
			$fixture['attendee_id'],
			$fixture['post_id']
		);

		$this->assertCount( 1, $log->sent, 'Flipping to Not Going should send exactly one email.' );
		$this->assertSame( 'rsvp-not-going', $log->sent[0]['slug'] );
		$this->assertSame( $fixture['purchaser_email'], $log->sent[0]['to'] );
	}

	public function test_changing_response_to_going_sends_the_rsvp_email(): void {
		$fixture = $this->create_rsvp_attendee( 'no' );
		$log     = $this->record_emails();

		do_action(
			'event_tickets_attendee_update',
			[ 'order_status' => 'going' ],
			$fixture['attendee_id'],
			$fixture['post_id']
		);

		$this->assertCount( 1, $log->sent, 'Flipping to Going should send exactly one email.' );
		$this->assertSame( 'rsvp', $log->sent[0]['slug'] );
		$this->assertSame( $fixture['purchaser_email'], $log->sent[0]['to'] );
	}

	public function test_resubmitting_the_same_response_sends_no_email(): void {
		$fixture = $this->create_rsvp_attendee( 'yes' );
		$log     = $this->record_emails();

		do_action(
			'event_tickets_attendee_update',
			[ 'order_status' => 'going' ],
			$fixture['attendee_id'],
			$fixture['post_id']
		);

		$this->assertSame( [], $log->sent, 'An unchanged response must not send an email.' );
	}

	public function test_a_user_who_does_not_own_the_order_gets_no_email(): void {
		$fixture    = $this->create_rsvp_attendee( 'yes' );
		$other_user = static::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $other_user );

		$log = $this->record_emails();

		do_action(
			'event_tickets_attendee_update',
			[ 'order_status' => 'not_going' ],
			$fixture['attendee_id'],
			$fixture['post_id']
		);

		$this->assertSame( [], $log->sent, 'Only the order purchaser can change the response.' );
		$this->assertSame(
			'yes',
			get_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, true ),
			'The response must not change for a non-owner.'
		);
	}
}
