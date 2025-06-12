<?php
/**
 * The main Classy feature controller for Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Classy;
 */

declare( strict_types=1 );

namespace TEC\Tickets\Classy;

use TEC\Common\Classy\Controller as Common_Controller;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Asset;
use Tribe__Tickets__Main as ET;

/**
 * Class Controller
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		// Register the main assets entry point.
		if ( did_action( 'tec_common_assets_loaded' ) ) {
			$this->register_assets();
		} else {
			add_action( 'tec_common_assets_loaded', [ $this, 'register_assets' ] );
		}
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
		remove_action( 'tec_common_assets_loaded', [ $this, 'register_assets' ] );
	}

	/**
	 * Registers the assets required to extend the Classy application with TEC functionality.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_assets() {
		$post_uses_classy = fn() => $this
			->container
			->get( Common_Controller::class )
			->post_uses_classy( get_post_type() );

		Asset::add(
			'tec-classy-tickets',
			'classy.js'
		)->add_to_group_path( "{$this->get_et_class()}-packages" )
			// @todo this should be dynamic depending on the loading context.
			->enqueue_on( 'enqueue_block_editor_assets' )
			->set_condition( $post_uses_classy )
			->add_dependency( 'tec-classy' )
			->add_to_group( 'tec-classy' )
			->register();
	}

	/**
	 * Get the ET class name.
	 *
	 * @return class-string
	 */
	private function get_et_class(): string {
		return ET::class;
	}
}
