<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Fees;

use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Tickets as Tickets;

class Emails_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use Fee_Creator;
	use With_Uopz;
	use SnapshotAssertions;

	protected static array $store = [];

	/**
	 * @before
	 */
	public function mock_wp_mail() {
		$store = &self::$store;
		$this->set_fn_return(
			'wp_mail',
			function ( $to, $subject, $content, $headers, $attachments ) use ( &$store ) {
				$store = compact( 'to', 'subject', 'content', 'headers', 'attachments' );
				return true;
			},
			true
		);
	}

	/**
	 * @after
	 */
	public function destroy_store() {
		self::$store = [];
	}

	protected function assertEmailSent() {
		$this->assertNotEmpty( self::$store );
	}

	/**
	 * @test
	 */
	public function it_should_include_fees_in_purchase_receipt() {
		$post      = static::factory()->post->create( [ 'post_name' => 'Event post' ] );
		$ticket_id = $this->create_tc_ticket( $post, 50 );

		$fee = $this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 5,
				'sub_type'   => 'flat',
			]
		);

		$this->set_class_fn_return( Tickets::class, 'generate_security_code', 'attendee-security-code' );

		$order           = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'sam@tec.com' ] );
		$refreshed_order = tec_tc_get_order( $order->ID );

		// Trigger email generation
		do_action( 'tec_tickets_commerce_send_email_purchase_receipt', $refreshed_order );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id, $order->ID ], [ '{POST_ID}', '{TICKET_ID}', '{ORDER_ID}' ], self::$store['content'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_include_fees_in_order_completed() {
		$post      = static::factory()->post->create( [ 'post_name' => 'Event post' ] );
		$ticket_id = $this->create_tc_ticket( $post, 50 );

		$fee = $this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 5,
				'sub_type'   => 'flat',
			]
		);

		$this->set_class_fn_return( Tickets::class, 'generate_security_code', 'attendee-security-code' );

		$order           = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'sam@tec.com' ] );
		$refreshed_order = tec_tc_get_order( $order->ID );

		// Trigger email generation
		do_action( 'tec_tickets_commerce_send_email_completed_order', $refreshed_order );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id, $order->ID ], [ '{POST_ID}', '{TICKET_ID}', '{ORDER_ID}' ], self::$store['content'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_break_template_when_adding_multiple_fees_to_same_ticket() {
		$post      = static::factory()->post->create( [ 'post_name' => 'Event post' ] );
		$ticket_id = $this->create_tc_ticket( $post, 50 );

		// Add 10 fees to the ticket
		for ( $i = 0; $i < 10; $i++ ) {
			$this->create_fee_for_ticket(
				$ticket_id,
				[
					'raw_amount' => 5,
					'sub_type'   => 'flat',
				]
			);
		}

		$this->set_class_fn_return( Tickets::class, 'generate_security_code', 'attendee-security-code' );

		$order           = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'sam@tec.com' ] );
		$refreshed_order = tec_tc_get_order( $order->ID );

		// Trigger email generation
		do_action( 'tec_tickets_commerce_send_email_purchase_receipt', $refreshed_order );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id, $order->ID ], [ '{POST_ID}', '{TICKET_ID}', '{ORDER_ID}' ], self::$store['content'] ) );
	}

}
