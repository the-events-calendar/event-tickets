<?php
/**
 * Square Gateway Assets.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Common\Asset;
use TEC\Common\StellarWP\Assets\Assets as Stellar_Assets;
use TEC\Tickets\Commerce\Cart;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Assets Controller for the Square Gateway.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Assets extends Controller_Contract {
	/**
	 * The nonce action to use when requesting the creation of a new order
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const ORDER_NONCE_ACTION = 'tec_square_order';

	/**
	 * Checkout instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Checkout
	 */
	private Checkout $checkout;

	/**
	 * Gateway instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Gateway
	 */
	private Gateway $gateway;

	/**
	 * Order endpoint instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Order_Endpoint
	 */
	private Order_Endpoint $order_endpoint;

	/**
	 * Merchant instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Payment handler instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Payment_Handler
	 */
	private Payment_Handler $payment_handler;

	/**
	 * Assets constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Container       $container Container instance.
	 * @param Checkout        $checkout  Checkout instance.
	 * @param Gateway         $gateway   Gateway instance.
	 * @param Order_Endpoint  $order_endpoint Order endpoint instance.
	 * @param Merchant        $merchant Merchant instance.
	 * @param Payment_Handler $payment_handler Payment handler instance.
	 */
	public function __construct( Container $container, Checkout $checkout, Gateway $gateway, Order_Endpoint $order_endpoint, Merchant $merchant, Payment_Handler $payment_handler ) {
		parent::__construct( $container );
		$this->checkout        = $checkout;
		$this->gateway         = $gateway;
		$this->order_endpoint  = $order_endpoint;
		$this->merchant        = $merchant;
		$this->payment_handler = $payment_handler;
	}

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		Asset::add(
			'tec-tickets-commerce-gateway-square-admin-settings',
			'admin/gateway/square/settings.js',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plugin::class )
			->set_dependencies(
				'jquery',
				'wp-i18n',
			)
			->set_condition( [ $this, 'is_square_section' ] )
			->set_action( 'admin_enqueue_scripts' )
			->add_localize_script(
				'tec.tickets.commerce.square.localized',
				[
					'connectNonce' => wp_create_nonce( 'square-connect' ),
				]
			)
			->register();

		Asset::add(
			'tec-tickets-commerce-gateway-square-base',
			$this->gateway->get_square_js_url(),
			Tickets_Plugin::VERSION
		)->register();

		Asset::add(
			'tec-tickets-commerce-gateway-square-checkout',
			'commerce/gateway/square/checkout.js',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plugin::class )
			->set_dependencies(
				'jquery',
				'tribe-common',
				'tec-ky',
				'tribe-query-string',
				'tec-tickets-commerce-gateway-square-base',
				'tribe-tickets-loader',
				'tribe-tickets-commerce-js',
				'tribe-tickets-commerce-notice-js',
				'tribe-tickets-commerce-base-gateway-checkout-toggler'
			)
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->set_action( 'tec-tickets-commerce-checkout-shortcode-assets' )
			->set_as_module()
			->add_to_group( 'tec-tickets-commerce-gateway-square' )
			->add_localize_script( 'tec.tickets.commerce.square.checkout.data', [ $this, 'get_square_checkout_data' ] )
			->register();

		// Tickets Commerce Square main frontend styles.
		Asset::add(
			'tec-tickets-commerce-square-style',
			'tickets-commerce/gateway/square.css',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plugin::class )
			->set_dependencies(
				'tribe-common-skeleton-style',
				'tribe-common-full-style',
			)
			->add_to_group( 'tec-tickets-commerce-square' )
			->add_to_group( 'tribe-tickets-commerce' )
			->print()
			->register();

		Asset::add(
			'tec-tickets-commerce-gateway-square-admin-webhooks',
			'admin/gateway/square/webhooks.js',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plugin::class )
			->set_dependencies(
				'tribe-clipboard',
				'tribe-common',
				'tec-ky',
				'wp-i18n',
				'jquery'
			)
			->set_action( 'admin_enqueue_scripts' )
			->set_condition( [ $this, 'is_square_section' ] )
			->register();

		// Administration styles for Square gateway.
		Asset::add(
			'tec-tickets-commerce-admin-styles',
			'tickets-admin.css',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plugin::class )
			->set_action( 'admin_enqueue_scripts' )
			->set_condition( [ $this, 'is_square_section' ] )
			->register();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$stellar_assets = Stellar_Assets::init();

		foreach ( [
			'tec-tickets-commerce-square',
			'tec-tickets-commerce-gateway-square-base',
			'tec-tickets-commerce-gateway-square-checkout',
			'tec-tickets-commerce-square-style',
			'tec-tickets-commerce-gateway-square-admin-webhooks',
			'tec-tickets-commerce-admin-styles',
		] as $asset ) {
			$stellar_assets->remove( $asset );
		}
	}

	/**
	 * Get the Square checkout data for localization.
	 *
	 * @since 5.24.0
	 * @since 5.25.1.1 Add amount and user data to the checkout data.
	 *
	 * @return array
	 */
	public function get_square_checkout_data(): array {
		$card_style_options = [
			'style' => [
				'input' => [
					'color'           => '#23282d',
					'backgroundColor' => '#ffffff',
					'fontSize'        => '14px',
				],
			],
		];

		$user_logged_in = is_user_logged_in();
		$user_data      = [];

		if ( $user_logged_in ) {
			$user       = wp_get_current_user();
			$name       = $user->nice_name ?? $user->display_name;
			$name_parts = explode( ' ', $name );
			$first_name = $user->first_name ? $user->first_name : '';
			$first_name = $first_name ? $first_name : $name_parts[0] ?? '';
			$last_name  = $user->last_name ? $user->last_name : '';
			$last_name  = $last_name ? $last_name : $name_parts[1] ?? '';

			$user_country = $user->user_country ?? '';
			$user_country = $user_country && strlen( $user_country ) !== 2 ? $user_country : '';

			$user_data = array_filter(
				[
					'givenName'    => $first_name,
					'familyName'   => $last_name,
					'email'        => $user->user_email ?? '',
					'phone'        => $user->user_phone ?? '',
					'countryCode'  => $user_country,
					'addressLines' => [ $user->user_address ?? '' ],
					'state'        => $user->user_state ?? '',
					'city'         => $user->user_city ?? '',
					'postalCode'   => $user->user_postcode ?? '',
				]
			);
		}

		$data = [
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'currencyCode'      => $this->merchant->get_merchant_currency(),
			'amount'            => (string) ( tribe( Cart::class )->get_cart_total() ),
			'orderEndpoint'     => $this->order_endpoint->get_route_url(),
			'applicationId'     => $this->gateway->get_application_id(),
			'locationId'        => $this->merchant->get_location_id(),
			'squareCardOptions' => $card_style_options,
			'userLoggedIn'      => is_user_logged_in(),
			'userData'          => $user_data,
		];

		/**
		 * Filters the Square checkout data for localization.
		 *
		 * @since 5.24.0
		 *
		 * @param array $data The data to be localized.
		 *
		 * @return array
		 */
		return (array) apply_filters( 'tec_tickets_commerce_square_checkout_localized_data', $data );
	}

	/**
	 * Determines if we are currently on the Square section of the settings.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function is_square_section(): bool {
		return is_admin() && Gateway::get_key() === tribe_get_request_var( Payments_Tab::$key_current_section_get_var );
	}

	/**
	 * Define if the assets for `Square` should be enqueued or not.
	 *
	 * @since 5.24.0
	 *
	 * @return bool If the `Square` assets should be enqueued or not.
	 */
	public function should_enqueue_assets(): bool {
		return $this->checkout->is_current_page() && $this->gateway->is_enabled() && $this->gateway->is_active();
	}
}
