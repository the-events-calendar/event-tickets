<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Reports;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Reports\Data\Order_Summary;
use TEC\Tickets\Commerce\Status\Completed;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

/**
 * Regression: a fee or coupon line can be the first thing to open a status row, because the
 * ticket it belongs to may be gone or excluded from the sales data by the time the report runs.
 * The row it opens has to carry every key `format_prices()` goes on to read, or the report fatals.
 */
class Order_Summary_Test extends WPTestCase {

	use Coupon_Creator;
	use Fee_Creator;
	use Order_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	public function test_summary_builds_when_a_coupon_opens_the_status_row(): void {
		$post      = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, 10 );
		$coupon    = $this->create_coupon();

		$this->create_order(
			[
				$ticket_id  => 1,
				$coupon->id => [
					'quantity' => 1,
					'extras'   => [ 'type' => 'coupon' ],
				],
			]
		);

		/* With the ticket gone, the coupon is the only line left to open the status row. */
		wp_delete_post( $ticket_id, true );

		$summary = new Order_Summary( $post );
		$summary->init();

		$by_status = $summary->get_event_sales_data()['by_status'];

		$this->assertArrayHasKey( Completed::SLUG, $by_status );
		$this->assertCount( 1, $by_status[ Completed::SLUG ]['total_discount_amounts'] );
		$this->assertSame( [], $by_status[ Completed::SLUG ]['total_fee_amounts'] );
	}

	public function test_summary_builds_when_a_fee_opens_the_status_row(): void {
		$post      = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, 10 );

		$this->create_fee_for_ticket( $ticket_id, [ 'raw_amount' => 5, 'sub_type' => 'flat' ] );

		$this->create_order( [ $ticket_id => 1 ] );

		add_filter( 'tec_tickets_commerce_order_report_summary_should_include_event_sales_data', '__return_false' );

		$summary = new Order_Summary( $post );
		$summary->init();

		$by_status = $summary->get_event_sales_data()['by_status'];

		$this->assertArrayHasKey( Completed::SLUG, $by_status );
		$this->assertCount( 1, $by_status[ Completed::SLUG ]['total_fee_amounts'] );
		$this->assertSame( [], $by_status[ Completed::SLUG ]['total_discount_amounts'] );
	}
}
