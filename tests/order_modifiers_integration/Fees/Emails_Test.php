<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Fees;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Emails_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Order_Maker;
	use Fee_Creator;

	protected string $controller_class = \TEC\Tickets\Commerce\Order_Modifiers\Checkout\Fees::class;
	protected static array $store = [];

	/**
	 * @before
	 */
	public function mock_wp_mail() {
//		$store = &self::$store;
//		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $content, $headers, $attachments ) use ( &$store ) {
//			$store = compact( 'to', 'subject', 'content', 'headers', 'attachments' );
//			return true;
//		}, true );
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

	protected function assertEmailContains( string $text ) {
		$this->assertStringContainsString( $text, self::$store['content'] );
	}

	/**
	 * @test
	 */
	public function it_should_include_fees_in_emails() {
		$post = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, 50 );

		$fee = $this->create_fee_for_ticket( $ticket_id, [ 'raw_amount' => 5, 'sub_type' => 'flat' ] );
		$this->add_fee_to_ticket( $fee, $ticket_id );

		$this->make_controller()->register();

		$order = $this->create_order( [ $ticket_id => 2 ] );
		$refreshed_order = tec_tc_get_order( $order->ID );

		// Trigger email generation
		do_action( 'tec_tickets_commerce_send_email_completed_order', $refreshed_order );
		do_action( 'tec_tickets_commerce_send_email_purchase_receipt', $refreshed_order );

		// Ensure emails were sent
		$this->assertEmailSent();

		// Check if email contains fees
		$this->assertEmailContains( 'Fee' );
		$this->assertEmailContains( '$5' ); // Flat fee should be included

		// Ensure snapshot correctness
		$this->assertMatchesHtmlSnapshot( str_replace( [ $post, $ticket_id ], [ '{POST_ID}', '{TICKET_ID}' ], self::$store['content'] ) );
	}
}
