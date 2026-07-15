<?php
namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\Shepherd\Regulator;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use WP_Post;

/**
 * @see https://linear.app/nexcess/issue/SOFT-3854 "Can't Go" RSVP emails are wrong.
 */
class Emails_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * @before
	 */
	public function ensure_clean_cart(): void {
		tribe( Cart::class )->clear_cart();
	}

	/**
	 * @after
	 */
	public function cleanup_cart(): void {
		tribe( Cart::class )->clear_cart();
	}

	public function use_rsvp_cart() {
		return tribe( RSVP_Cart::class );
	}

	/**
	 * Order emails are dispatched to a Shepherd background job for real requests. For the
	 * purposes of this test we want them to run in the same request so we can inspect what
	 * would have been sent via `wp_mail()`.
	 */
	private function run_dispatched_email_jobs_synchronously(): void {
		$this->set_class_fn_return(
			Regulator::class,
			'dispatch',
			function ( $task, $delay = 0 ) {
				$task->process();
			},
			true
		);
	}

	/**
	 * Intercepted `wp_mail()` calls are collected in a $GLOBALS entry rather than a class
	 * property because uopz drops all class scope (including `self::`) from the closure
	 * that replaces the global `wp_mail()` function.
	 */
	private function intercept_emails(): void {
		$GLOBALS['tec_rsvp_v2_emails_test_sent_emails'] = [];

		$this->set_fn_return(
			'wp_mail',
			static function ( $to, $subject, $message, $headers = '', $attachments = [] ) {
				$GLOBALS['tec_rsvp_v2_emails_test_sent_emails'][] = [
					'to'      => $to,
					'subject' => $subject,
					'message' => $message,
				];

				return true;
			},
			true
		);
	}

	private function sent_emails(): array {
		return $GLOBALS['tec_rsvp_v2_emails_test_sent_emails'] ?? [];
	}

	/**
	 * Creates and completes an RSVP V2 order, mirroring what
	 * Order_Endpoint::process_rsvp_step() does for the "success" step.
	 *
	 * @param string $order_status 'yes' for Going, 'no' for Can't Go.
	 * @param string $email        The attendee/purchaser email.
	 *
	 * @return WP_Post The completed, fully-decorated order.
	 */
	private function create_and_complete_rsvp_order( string $order_status, string $email ): WP_Post {
		add_filter( 'tec_tickets_commerce_cart_repository', [ $this, 'use_rsvp_cart' ] );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$cart = tribe( RSVP_Cart::class );
		$cart->clear();
		$cart->upsert_item(
			$ticket_id,
			1,
			[
				'type'         => Constants::TC_RSVP_TYPE,
				'order_status' => $order_status,
				'optout'       => false,
			]
		);
		$cart->save();

		$purchaser = tribe( Order::class )->get_purchaser_data(
			[
				'purchaser' => [
					'name'  => 'Test RSVP User',
					'email' => $email,
				],
			]
		);

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser, Constants::TC_RSVP_TYPE );

		tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );
		tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		tribe( Cart::class )->clear_cart();
		remove_filter( 'tec_tickets_commerce_cart_repository', [ $this, 'use_rsvp_cart' ] );

		clean_post_cache( $order->ID );

		return tec_tc_get_order( $order->ID );
	}

	private function subjects_containing( string $needle ): array {
		return array_values(
			array_filter(
				$this->sent_emails(),
				fn( $email ) => false !== strpos( $email['subject'], $needle )
			)
		);
	}

	private function emails_containing( string $needle ): array {
		return array_values(
			array_filter(
				$this->sent_emails(),
				fn( $email ) => false !== strpos( $email['subject'], $needle ) || false !== strpos( $email['message'], $needle )
			)
		);
	}

	/**
	 * @test
	 */
	public function it_should_send_purchase_and_completed_order_emails_for_going_rsvp(): void {
		$this->run_dispatched_email_jobs_synchronously();
		$this->intercept_emails();

		$this->create_and_complete_rsvp_order( 'yes', 'going@example.com' );

		$this->assertNotEmpty( $this->subjects_containing( 'purchase receipt' ) );
		$this->assertNotEmpty( $this->subjects_containing( 'Completed order' ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_send_purchase_or_completed_order_emails_for_not_going_rsvp(): void {
		$this->run_dispatched_email_jobs_synchronously();
		$this->intercept_emails();

		$this->create_and_complete_rsvp_order( 'no', 'notgoing@example.com' );

		$this->assertEmpty(
			$this->subjects_containing( 'purchase receipt' ),
			'A "Can\'t Go" RSVP must not trigger the Tickets Commerce purchase receipt email.'
		);
		$this->assertEmpty(
			$this->subjects_containing( 'Completed order' ),
			'A "Can\'t Go" RSVP must not trigger the Tickets Commerce admin completed-order email.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_send_not_going_confirmation_email_for_not_going_rsvp(): void {
		$this->run_dispatched_email_jobs_synchronously();
		$this->intercept_emails();

		$this->create_and_complete_rsvp_order( 'no', 'notgoing@example.com' );

		$matches = $this->subjects_containing( 'You confirmed you will not be attending' );

		$this->assertCount( 1, $matches, 'The RSVP "Not Going" confirmation email should be sent exactly once.' );
		$this->assertSame( 'notgoing@example.com', $matches[0]['to'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_send_not_going_confirmation_email_for_going_rsvp(): void {
		$this->run_dispatched_email_jobs_synchronously();
		$this->intercept_emails();

		$this->create_and_complete_rsvp_order( 'yes', 'going@example.com' );

		$this->assertEmpty(
			$this->subjects_containing( 'You confirmed you will not be attending' ),
			'A "Going" RSVP must not trigger the "Not Going" confirmation email.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_send_ticket_delivery_email_for_not_going_rsvp(): void {
		$this->run_dispatched_email_jobs_synchronously();
		$this->intercept_emails();

		$this->create_and_complete_rsvp_order( 'no', 'notgoing4@example.com' );

		$this->assertEmpty(
			$this->emails_containing( "Here's your" ),
			'A "Can\'t Go" RSVP must not trigger the generic ticket delivery email.'
		);
	}
}
