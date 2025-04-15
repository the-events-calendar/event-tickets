<?php
/**
 * Square Gateway Assets.
 *
 * @since TBD
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
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Assets Controller for the Square Gateway.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Assets extends Controller_Contract {
	/**
	 * The nonce action to use when requesting the creation of a new order
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ORDER_NONCE_ACTION = 'tec_square_order';

	/**
	 * Checkout instance.
	 *
	 * @since TBD
	 *
	 * @var Checkout
	 */
	private Checkout $checkout;

	/**
	 * Gateway instance.
	 *
	 * @since TBD
	 *
	 * @var Gateway
	 */
	private Gateway $gateway;

	/**
	 * Order endpoint instance.
	 *
	 * @since TBD
	 *
	 * @var Order_Endpoint
	 */
	private Order_Endpoint $order_endpoint;

	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Payment handler instance.
	 *
	 * @since TBD
	 *
	 * @var Payment_Handler
	 */
	private Payment_Handler $payment_handler;

	/**
	 * Assets constructor.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		Asset::add(
			'tec-tickets-commerce-gateway-square-admin-settings',
			'admin/gateway/square/settings.js',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( 'et-core' )
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
			->add_to_group_path( 'et-core' )
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->set_action( 'tec-tickets-commerce-checkout-shortcode-assets' )
			->set_as_module()
			->add_to_group( 'tec-tickets-commerce-gateway-square' )
			->add_localize_script(
				'tec.tickets.commerce.square.checkout.data',
				fn() => (array) apply_filters(
					'tec_tickets_commerce_square_checkout_localized_data',
					[
						'nonce'             => wp_create_nonce( 'wp_rest' ),
						'orderEndpoint'     => $this->order_endpoint->get_route_url(),
						'applicationId'     => $this->gateway->get_application_id(),
						'locationId'        => $this->merchant->get_location_id(),
						'paymentData'       => $this->payment_handler->get_publishable_payment_data(),
						'squareCardOptions' => [
							'style' => [
								'input' => [
									'color'           => '#23282d',
									'backgroundColor' => '#fff',
									'fontSize'        => '14px',
								],
							],
						],
					]
				)
			)
			->register();

		// Tickets Commerce Square main frontend styles.
		Asset::add(
			'tec-tickets-commerce-square-style',
			'tickets-commerce/gateway/square.css',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( 'et-core' )
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
			->add_to_group_path( 'et-core' )
			->set_dependencies(
				'tribe-clipboard',
				'tribe-common',
				'tec-ky',
			)
			->set_action( 'admin_enqueue_scripts' )
			->set_condition( [ $this, 'is_square_section' ] )
			->register();

		// Administration styles for Square gateway.
		Asset::add(
			'tec-tickets-commerce-gateway-square-admin-webhooks-styles',
			'tickets-commerce/admin/gateway/square/webhooks.css',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( 'et-core' )
			->set_action( 'admin_enqueue_scripts' )
			->set_condition( [ $this, 'is_square_section' ] )
			->register();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
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
			'tec-tickets-commerce-gateway-square-admin-webhooks-styles',
		] as $asset ) {
			$stellar_assets->remove( $asset );
		}
	}

	/**
	 * Get the Square checkout data for localization.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_square_checkout_data() {
		$card_style_options =[
			'style' => [
				'input' => [
					'color'           => '#23282d',
					'backgroundColor' => '#ffffff',
					'fontSize'        => '14px',
				],
			],
		];

		$data = [
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'orderEndpoint'     => tribe( Order_Endpoint::class )->get_route_url(),
			'applicationId'     => tribe( Gateway::class )->get_application_id(),
			'locationId'        => tribe( Merchant::class )->get_location_id(),
			'paymentData'       => tribe( Payment_Handler::class )->get_publishable_payment_data(),
			'squareCardOptions' => $card_style_options,
		];

		/**
		 * Filters the Square checkout data for localization.
		 *
		 * @since TBD
		 *
		 * @param array $data The data to be localized.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_commerce_square_checkout_localized_data', $data );
	}

	/**
	 * Determines if we are currently on the Square section of the settings.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_square_section(): bool {
		return Gateway::get_key() === tribe_get_request_var( Payments_Tab::$key_current_section_get_var );
	}

	/**
	 * Define if the assets for `Square` should be enqueued or not.
	 *
	 * @since TBD
	 *
	 * @return bool If the `Square` assets should be enqueued or not.
	 */
	public function should_enqueue_assets(): bool {
		return $this->checkout->is_current_page() && $this->gateway->is_enabled() && $this->gateway->is_active();
	}
}
