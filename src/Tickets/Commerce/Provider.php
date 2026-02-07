<?php
/**
 * The main service provider for the Tickets Commerce.
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Cart\Agnostic_Cart;
use TEC\Tickets\Commerce\Cart\Cart_Interface;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Service provider for the Tickets Commerce.
 *
 * @since 5.1.6
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
		$this->container->register( Payments_Tab::class );

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
		// We need to init for the registration as a module to take place early.
		$this->container->get( Module::class );

		$this->container->singleton( Attendee::class );
		$this->container->singleton( Order::class );
		$this->container->singleton( Ticket::class );
		$this->container->singleton( Cart::class );
		$this->container->singleton( Cart\Unmanaged_Cart::class );
		$this->container->singleton( Cart_Interface::class, Agnostic_Cart::class );
		$this->container->singleton( Stock_Validator::class );

		$this->container->singleton( Checkout::class );
		$this->container->singleton( Settings::class );
		$this->container->singleton( Tickets_View::class );
		$this->container->singleton( Promoter_Observer::class, new Promoter_Observer );

		$this->container->register( Status\Status_Handler::class );
		$this->container->register( Flag_Actions\Flag_Action_Handler::class );

		// Register Compatibility Classes.
		$this->container->singleton( Compatibility\Events::class );

		// Load any external SPs we might need.
		$this->container->register( Gateways\Square\Controller::class );
		$this->container->register( Gateways\Stripe\Provider::class );
		$this->container->register( Gateways\PayPal\Provider::class );
		$this->container->register( Gateways\Manual\Provider::class );
		$this->container->register( Gateways\Free\Provider::class );

		// Register and add hooks for admin notices.
		$this->container->register( Admin\Notices::class );

		$this->container->register( Admin\Singular_Order_Page::class );

		// Register Order modifiers main controller.
		$this->container->register( Order_Modifiers\Controller::class );

		// Commerce Tables Controller.
		$this->container->register( Tables::class );

		$this->container->register_on_action(
			'tec_events_pro_custom_tables_v1_fully_activated',
			Custom_Tables\V1\Provider::class
		);

		// Cache invalidation.
		add_filter( 'tec_cache_listener_save_post_types', [ $this, 'filter_cache_listener_save_post_types' ] );

		// Since currently shepherd is only used with ET's TicketsCommerce, we re-enable the cleanup task here.
		add_action(
			'wp_loaded',
			function () {
				remove_filter( 'shepherd_tec_schedule_cleanup_task_every', '__return_zero' );
			},
			15
		);
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

		// Allow Hooks to be removed, by having the them registered to the container.
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
