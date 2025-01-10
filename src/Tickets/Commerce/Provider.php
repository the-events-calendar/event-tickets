<?php
/**
 * The main service provider for the Tickets Commerce.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Gateways;
use Tribe__Tickets__Main as Tickets_Plugin;
use WP_Post;
use Exception;
use TEC\Tickets\Commerce\Status\Status_Handler;

/**
 * Service provider for the Tickets Commerce.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce
 */
class Provider extends Service_Provider {
	/**
	 * A reference to the Hooks object.
	 *
	 * @since 5.16.0
	 *
	 * @var Hooks|null
	 */
	private ?Hooks $hooks;

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		// Specifically prevents anything else from loading.
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return;
		}

		$this->register_assets();
		$this->register_hooks();
		$this->load_functions();
		$this->register_legacy_compat();

		// Register the SP on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets.commerce.provider', $this );

		// Register all singleton classes.
		$this->container->singleton( Gateways\Manager::class, Gateways\Manager::class );

		$this->container->singleton( Reports\Attendance_Totals::class );
		$this->container->singleton( Reports\Attendees::class );
		$this->container->singleton( Reports\Orders::class );
		$this->container->singleton( Admin_Tables\Orders::class );
		$this->container->singleton( Admin_Tables\Attendees::class );

		$this->container->singleton( Editor\Metabox::class );
		$this->container->singleton( Notice_Handler::class );

		$this->container->singleton( Module::class );
		$this->container->singleton( Attendee::class );
		$this->container->singleton( Order::class );
		$this->container->singleton( Ticket::class );
		$this->container->singleton( Cart::class );
		$this->container->singleton( Cart\Unmanaged_Cart::class );

		$this->container->singleton( Checkout::class );
		$this->container->singleton( Settings::class );
		$this->container->singleton( Tickets_View::class );
		$this->container->singleton( Promoter_Observer::class, new Promoter_Observer );

		$this->container->register( Status\Status_Handler::class );
		$this->container->register( Flag_Actions\Flag_Action_Handler::class );

		// Register Compatibility Classes
		$this->container->singleton( Compatibility\Events::class );

		// Load any external SPs we might need.
		$this->container->register( Gateways\Stripe\Provider::class );
		$this->container->register( Gateways\PayPal\Provider::class );
		$this->container->register( Gateways\Manual\Provider::class );
		$this->container->register( Gateways\Free\Provider::class );

		// Register and add hooks for admin notices.
		$this->container->register( Admin\Notices::class );

		$this->container->register( Admin\Singular_Order_Page::class );

		// Register Order modifiers main controller.
		$this->container->register( Order_Modifiers\Controller::class );

		$this->container->register_on_action(
			'tec_events_pro_custom_tables_v1_fully_activated',
			Custom_Tables\V1\Provider::class
		);

		// Cache invalidation.
		add_filter( 'tec_cache_listener_save_post_types', [ $this, 'filter_cache_listener_save_post_types' ] );

		add_action( 'tec_tickets_commerce_async_webhook_process', [ $this, 'process_async_stripe_webhook' ], 10 );
	}

	/**
	 * Process the async stripe webhook.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @throws Exception If the action fails after too many retries.
	 */
	public function process_async_stripe_webhook( int $order_id ): void {
		$order = tec_tc_get_order( $order_id );

		if ( ! $order || ! $order instanceof WP_Post || ! $order->ID ) {
			return;
		}

		$pending_webhooks = get_post_meta( $order->ID, '_tec_tickets_commerce_stripe_webhook_pending' );

		// On multiple checkout completes, make sure we dont process the same webhook twice.
		delete_post_meta( $order->ID, '_tec_tickets_commerce_stripe_webhook_pending' );

		foreach ( $pending_webhooks as $pending_webhook ) {
			if ( ! ( is_array( $pending_webhook ) && isset( $pending_webhook['new_status'], $pending_webhook['metadata'], $pending_webhook['old_status'] ) ) ) {
				continue;
			}

			$new_status_wp_slug = $pending_webhook['new_status'];

			// The order is already there!
			if ( $order->post_status === $new_status_wp_slug ) {
				continue;
			}

			// The order is no longer where it was... that could be dangerous, lets bail?
			if ( $order->post_status !== $pending_webhook['old_status'] ) {
				continue;
			}

			tribe( Order::class )->modify_status(
				$order->ID,
				tribe( Status_Handler::class )->get_by_wp_slug( $new_status_wp_slug )->get_slug(),
				$pending_webhook['metadata']
			);
		}
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Commerce.
	 *
	 * @since 5.1.6
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Include All function files.
	 *
	 * @since 5.1.9
	 */
	protected function load_functions() {
		$path = Tickets_Plugin::instance()->plugin_path;

		require_once $path . 'src/functions/commerce/orm.php';
		require_once $path . 'src/functions/commerce/orders.php';
		require_once $path . 'src/functions/commerce/attendees.php';
		require_once $path . 'src/functions/commerce/tickets.php';
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Commerce.
	 *
	 * @since 5.1.6
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$this->hooks = $hooks;
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets.commerce.hooks', $hooks );
	}

	/**
	 * Registers the provider handling compatibility with legacy payments from Tribe Tickets Commerce.
	 *
	 * @since 5.1.6
	 */
	protected function register_legacy_compat() {
		$v1_compat = new Legacy_Compat( $this->container );
		$v1_compat->register();

		$this->container->singleton( Legacy_Compat::class, $v1_compat );
		$this->container->singleton( 'tickets.commerce.legacy-compat', $v1_compat );
	}

	/**
	 * Filters the list of post types that should trigger a cache invalidation on `save_post` to add
	 * all the ones modeling Commerce Tickets, Attendees and Orders.
	 *
	 * @since 5.6.7
	 *
	 * @param string[] $post_types The list of post types that should trigger a cache invalidation on `save_post`.
	 *
	 * @return string[] The filtered list of post types that should trigger a cache invalidation on `save_post`.
	 */
	public function filter_cache_listener_save_post_types( array $post_types = [] ): array {
		$post_types[] = Ticket::POSTTYPE;
		$post_types[] = Attendee::POSTTYPE;
		$post_types[] = Order::POSTTYPE;

		return $post_types;
	}

	/**
	 * Runs the init hooks managed by the Hooks object.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function run_init_hooks(): void {
		if ( $this->hooks === null ) {
			return;
		}

		$this->hooks->run_init_hooks();
	}
}
