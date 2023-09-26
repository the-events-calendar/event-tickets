<?php

namespace TEC\Tickets\QR;

use \TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\QR
 */
class Controller extends Controller_Contract {
	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail if method belongs to the parent/abstract class.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Boot the Controller.
	 *
	 * This function is used to instantiate the singleton classes and register any other providers.
	 *
	 * @since   TBD
	 */
	public function boot() {
		$this->container->bind( Facade::class, [ $this, 'bind_facade_or_error' ] );
	}

	/**
	 * Register the controller.
	 *
	 * @since   TBD
	 */
	public function do_register(): void {
		$this->boot();

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since   TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
		$this->remove_filters();
	}

	/**
	 * Add the action hooks.
	 *
	 * @since   TBD
	 *
	 * @return void
	 */
	protected function add_actions(): void {

	}

	/**
	 * Add the action hooks.
	 *
	 * @since   TBD
	 *
	 * @return void
	 */
	protected function add_filters(): void {

	}

	/**
	 * Removes actions.
	 *
	 * @since   TBD
	 *
	 * @return void
	 */
	protected function remove_actions(): void {

	}

	/**
	 * Removes filters.
	 *
	 * @since   TBD
	 *
	 * @return void
	 */
	protected function remove_filters(): void {

	}

	/**
	 * Binds the facade or throws an error.
	 *
	 * @since TBD
	 *
	 * @return \WP_Error|Facade
	 */
	public function bind_facade_or_error() {
		if ( ! $this->can_use() ) {
			return new \WP_Error(
				'tec_tickets_qr_code_cannot_use',
				__( 'The QR code cannot be used, please contact your host and ask for `gzip` and `gd` support.', 'event-tickets' )
			);
		}

		// Load the library if it's not loaded already.
		$this->load_library();

		return new Facade;
	}

	/**
	 * Determines if the QR code library is loaded.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function has_library_loaded(): bool {
		return class_exists( 'QRencode', false ) || class_exists( 'QRcode', false );
	}

	/**
	 * Loads the QR code library if it's not loaded already.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function load_library(): void {
		if ( $this->has_library_loaded() ) {
			return;
		}

		require_once tribe( 'tickets.main' )->plugin_path . '/vendor/phpqrcode/qrlib.php';
	}

	/**
	 * Determines if the QR code can be used.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function can_use(): bool {
		$can_use = function_exists( 'gzuncompress' ) && function_exists( 'ImageCreate' );

		/**
		 * Filter to determine if the QR code can be used.
		 *
		 * @since TBD
		 *
		 * @param bool $can_use Whether the QR code can be used based on the current environment.
		 */
		return apply_filters( 'tec_tickets_qr_code_can_use', $can_use );
	}
}