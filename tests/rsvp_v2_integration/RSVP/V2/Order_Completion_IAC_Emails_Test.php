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
use TEC\Tickets\Commerce\Emails\RSVP_Email_Sender;
use Tribe\Tests\Traits\With_Uopz;

/**
 * @group rsvp_iac
 */
class Order_Completion_IAC_Emails_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

	private function create_tc_rsvp_order_with_quantity( int $ticket_id, int $quantity, string $order_status, array $purchaser_overrides = [] ): \WP_Post {
		/** @var RSVP_Cart $cart */
		$cart = tribe( RSVP_Cart::class );
		$cart->clear();
		$cart->upsert_item(
			$ticket_id,
			$quantity,
			[
				'type'         => Constants::TC_RSVP_TYPE,
				'order_status' => $order_status,
			]
		);
		$cart->save();

		$purchaser = wp_parse_args(
			$purchaser_overrides,
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Alice',
				'purchaser_first_name' => 'Alice',
				'purchaser_last_name'  => '',
				'purchaser_email'      => 'alice@example.test',
			]
		);

		/** @var Order $orders */
		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart( tribe( Gateway::class ), $purchaser, Constants::TC_RSVP_TYPE );

		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, Completed::SLUG );

		$cart->clear();

		return $order;
	}

	private function intercept_wp_mail(): stdClass {
		$log         = new stdClass();
		$log->emails = [];

		$this->set_fn_return(
			'wp_mail',
			function ( $to, $subject, $message, $headers = '', $attachments = [] ) use ( $log ) {
				$log->emails[] = [
					'to'      => $to,
					'subject' => $subject,
					'message' => $message,
				];
				return true;
			},
			true
		);

		return $log;
	}

	/**
	 * Happy path: IAC order with two attendees and two unique emails.
	 * Mirrors QA repro: total guests 2, alice + bob, each should get mail,
	 * Main Guest (alice) gets bundle with both tickets.
	 */
	public function test_iac_order_with_two_distinct_emails_fans_out_to_both(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		// Create order with quantity 2, purchaser alice.
		$mail = $this->intercept_wp_mail();
		$order = $this->create_tc_rsvp_order_with_quantity( $ticket_id, 2, 'yes', [ 'purchaser_email' => 'alice@example.test', 'purchaser_full_name' => 'Alice' ] );

		// First send was with both attendees sharing alice's email (no IAC yet) -> 1 mail. Clear log.
		$mail->emails = [];

		// Simulate IAC: second attendee gets bob's email/name.
		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$this->assertCount( 2, $attendees, 'Quantity 2 should create 2 attendees.' );

		// Second attendee is the guest (index 1).
		$second = $attendees[1];
		tec_tc_attendees()->where( 'id', $second['attendee_id'] )->set_args( [ 'email' => 'bob@example.test', 'full_name' => 'Bob' ] )->save();
		clean_post_cache( $second['attendee_id'] );
		wp_cache_flush();

		// Re-run sender for the same order (as if order completion re-fired after IAC).
		$mail->emails = [];
		tribe( RSVP_Email_Sender::class )->send( tec_tc_get_order( $order->ID ) );

		$this->assertCount( 2, $mail->emails, 'Exactly two emails should be sent: one per unique holder_email.' );

		$recipients = array_column( $mail->emails, 'to' );
		sort( $recipients );
		$this->assertEquals( [ 'alice@example.test', 'bob@example.test' ], $recipients, 'Mails must go to attendee addresses, not logged-in account.' );

		// Main Guest (alice) gets bundle with both tickets; bob gets single.
		$alice_mail = array_values( array_filter( $mail->emails, fn( $e ) => $e['to'] === 'alice@example.test' ) )[0];
		$bob_mail   = array_values( array_filter( $mail->emails, fn( $e ) => $e['to'] === 'bob@example.test' ) )[0];

		$this->assertStringContainsString( 'Alice', $alice_mail['message'], 'Alice mail should contain Alice.' );
		$this->assertStringContainsString( 'Bob', $alice_mail['message'], 'Alice bundle mail should contain Bob as well.' );
		$this->assertStringContainsString( 'Bob', $bob_mail['message'], 'Bob mail should contain Bob.' );
	}

	/**
	 * Bad path: same email for both attendees -> single bundle mail, no duplicate.
	 */
	public function test_iac_order_with_same_email_sends_single_bundle(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$mail = $this->intercept_wp_mail();
		$order = $this->create_tc_rsvp_order_with_quantity( $ticket_id, 2, 'yes', [ 'purchaser_email' => 'alice@example.test', 'purchaser_full_name' => 'Alice', 'purchaser_first_name' => 'Alice', 'purchaser_last_name' => '' ] );
		$mail->emails = [];

		// Both attendees share alice's email (no IAC distinct).
		tribe( \TEC\Tickets\Commerce\Emails\RSVP_Email_Sender::class )->send( tec_tc_get_order( $order->ID ) );

		$this->assertCount( 1, $mail->emails, 'Duplicate holder_email should dedupe to single bundle mail.' );
		$this->assertEquals( 'alice@example.test', $mail->emails[0]['to'] );
		$this->assertStringContainsString( 'Alice', $mail->emails[0]['message'] );
	}

	/**
	 * Bad path: invalid holder email falls back to purchaser bundle, no extra mail to invalid.
	 */
	public function test_invalid_holder_email_falls_back_to_purchaser(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$mail = $this->intercept_wp_mail();
		$order = $this->create_tc_rsvp_order_with_quantity( $ticket_id, 2, 'yes', [ 'purchaser_email' => 'alice@example.test' ] );

		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$second = $attendees[1];
		tec_tc_attendees()->where( 'id', $second['attendee_id'] )->set_args( [ 'email' => 'not-an-email' ] )->save();
		clean_post_cache( $second['attendee_id'] );
		wp_cache_flush();

		$mail->emails = [];
		tribe( RSVP_Email_Sender::class )->send( tec_tc_get_order( $order->ID ) );

		$this->assertCount( 1, $mail->emails, 'Invalid second email should not create extra mail; only purchaser bundle.' );
		$this->assertEquals( 'alice@example.test', $mail->emails[0]['to'] );
	}

	/**
	 * Logged-in purchaser: order->purchaser['email'] holds the WP account email, which differs
	 * from the Main Guest's typed form email. The bundle must follow the form email, not the account.
	 */
	public function test_logged_in_purchaser_account_email_does_not_receive_bundle(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$mail  = $this->intercept_wp_mail();
		$order = $this->create_tc_rsvp_order_with_quantity(
			$ticket_id,
			2,
			'yes',
			[ 'purchaser_email' => 'account@example.test', 'purchaser_full_name' => 'Alice' ]
		);

		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$this->assertCount( 2, $attendees, 'Quantity 2 should create 2 attendees.' );

		// Main Guest's holder_email is the form email, distinct from the WP account email.
		$first = $attendees[0];
		tec_tc_attendees()->where( 'id', $first['attendee_id'] )->set_args( [ 'email' => 'alice@example.test', 'full_name' => 'Alice' ] )->save();
		clean_post_cache( $first['attendee_id'] );

		$second = $attendees[1];
		tec_tc_attendees()->where( 'id', $second['attendee_id'] )->set_args( [ 'email' => 'bob@example.test', 'full_name' => 'Bob' ] )->save();
		clean_post_cache( $second['attendee_id'] );
		wp_cache_flush();

		$mail->emails = [];
		tribe( RSVP_Email_Sender::class )->send( tec_tc_get_order( $order->ID ) );

		$this->assertCount( 2, $mail->emails, 'Exactly two emails should be sent: alice@ and bob@.' );

		$recipients = array_column( $mail->emails, 'to' );
		sort( $recipients );
		$this->assertEquals( [ 'alice@example.test', 'bob@example.test' ], $recipients );
		$this->assertNotContains( 'account@example.test', $recipients, 'The WP account email must never receive an RSVP email.' );

		$alice_mail = array_values( array_filter( $mail->emails, fn( $e ) => $e['to'] === 'alice@example.test' ) )[0];
		$bob_mail   = array_values( array_filter( $mail->emails, fn( $e ) => $e['to'] === 'bob@example.test' ) )[0];

		$this->assertStringContainsString( 'Alice', $alice_mail['message'], 'Alice mail should contain Alice.' );
		$this->assertStringContainsString( 'Bob', $alice_mail['message'], 'Alice, as Main Guest, should receive the full bundle including Bob.' );
		$this->assertStringContainsString( 'Bob', $bob_mail['message'], 'Bob mail should contain Bob.' );
		$this->assertStringNotContainsString( 'Alice', $bob_mail['message'], 'Bob should only receive his own ticket, not the bundle.' );
	}
}
