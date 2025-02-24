<?php

namespace TEC\Tickets\Commerce\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Traits\Type;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Traits\With_Test_Orders;
use WP_Screen;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Singular_Order_PageTest extends WPTestCase {

	use Coupon_Creator;
	use SnapshotAssertions;
	use Type;
	use With_Test_Orders;
	use With_Uopz;

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
	public function it_should_match_order_actions_as_admin() {
		$this->prepare_test_data();
		$singular_page = tribe( Singular_Order_Page::class );

		// Login as admin.
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

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
	public function it_should_render_breadcrumb() {
		$screen                  = WP_Screen::get( '' );
		$screen->base            = 'post';
		$screen->action          = '';
		$screen->post_type       = Order::POSTTYPE;
		$screen->parent_file     = 'edit.php?post_type=tec_tc_order';
		$screen->is_block_editor = false;

		$this->set_fn_return( 'get_current_screen', $screen );

		$singular_page = tribe( Singular_Order_Page::class );
		ob_start();
		$singular_page->render_breadcrumb_order_edit_screen_html();
		$html = ob_get_clean();
		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_update_order_status() {
		$this->prepare_test_data();
		$singular_page = tribe( Singular_Order_Page::class );

		$counter = did_action( 'tec_tickets_commerce_order_status_transition' ) ? did_action( 'tec_tickets_commerce_order_status_transition' ) : 1;

		foreach ( $this->orders as $order ) {
			// Set is_admin to true.
			$GLOBALS[ 'current_screen'] =  WP_Screen::get( 'edit-' . Order::POSTTYPE );
			// Set current user to admin.
			wp_set_current_user( 1 );

			// Set request `tribe-tickets-commerce-status` to `DENIED`.
			$_REQUEST['tribe-tickets-commerce-status'] = tribe( Denied::class )->get_slug();

			$singular_page->update_order_status( $order->ID, $order );

			$this->assertEquals( ++$counter, did_action( 'tec_tickets_commerce_order_status_transition' ) );

			$this->assertEquals( tribe( Denied::class )->get_wp_slug(), get_post_status( $order->ID ) );
		}
	}

	/**
	 * @test
	 */
	public function it_should_render_coupons() {
		// First, we should ensure that orders with no coupons have no mention of coupons.
		$this->prepare_test_data();

		/** @var Singular_Order_Page $singular_page */
		$singular_page = tribe( Singular_Order_Page::class );

		foreach ( $this->orders as $order ) {
			ob_start();
			$singular_page->render_order_items( $order );
			$html = ob_get_clean();

			// These orders don't have coupons, so they shouldn't mention them at all.
			$this->assertFalse(
				stripos( $html, 'coupon' ),
				'Orders without coupons should have no mention of coupons in the HTML.'
			);
		}

		// Now, we should add a coupon to the order and ensure it is displayed.
		$coupon = $this->create_coupon();
		$order  = $this->create_order(
			[
				$this->tickets[0] => 1,
				$coupon->id       => [
					'id'     => $this->get_unique_type_id( $coupon->id, 'coupon' ),
					'extras' => [ 'type' => 'coupon' ],
				],
			]
		);

		ob_start();
		$singular_page->render_order_items( $order );
		$html = ob_get_clean();

		$html = str_replace( $this->tickets, '{{TICKET_ID}}', $html );
		$html = str_replace( $order->ID, '{{ORDER_ID}}', $html );
		$html = str_replace( $this->event_ids, '{{EVENT_ID}}', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
