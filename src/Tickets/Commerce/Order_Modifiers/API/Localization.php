<?php
/**
 * Localization for the Order Modifiers feature.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Common\StellarWP\Assets\Assets;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Localization
 *
 * @since TBD
 */
class Localization extends Controller {

	use Namespace_Trait;

	/**
	 * The Tickets plugin instance.
	 *
	 * @var Tickets
	 */
	protected Tickets $plugin;

	/**
	 * ServiceProvider constructor.
	 *
	 * @param Container $container The DI container.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );
		$this->plugin = Tickets::instance();
	}

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_assets();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since TBD
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		Assets::init()->remove( 'tec-tickets-order-modifiers-rest-localization' );
	}

	/**
	 * Register the assets for the Order Modifiers feature.
	 *
	 * @return void
	 */
	protected function register_assets() {
		Asset::add(
			'tec-tickets-order-modifiers-rest-localization',
			$this->get_built_asset_url( 'rest.js' ),
			Tickets::VERSION
		)
			->add_localize_script( 'tec.tickets.orderModifiers.rest', fn() => $this->get_rest_data() )
			->add_to_group( 'tec-tickets-order-modifiers' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->register();
	}

	/**
	 * Get the built asset URL for the Order Modifiers feature.
	 *
	 * @since TBD
	 *
	 * @param string $path The file path from the `/build/OrderModifiers` directory of the plugin.
	 */
	protected function get_built_asset_url( string $path ): string {
		$path = ltrim( $path, '/' );

		return "{$this->plugin->plugin_url}build/OrderModifiers/{$path}";
	}

	/**
	 * Get the REST data for the Order Modifiers feature.
	 *
	 * @since TBD
	 *
	 * @return array The REST data for the Order Modifiers feature.
	 */
	protected function get_rest_data(): array {
		return [
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'baseUrl' => rest_url( $this->namespace ),
		];
	}
}
