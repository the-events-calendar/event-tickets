<?php

namespace TEC\Tickets\Commerce\Emails;

use Codeception\TestCase\WPTestCase;
use stdClass;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\RSVP\V2\Constants;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;

class RSVP_Email_Sender_IAC_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

	public function setUp(): void {
		parent::setUp();
		add_filter( 'tec_tickets_rsvp_version', fn() => 'v2' );
	}

	public function tearDown(): void {
		remove_filter( 'tec_tickets_rsvp_version', fn() => 'v2' );
		parent::tearDown();
	}

	private function create_tc_rsvp_order_with_quantity( int $ticket_id, int $quantity, string $order_status, array $purchaser_overrides = [] ): \WP_Post {
		/** @var RSVP_Cart $cart */
		$cart = tribe( RSVP_Cart::class );
		$cart->clear();
		$cart->upsert_item( $ticket_id, $quantity, [ 'type' => Constants::TC_RSVP_TYPE, 'order_status' => $order_status ] );
		$cart->save();

		$purchaser = wp_parse_args( $purchaser_overrides, [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Alice',
			'purchaser_first_name' => 'Alice',
			'purchaser_last_name'  => '',
			'purchaser_email'      => 'alice@example.test',
		] );

		/** @var Order $orders */
		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart( tribe( Gateway::class ), $purchaser, Constants::TC_RSVP_TYPE );
		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, Completed::SLUG );
		$cart->clear();

		return $order;
	}

	private function intercept_wp_mail(): stdClass {
		$log = new stdClass();
		$log->emails = [];
		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $message ) use ( $log ) {
			$log->emails[] = [ 'to' => $to, 'subject' => $subject, 'message' => $message ];
			return true;
		}, true );
		return $log;
	}

	public function test_happy_path_two_distinct_emails_fan_out(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$mail = $this->intercept_wp_mail();
		$order = $this->create_tc_rsvp_order_with_quantity( $ticket_id, 2, 'yes', [ 'purchaser_email' => 'alice@example.test', 'purchaser_full_name' => 'Alice' ] );
		// First mail was with same email for both attendees (1). Clear.
		$mail->emails = [];

		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$this->assertCount( 2, $attendees );
		$second = $attendees[1];
		update_post_meta( $second['attendee_id'], '_tec_tickets_commerce_email', 'bob@example.test' );
		update_post_meta( $second['attendee_id'], '_tec_tickets_commerce_full_name', 'Bob' );

		$mail->emails = [];
		tribe( RSVP_Email_Sender::class )->send( tec_tc_get_order( $order->ID ) );

		$this->assertCount( 2, $mail->emails, 'IAC with two unique emails must send two mails.' );
		$tos = array_column( $mail->emails, 'to' );
		sort( $tos );
		$this->assertEquals( [ 'alice@example.test', 'bob@example.test' ], $tos );

		$alice = array_values( array_filter( $mail->emails, fn( $e ) => $e['to'] === 'alice@example.test' ) )[0];
		$bob   = array_values( array_filter( $mail->emails, fn( $e ) => $e['to'] === 'bob@example.test' ) )[0];
		$this->assertStringContainsString( 'Alice', $alice['message'] );
		$this->assertStringContainsString( 'Bob', $alice['message'], 'Main Guest bundle must contain both.' );
		$this->assertStringContainsString( 'Bob', $bob['message'] );
	}

	public function test_bad_path_same_email_single_bundle(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$mail = $this->intercept_wp_mail();
		$order = $this->create_tc_rsvp_order_with_quantity( $ticket_id, 2, 'yes', [ 'purchaser_email' => 'alice@example.test' ] );
		$mail->emails = [];
		tribe( RSVP_Email_Sender::class )->send( tec_tc_get_order( $order->ID ) );

		$this->assertCount( 1, $mail->emails, 'Same email should dedupe to single bundle.' );
		$this->assertEquals( 'alice@example.test', $mail->emails[0]['to'] );
	}

	public function test_bad_path_invalid_email_falls_back(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$mail = $this->intercept_wp_mail();
		$order = $this->create_tc_rsvp_order_with_quantity( $ticket_id, 2, 'yes', [ 'purchaser_email' => 'alice@example.test' ] );
		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		update_post_meta( $attendees[1]['attendee_id'], '_tec_tickets_commerce_email', 'not-an-email' );
		$mail->emails = [];
		tribe( RSVP_Email_Sender::class )->send( tec_tc_get_order( $order->ID ) );

		$this->assertCount( 1, $mail->emails, 'Invalid second email must not create extra mail.' );
		$this->assertEquals( 'alice@example.test', $mail->emails[0]['to'] );
	}
}
