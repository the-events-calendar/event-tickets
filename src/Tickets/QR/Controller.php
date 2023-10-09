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
	 * @since TBD
	 *
	 * @uses  Notices::register_admin_notices()
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->bind( QR::class, [ $this, 'bind_facade_or_error' ] );
		$this->container->singleton( Settings::class, Settings::class );
		$this->container->singleton( Notices::class, Notices::class );
		$this->container->singleton( Connector::class, Connector::class );
		$this->container->singleton( Observer::class, Observer::class );

		// Register the Admin Notices right away.
		$this->container->make( Notices::class )->register_admin_notices();

		$this->add_actions();
		$this->add_filters();

		$this->register_assets();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
		$this->remove_filters();
	}

	/**
	 * Adds the actions required by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function add_actions(): void {
		$connector_ajax_action = tribe( Connector::class )->get_ajax_action_key();
		add_action( "wp_ajax_{$connector_ajax_action}", [ tribe( Connector::class ), 'handle_ajax_generate_api_hash' ] );
	}

	/**
	 * Removes the actions required by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function remove_actions(): void {
		$connector_ajax_action = tribe( Connector::class )->get_ajax_action_key();
		remove_action( "wp_ajax_{$connector_ajax_action}", [ tribe( Connector::class ), 'handle_ajax_generate_api_hash' ] );
	}

	/**
	 * Adds the filters required by the QR controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function add_filters(): void {
		$observer = tribe( Observer::class );
		add_filter( 'template_redirect', [ $observer, 'handle_checkin_redirect' ], 10 );
		add_filter( 'admin_notices', [ $observer, 'legacy_handler_admin_notice' ], 10 );
	}

	/**
	 * Removes the filters required by the QR controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function remove_filters(): void {
		$observer = tribe( Observer::class );
		remove_filter( 'template_redirect', [ $observer, 'handle_checkin_redirect' ], 10 );
		remove_filter( 'admin_notices', [ $observer, 'legacy_handler_admin_notice' ], 10 );
	}

	/**
	 * Register the assets related to the QR module.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_assets(): void {
		tribe_asset(
			\Tribe__Tickets__Main::instance(),
			'tec-tickets-qr-connector',
			'qr-connector.js',
			[ 'jquery', 'tribe-common' ],
			null,
			[
				'priority' => 0,
			]
		);
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
	public function has_library_loaded(): bool {
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