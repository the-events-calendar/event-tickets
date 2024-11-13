<?php
/**
 * Editor class.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Asset_Build;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Editor
 *
 * @since TBD
 */
class Editor extends Controller {

	use Asset_Build;

	/**
	 * ServiceProvider constructor.
	 *
	 * @param Container $container
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
		$this->register_block_editor_assets();
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
		Assets::instance()->remove( 'tec-tickets-order-modifiers-block-editor' );
	}

	/**
	 * Registers the block editor assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_block_editor_assets() {
		$this->add_asset(
			'tec-tickets-order-modifiers-block-editor',
			'block-editor.js',
		)
			->set_dependencies(
				'wp-hooks',
				'react',
				'react-dom',
				'tec-tickets-order-modifiers-rest-localization',
			)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->register();
	}
}
