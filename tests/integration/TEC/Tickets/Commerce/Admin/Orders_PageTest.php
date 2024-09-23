<?php

namespace TEC\Tickets\Commerce\Admin;

use TEC\Tickets\Commerce\Order;
use Tribe\Tickets\Test\Traits\With_Globals;
use WP_Screen;
use Tribe\Tickets\Admin\Settings;
use Tribe\Admin\Pages;
use WP_User;
use WP_Query;
use TEC\Tickets\Commerce\Admin_Tables\Orders_Table;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Voided;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Hooks;

class Orders_PageTest extends \Codeception\TestCase\WPTestCase {

	use With_Globals;

	/**
	 * @before
	 */
	public function set_up() {
		$this->set_global_value( 'current_screen', WP_Screen::get( 'edit-' . Order::POSTTYPE ) );
		$this->set_global_value( 'typenow', Order::POSTTYPE );
	}

	/**
	 * @test
	 */
	public function it_should_match_parent_page_slug() {
		$this->assertEquals( Settings::$parent_slug, Orders_Page::$parent_slug );
	}

	/**
	 * @test
	 */
	public function it_should_add_new_admin_page() {
		$orders_page = new Orders_Page();

		$this->assertEquals( 'Orders', $orders_page->get_page_title() );
		$this->assertEquals( 'Orders', $orders_page->get_menu_title() );
		$this->assertEquals( Pages::get_capability(), $orders_page->get_capability() );
		$this->assertEquals( 'edit.php?post_type=' . Order::POSTTYPE, $orders_page->get_menu_slug() );
		$this->assertEquals( 1.7, $orders_page->get_position() );

		global $submenu;

		if ( ! is_array( $submenu ) ) {
			$this->set_global_value( 'submenu', array( Orders_Page::$parent_slug => [] ) );
		}

		$this->set_global_value( 'current_user', new WP_User( 1 ) );

		$new_sub_menu = array( $orders_page->get_menu_title(), $orders_page->get_capability(), $orders_page->get_menu_slug(), $orders_page->get_page_title() );

		$this->assertFalse( tribe( 'assets' )->exists( 'event-tickets-commerce-admin-orders-css' ) );
		$this->assertFalse( tribe( 'assets' )->exists( 'event-tickets-commerce-admin-orders' ) );
		$this->assertFalse( in_array( $new_sub_menu, $submenu[ Orders_Page::$parent_slug ], true ) );

		$orders_page->add_orders_page();

		$this->assertTrue( tribe( 'assets' )->exists( 'event-tickets-commerce-admin-orders-css' ) );
		$this->assertTrue( tribe( 'assets' )->exists( 'event-tickets-commerce-admin-orders' ) );

		$this->assertTrue( in_array( $new_sub_menu, $submenu[ Orders_Page::$parent_slug ], true ) );
	}

	/**
	 * @test
	 */
	public function it_should_locate_the_orders_page() {
		$orders_page = new Orders_Page();

		$this->set_global_value( 'current_screen', WP_Screen::get( 'edit' ) );
		$this->assertFalse( $orders_page->is_admin_orders_page() );

		$this->set_global_value( 'current_screen', WP_Screen::get( 'edit-' . Order::POSTTYPE ) );
		$this->assertTrue( $orders_page->is_admin_orders_page() );
	}

	/**
	 * @test
	 */
	public function it_should_locate_the_singular_order_page() {
		$orders_page = new Orders_Page();

		$this->set_global_value( 'current_screen', WP_Screen::get( 'edit' ) );
		$this->assertFalse( $orders_page->is_admin_single_page() );

		$this->set_global_value( 'current_screen', WP_Screen::get( Order::POSTTYPE ) );
		$this->assertTrue( $orders_page->is_admin_single_page() );
	}

	/**
	 * @test
	 */
	public function it_should_locate_the_orders_or_the_singular_order_page() {
		$orders_page = new Orders_Page();

		$this->set_global_value( 'current_screen', WP_Screen::get( 'edit' ) );
		$this->assertFalse( $orders_page->is_admin_orders_page_or_admin_single_page() );

		$this->set_global_value( 'current_screen', WP_Screen::get( Order::POSTTYPE ) );
		$this->assertTrue( $orders_page->is_admin_orders_page_or_admin_single_page() );

		$this->set_global_value( 'current_screen', WP_Screen::get( 'edit-' . Order::POSTTYPE ) );
		$this->assertTrue( $orders_page->is_admin_orders_page_or_admin_single_page() );
	}

