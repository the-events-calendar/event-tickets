<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;

class BaseRestCest {

	/**
	 * @var string
	 */
	protected $rest_disable_option = 'et-rest-v1-disabled';
	/**
	 * @var string The site full URL to the homepage.
	 */
	protected $site_url;
	/**
	 * @var string
	 */
	protected $tec_option = 'tribe_events_calendar_options';

	/**
	 * @var string The site full URL to the REST API root.
	 */
	protected $rest_url;

	/**
	 * @var string
	 */
	protected $tickets_url;

	/**
	 * @var string
	 */
	protected $attendees_url;

	/**
	 * @var string
	 */
	protected $cart_url;

	/**
	 * @var string
	 */
	protected $documentation_url;

	/**
	 * @var \tad\WPBrowser\Module\WPLoader\FactoryStore
	 */
	protected $factory;

	/**
	 * @var string
	 */
	protected $wp_rest_url;

	/**
	 * @var string
	 */
	protected $tec_rest_url;

	public function _before( Restv1Tester $I ) {
		$this->site_url          = $I->grabSiteUrl();
		$this->wp_rest_url       = $this->site_url . '/wp-json/wp/v2/';
		$this->rest_url          = $this->site_url . '/wp-json/tribe/tickets/v1/';
		$this->tec_rest_url      = $this->site_url . '/wp-json/tribe/events/v1/';
		$this->tickets_url       = $this->rest_url . 'tickets';
		$this->attendees_url     = $this->rest_url . 'attendees';
		$this->cart_url          = $this->rest_url . 'cart';
		$this->documentation_url = $this->rest_url . 'doc';
		$this->factory           = $I->factory();

		/**
		 * Let's make sure Tribe Commerce is enabled and correctly configured.
		 */
		tribe_update_option( 'ticket-paypal-enable', 'yes' );
		tribe_update_option( 'ticket-paypal-email', 'merchant@example.com' );
		tribe_update_option( 'ticket-paypal-ipn-enabled', 'yes' );
		tribe_update_option( 'ticket-paypal-ipn-address-set', 'yes' );

		/**
		 * Let's make sure for sure.
		 */
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );


		tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'tribe_events' ] );

		tribe( 'tickets.commerce.paypal' )->pending_attendees_by_ticket = [];
		wp_cache_flush();

		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );
		$repository->reset_ticket_cache();

		// reset the user to visitor before each test
		wp_set_current_user( 0 );
	}

	/**
	 * Add item to Tribe Commerce cart.
	 *
	 * @param Restv1Tester $I          REST tester.
	 * @param int|array    $product_id Product to add to the cart or list of products/quantities.
	 * @param int          $quantity   Quantity of product to add to the cart.
	 * @param int          $post_id    Which post ID for the cart.
	 *
	 * @throws \Exception
	 */
	protected function paypal_add_item_to_cart( $I, $product_id, $quantity, $post_id ) {
		$cart_rest_url = $this->cart_url . "/{$post_id}";

		$tickets = [];

		if ( is_array( $product_id ) ) {
			foreach ( $product_id as $ticket_id => $ticket_quantity ) {
				$tickets[] = [
					'ticket_id' => $ticket_id,
					'quantity'  => $ticket_quantity,
				];
			}
		} else {
			$tickets[] = [
				'ticket_id' => $product_id,
				'quantity'  => $quantity,
			];
		}

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => $tickets,
		] );
	}
}