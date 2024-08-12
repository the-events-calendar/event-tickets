<?php

namespace TEC\Tickets\Commerce\Admin;

use TEC\Tickets\Commerce\Order;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Status\Denied;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Globals;
use Tribe\Tickets\Test\Traits\With_Test_Orders;
use WP_Screen;

class Singular_Order_PageTest extends \Codeception\TestCase\WPTestCase {

	use SnapshotAssertions;
	use With_Uopz;
	use With_Globals;
	use With_Test_Orders;

	/**
	 * Created orders.
	 *
	 * @var array
	 */
	protected $orders;

	/**
	 * Created tickets.
	 *
	 * @var array
	 */
	protected $tickets;

	/**
	 * Created event IDs.
	 *
	 * @var array
	 */
	protected $event_ids;

	/**
	 * Created user IDs.
	 *
	 * @var array
	 */
	protected $user_ids = [];

	/**
	 * @test
	 */
	public function it_should_match_order_details() {
		$this->prepare_test_data();
		$singular_page = tribe( Singular_Order_Page::class );

		$html = [];
		foreach ( $this->orders as $key => $order ) {
			ob_start();
			$singular_page->render_order_details( $order );
			$html[] = str_replace( $order->ID, '{{order_id}}', ob_get_clean() );

		}

		$this->assertMatchesHtmlSnapshot( implode( PHP_EOL . '<!--NEXT ITEM-->' . PHP_EOL, $html ) );
	}

	/**
	 * @test
	 */
	public function it_should_match_order_actions() {
		$this->prepare_test_data();
		$singular_page = tribe( Singular_Order_Page::class );

		$html = [];
		foreach ( $this->orders as $key => $order ) {
			ob_start();
			$singular_page->render_actions( $order );
			$html[] = str_replace( $order->ID, '{{order_id}}', ob_get_clean() );

		}

		$this->assertMatchesHtmlSnapshot( implode( PHP_EOL . '<!--NEXT ITEM-->' . PHP_EOL, $html ) );
	}

	/**
	 * @test
	 */
	public function it_should_match_gateway_label() {
		$this->prepare_test_data();
		$singular_page = tribe( Singular_Order_Page::class );

		$html = [];
		foreach ( $this->orders as $key => $order ) {
			$html[] = str_replace( $order->ID, '{{order_id}}', $singular_page->get_gateway_label( $order ) );
		}

		$this->assertMatchesHtmlSnapshot( implode( PHP_EOL . '<!--NEXT ITEM-->' . PHP_EOL, $html ) );
	}

	/**
	 * @test
	 */
	public function it_should_match_order_items() {
		$this->prepare_test_data();
		$singular_page = tribe( Singular_Order_Page::class );

		$html = [];
		foreach ( $this->orders as $key => $order ) {
			ob_start();
			$singular_page->render_order_items( $order );
			$html[] = str_replace( $order->ID, '{{order_id}}', str_replace( $this->event_ids, '{{EVENT_ID}}', ob_get_clean() ) );

		}

		$this->assertMatchesHtmlSnapshot( implode( PHP_EOL . '<!--NEXT ITEM-->' . PHP_EOL, $html ) );
	}

	/**
	 * @test
	 */
	public function it_should_update_order_status() {
		$this->prepare_test_data();
		$singular_page = tribe( Singular_Order_Page::class );

		foreach ( $this->orders as $order ) {
			// Set is_admin to true.
			$this->set_global_value( 'current_screen', WP_Screen::get( 'edit-' . Order::POSTTYPE ) );
			// Set current user to admin.
			wp_set_current_user( 1 );
			// Set request `tribe-tickets-commerce-status` to `completed`.
			$this->set_global_value( '_REQUEST', tribe( Denied::class )->get_slug(), 'tribe-tickets-commerce-status' );

			$singular_page->update_order_status( $order->ID, $order );

			$this->assertEquals( tribe( Denied::class )->get_wp_slug(), get_post_status( $order->ID ) );
		}
	}
}
