<?php

namespace TEC\Tickets\QR;

use \TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller.
 *
 * @since 5.6.7
 *
 * @package TEC\Tickets\QR
 */
class Controller extends Controller_Contract {
	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail if method belongs to the parent/abstract class.
	 *
	 * @since 5.6.7
	 *
	 * @return bool Whether the controller is active or not.
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Register the controller.
	 *
	 * @since 5.6.7
	 *
	 * @uses  Notices::register_admin_notices()
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->bind( QR::class, [ $this, 'bind_facade_or_error' ] );
		$this->container->singleton( Settings::class );
		$this->container->singleton( Notices::class );
		$this->container->singleton( Connector::class );
		$this->container->singleton( Observer::class );

		// Register the Admin Notices right away.
		$this->container->make( Notices::class )->register_admin_notices();

		$this->add_actions();

		$this->register_assets();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 5.6.
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
	}

	/**
	 * Adds the actions required by the controller.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	protected function add_actions(): void {
		$connector_ajax_action = tribe( Connector::class )->get_ajax_action_key();
		add_action( "wp_ajax_{$connector_ajax_action}", [ $this, 'handle_ajax_generate_api_key' ] );
		add_action( 'admin_notices', [ $this, 'legacy_handler_admin_notice' ], 10 );
		add_action( 'template_redirect', [ $this, 'handle_checkin_redirect' ], 10 );
	}

	/**
	 * Removes the actions required by the controller.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	protected function remove_actions(): void {
		$connector_ajax_action = tribe( Connector::class )->get_ajax_action_key();
		remove_action( "wp_ajax_{$connector_ajax_action}", [ $this, 'handle_ajax_generate_api_key' ] );
		remove_action( 'admin_notices', [ $this, 'legacy_handler_admin_notice' ], 10 );
		remove_action( 'template_redirect', [ $this, 'handle_checkin_redirect' ], 10 );
	}

	/**
	 * Register the assets related to the QR module.
	 *
	 * @since 5.7.0
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
	 * Handles the checkin redirection.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function handle_checkin_redirect(): void {
		tribe( Observer::class )->handle_checkin_redirect();
	}

	/**
	 * Handles the admin notice in the legacy way. Needs to be deprecated at some point.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function legacy_handler_admin_notice(): void {
		tribe( Observer::class )->legacy_handler_admin_notice();
	}

	/**
	 * Handles the AJAX request to generate the API key.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function handle_ajax_generate_api_key(): void {
		tribe( Connector::class )->handle_ajax_generate_api_key();
	}

	/**
	 * Binds the facade or throws an error.
	 *
	 * @since 5.6.7
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
	 * @since 5.6.7
	 */
	public function has_library_loaded(): bool {
		return defined( 'TEC_TICKETS_QR_CACHEABLE' );
	}

	/**
	 * Loads the QR code library if it's not loaded already.
	 *
	 * @since 5.6.7
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
	 * @since 5.6.7
	 *
	 * @return bool Whether the current server configuration supports the QR functionality.
	 */
	public function can_use(): bool {
		$can_use = function_exists( 'gzuncompress' ) && function_exists( 'ImageCreate' );

		/**
		 * Filter to determine if the QR code can be used.
		 *
		 * @since 5.6.7
		 *
		 * @param bool $can_use Whether the QR code can be used based on the current environment.
		 */
		return apply_filters( 'tec_tickets_qr_code_can_use', $can_use );
	}
}
