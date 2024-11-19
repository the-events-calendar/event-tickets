<?php
/**
 * Localization for the Order Modifiers feature.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\Contracts\Container;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Asset_Build;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Localization
 *
 * @since TBD
 */
class Localization extends Controller {

	use Asset_Build;
	use Namespace_Trait;

	/**
	 * ServiceProvider constructor.
	 *
	 * @since TBD
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

		/*
		 * This is a workaround to avoid the following error:
		 *
		 * ReferenceError: Can't find variable: tec
		 *
		 * This error is caused by webpack trying to map the tec.tickets.orderModifiers.rest object
		 * before is has been initialized in the browser. It's not yet known why this is happening.
		 *
		 * @todo: remove this workaround once the issue is fixed.
		 */
		add_action(
			'admin_enqueue_scripts',
			function () {
				?>
				<script type="text/javascript">
					(function () {
						window.tec = window.tec || {};
						window.tec.tickets = window.tec.tickets || {};
						window.tec.tickets.orderModifiers = window.tec.tickets.orderModifiers || {};
						window.tec.tickets.orderModifiers.rest = window.tec.tickets.orderModifiers.rest || {};
					})();
				</script>
				<?php
			}
		);
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
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_assets() {
		$this->add_asset(
			'tec-tickets-order-modifiers-rest-localization',
			'rest.js',
		)
			->add_localize_script( 'tec.tickets.orderModifiers.rest', fn() => $this->get_rest_data() )
			->register();
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
