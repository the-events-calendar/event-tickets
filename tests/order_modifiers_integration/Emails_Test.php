<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Tickets as Tickets;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe__Date_Utils as Dates;
use TEC\Tickets\Commerce\Order_Modifiers\Controller as Order_Modifiers_Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Fees as Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal\Fees as Checkout_PayPal_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe\Fees as Checkout_Stripe_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees as API_Fees;
use TEC\Tickets\Commerce\Order;

class Emails_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Order_Maker;
	use Fee_Creator;
	use With_Uopz;
	use SnapshotAssertions;
	use With_Clock_Mock;

	protected string $controller_class = Emails::class;

	protected static array $store = [];

	/**
	 * @before
	 */
	public function mock_wp_mail() {
		$store = &self::$store;
		$this->set_fn_return(
			'wp_mail',
			function ( $to, $subject, $content, $headers, $attachments ) use ( &$store ) {
				$store[] = compact( 'to', 'subject', 'content', 'headers', 'attachments' );
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
		$this->freeze_time( Dates::immutable( '2025-02-20 10:00:00' ) );
		$this->make_controller()->register();
		$post      = static::factory()->post->create( [ 'post_title' => 'Event post' ] );
		$ticket_id = $this->create_tc_ticket( $post, 50 );

		$this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 5,
				'sub_type'   => 'flat',
			]
		);

		$this->set_class_fn_return( Tickets::class, 'generate_security_code', 'attendee-security-code' );

		$order = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'sam@tec.com' ] );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id, $order->ID ], [ '{POST_ID}', '{TICKET_ID}', '{ORDER_ID}' ], self::$store['2']['content'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_include_fees_in_order_completed() {
		$this->freeze_time( Dates::immutable( '2025-02-20 10:00:00' ) );
		$this->make_controller()->register();
		$post      = static::factory()->post->create( [ 'post_title' => 'Event post' ] );
		$ticket_id = $this->create_tc_ticket( $post, 50 );

		$this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 5,
				'sub_type'   => 'flat',
			]
		);

		$this->set_class_fn_return( Tickets::class, 'generate_security_code', 'attendee-security-code' );
		$this->set_class_fn_return( Order::class, 'generate_order_key', 'paypal-gateway-id' );

		$order = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'sam@tec.com' ] );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id, $order->ID ], [ '{POST_ID}', '{TICKET_ID}', '{ORDER_ID}' ], self::$store['1']['content'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_break_template_when_adding_multiple_fees_to_same_ticket() {
		$this->freeze_time( Dates::immutable( '2025-02-20 10:00:00' ) );
		$this->make_controller()->register();
		$post      = static::factory()->post->create( [ 'post_title' => 'Event post' ] );
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

		$order = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'sam@tec.com' ] );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id, $order->ID ], [ '{POST_ID}', '{TICKET_ID}', '{ORDER_ID}' ], self::$store['2']['content'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_allow_multiple_fees_of_different_types_on_the_same_ticket() {
		$this->freeze_time( Dates::immutable( '2025-02-20 10:00:00' ) );
		$this->make_controller()->register();
		$post      = static::factory()->post->create( [ 'post_title' => 'Event post' ] );
		$ticket_id = $this->create_tc_ticket( $post, 50 );

		// Add multiple fees of different types to the same ticket
		$this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 5,
				'sub_type'   => 'flat',
			]
		);

		$this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 10,
				'sub_type'   => 'percent',
			]
		);

		$this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 2.5,
				'sub_type'   => 'flat',
			]
		);

		$this->create_fee_for_ticket(
			$ticket_id,
			[
				'raw_amount' => 15,
				'sub_type'   => 'percent',
			]
		);

		$this->set_class_fn_return( Tickets::class, 'generate_security_code', 'attendee-security-code' );

		$order = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'sam@tec.com' ] );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id, $order->ID ], [ '{POST_ID}', '{TICKET_ID}', '{ORDER_ID}' ], self::$store['2']['content'] ) );
	}
}
