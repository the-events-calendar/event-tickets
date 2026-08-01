<?php
/**
 * Minimal Event Tickets Plus WooCommerce Orders tab stub used to exercise the
 * `Tabbed_View::woo_orders_tab_is_registered()` `instanceof` branch in tests
 * without loading Event Tickets Plus.
 *
 * @package TEC\Tickets\Tests\Support\Stubs
 */

if ( ! class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab', false ) ) {
	/**
	 * Minimal WooCommerce Orders tab stub.
	 */
	class Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab extends Tribe__Tabbed_View__Tab {
		/**
		 * @var string
		 */
		protected $slug = 'tribe-tickets-plus-woocommerce-orders-report';

		/**
		 * @return string
		 */
		public function get_slug() {
			return $this->slug;
		}

		/**
		 * @return string
		 */
		public function get_label() {
			return 'Orders';
		}
	}
}
