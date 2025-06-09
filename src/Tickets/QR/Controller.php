<?php

namespace TEC\Tickets\QR;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

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
	 * @return void
	 */
	public function do_register(): void {
		$this->container->singleton( Settings::class );
		$this->container->singleton( Connector::class );
		$this->container->singleton( Observer::class );

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

		add_filter( 'tec_qr_notice_valid_pages', [ $this, 'add_valid_pages' ] );
		add_filter( 'tec_qr_notice_valid_post_types', [ $this, 'add_post_types' ] );
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

		remove_filter( 'tec_qr_notice_valid_pages', [ $this, 'add_valid_pages' ] );
		remove_filter( 'tec_qr_notice_valid_post_types', [ $this, 'add_valid_post_types' ] );
	}

	/**
	 * Adds the ET pages to the list for the QR code notice.
	 *
	 * @since 5.22.0
	 *
	 * @param array $valid_pages An array of pages where notice will be displayed.
	 *
	 * @return array
	 */
	public function add_valid_pages( $valid_pages ) {
		$et_pages = [
			'tickets-attendees',
			'tickets-commerce-orders',
			'edd-orders',
			'tickets-orders',
			'tec-tickets',
			'tec-tickets-help',
			'tec-tickets-troubleshooting',
			'tec-tickets-settings',
		];

		return array_merge( $valid_pages, $et_pages );
	}

	/**
	 * Adds the post types to the list for the QR code notice.
	 *
	 * @since 5.22.0
	 *
	 * @param array $valid_post_types An array of post types to display the notice.
	 *
	 * @return array
	 */
	public function add_valid_post_types( $valid_post_types ) {
		return array_merge( $valid_post_types, [ 'ticket-meta-fieldset' ] );
	}

	/**
	 * Register the assets related to the QR module.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	protected function register_assets(): void {
		tec_asset(
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
}
