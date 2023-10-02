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
	 * @return bool Whether the controller is active or not.
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Register the controller.
	 *
	 * @since   TBD
	 */
	public function do_register(): void {
		$this->container->bind( QR::class, [ $this, 'bind_facade_or_error' ] );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since   TBD
	 *
	 * @return void
	 */
	public function unregister(): void {

	}

	/**
	 * Binds the facade or throws an error.
	 *
	 * @since TBD
	 *
	 * @return \WP_Error|QR Either the build QR façade, or an error to detail the failure.
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

		return new QR;
	}

	/**
	 * Determines if the QR code library is loaded.
	 *
	 * @since TBD
	 */
	protected function has_library_loaded(): bool {
		return defined( 'TEC_TICKETS_QR_CACHEABLE' );
	}

	/**
	 * Loads the QR code library if it's not loaded already.
	 *
	 * @since TBD
	 */
	protected function load_library(): void {
		if ( $this->has_library_loaded() ) {
			return;
		}

		require_once tribe( 'tickets.main' )->plugin_path . 'vendor/phpqrcode/qrlib.php';
	}

	/**
	 * Determines if the QR code can be used.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the current server configuration supports the QR functionality.
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