	/**
	 * @test
	 */
	public function it_should_user_custom_list_table_class() {
		$this->assertInstanceOf( Orders_Table::class, _get_list_table( 'WP_Posts_List_Table' ) );
	}

	/**
	 * @test
	 */
	public function it_should_pre_filter_global_wp_query() {
		$args = [
			'post_type' => Order::POSTTYPE,
			'posts_per_page' => 10,
			'post_status' => 'any',
			'order'       => 'ASC',
			'orderby'     => 'ID',
		];

		$query = $this->overwrite_global_wp_query( $args );

		$new_query = tribe( Hooks::class )->pre_filter_admin_order_table( $query );

		$this->assertSame( $query, $new_query );

		$this->assertEmpty( $query->get( 'meta_query' ) );

		$this->set_global_value( '_GET', 'free', 'tec_tc_gateway' );

		$new_query = tribe( Hooks::class )->pre_filter_admin_order_table( $query );

		$this->assertEquals(
			[
				[
					'key'     => Order::$gateway_meta_key,
					'value'   => 'free',
					'compare' => '=',
				]
			],
			$new_query->get( 'meta_query' )
		);

		$query->set( 'meta_query', [] );

		$this->assertEmpty( $query->get( 'meta_query' ) );

		$this->set_global_value( '_GET', 'stripe', 'tec_tc_gateway' );
		$this->set_global_value( '_GET', '6', 'tec_tc_events' );

		$new_query = tribe( Hooks::class )->pre_filter_admin_order_table( $query );

		$this->assertEquals(
			[
				[
					'key'     => Order::$gateway_meta_key,
					'value'   => 'stripe',
					'compare' => '=',
				],
				[
					'key'     => Order::$events_in_order_meta_key,
					'value'   => 6,
					'compare' => 'IN',
				],
				'relation' => 'AND',
			],
			$new_query->get( 'meta_query' )
		);

		$query->set( 'meta_query', [] );

		$this->assertEmpty( $query->get( 'meta_query' ) );

		$this->set_global_value( '_GET', '1', 'tec_tc_customers' );

		$new_query = tribe( Hooks::class )->pre_filter_admin_order_table( $query );

		$this->assertEquals(
			[
				[
					'key'     => Order::$gateway_meta_key,
					'value'   => 'stripe',
					'compare' => '=',
				],
				[
					'key'     => Order::$events_in_order_meta_key,
					'value'   => 6,
					'compare' => 'IN',
				],
				[
					'key'     => Order::$purchaser_user_id_meta_key,
					'value'   => 1,
					'compare' => '=',
				],
				'relation' => 'AND',
			],
			$new_query->get( 'meta_query' )
		);

		$this->assertEmpty( $new_query->get( 'date_query' ) );

		$this->set_global_value( '_GET', '2024-06-18', 'tec_tc_date_range_from' );
		$this->set_global_value( '_GET', '2024-06-20', 'tec_tc_date_range_to' );

		$new_query = tribe( Hooks::class )->pre_filter_admin_order_table( $query );

		$this->assertEquals(
			[
				[
					'after'     => '2024-06-18 00:00:00',
					'inclusive' => true
				],
				[
					'before'    => '2024-06-20 23:59:59',
					'inclusive' => true
				],
				'relation' => 'AND',
			],
			$new_query->get( 'date_query' )
		);
		$status = [
			Pending::class,
			Completed::class,
			Denied::class,
			Refunded::class,
			Voided::class,
		];

		foreach ( $status as $st ) {
			$args['post_status'] = tribe( $st )->get_wp_slug();

			$new_query = tribe( Hooks::class )->pre_filter_admin_order_table( $this->overwrite_global_wp_query( $args ) );

			$this->assertEquals( tribe( Status_Handler::class )->get_group_of_statuses_by_slug( '', $args['post_status'] ), $new_query->get( 'post_status' ) );
		}
	}

	/**
	 * Overwrite the global WP_Query.
	 *
	 * @param array $args The arguments to overwrite the query.
	 * @return WP_Query
	 */
	protected function overwrite_global_wp_query( $args ) {
		$overwrite_query = new WP_Query( $args );

		$this->set_global_value( 'wp_query', $overwrite_query );

		// Set it as main Query.
		$this->set_global_value( 'wp_the_query', $overwrite_query );

		return $overwrite_query;
	}
}
