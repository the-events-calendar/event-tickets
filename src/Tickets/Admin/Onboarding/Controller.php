<?php
/**
 * Controller for interfacing with TEC\Common\Onboarding.
 *
 * @since 5.23.0
 */

namespace TEC\Tickets\Admin\Onboarding;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Admin\Onboarding\API;
use TEC\Tickets\Admin\Onboarding\Steps\Welcome;
use TEC\Tickets\Admin\Onboarding\Steps\Settings;
use TEC\Tickets\Admin\Onboarding\Steps\Payments;
use TEC\Tickets\Admin\Onboarding\Steps\Communication;
use TEC\Tickets\Admin\Onboarding\Steps\Events;
use TEC\Tickets\Admin\Onboarding\Data;
use TEC\Tickets\Admin\Onboarding\Tickets_Landing_Page as Landing_Page;
use TEC\Common\StellarWP\Assets\Config as Asset_Config;

/**
 * Class Controller
 *
 * @since 5.23.0
 * @package TEC\Tickets\Admin\Onboarding
 */
class Controller extends Controller_Contract {
	/**
	 * The step instances.
	 *
	 * @since 5.23.0
	 *
	 * @var array
	 */
	protected $steps = [];

	/**
	 * Register the provider.
	 *
	 * @since 5.23.0
	 */
	public function do_register(): void {
		Asset_Config::add_group_path( 'tec-tickets-onboarding', tribe( 'tickets.main' )->plugin_path . 'build/', 'wizard' );

		$this->steps = [
			'welcome'       => new Welcome(),
			'settings'      => new Settings(),
			'payments'      => new Payments(),
			'communication' => new Communication(),
			'events'        => new Events(),
		];

		$this->add_filters();
		$this->add_actions();

		$this->container->singleton( Landing_Page::class );
		$this->container->singleton( Data::class );
		$this->container->singleton( API::class );

		$this->container->make( API::class )->set_data( $this->container->make( Data::class ) );
	}

	/**
	 * Whether the controller is active.
	 *
	 * @since 5.23.0
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		/**
		 * Filters whether the onboarding controller is active.
		 *
		 * @since 5.23.0
		 *
		 * @param bool $is_active Whether the controller is active.
		 */
		return apply_filters( 'tec_tickets_onboarding_is_active', true );
	}

	/**
	 * Unhooks actions and filters.
	 *
	 * @since 5.23.0
	 */
	public function unregister(): void {
		$this->remove_filters();
		$this->remove_actions();
	}

	/**
	 * Add the filter hooks.
	 *
	 * @since 5.23.0
	 */
	public function add_filters(): void {
		// Add the step handlers.
		add_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['welcome'], 'handle' ], 10, 2 );
		add_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['settings'], 'handle' ], 11, 2 );
		add_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['payments'], 'handle' ], 12, 2 );
		add_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['communication'], 'handle' ], 13, 2 );
		add_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['events'], 'handle' ], 14, 2 );
		add_filter( 'tec_telemetry_is_et_admin_page', [ $this, 'hide_telemetry_on_onboarding_page' ], 10, 1 );
	}

	/**
	 * Add the action hooks.
	 *
	 * @since 5.23.0
	 */
	public function add_actions(): void {
		add_action( 'admin_menu', [ $this, 'landing_page' ], 20 );
		add_action( 'admin_init', [ $this, 'enqueue_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
		add_action( 'admin_post_' . Landing_Page::DISMISS_PAGE_ACTION, [ $this, 'handle_onboarding_page_dismiss' ] );
		add_action( 'admin_notices', [ $this, 'remove_all_admin_notices_in_onboarding_page' ], -1 * PHP_INT_MAX );
		add_action( 'tec_admin_headers_about_to_be_sent', [ $this, 'redirect_tec_pages_to_guided_setup' ] );
	}

	/**
	 * Remove the filter hooks.
	 *
	 * @since 5.23.0
	 */
	public function remove_filters(): void {
		// Remove the step handlers.
		remove_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['welcome'], 'handle' ], 10 );
		remove_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['settings'], 'handle' ], 11 );
		remove_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['payments'], 'handle' ], 12 );
		remove_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['communication'], 'handle' ], 13 );
		remove_filter( 'tec_tickets_onboarding_wizard_handle', [ $this->steps['events'], 'handle' ], 14 );
		remove_filter( 'tec_telemetry_is_et_admin_page', [ $this, 'hide_telemetry_on_onboarding_page' ], 10 );
		remove_filter( 'tec_settings_page_logo_source', [ $this->container->make( Landing_Page::class ), 'logo_source' ] );
	}

	/**
	 * Remove the action hooks.
	 *
	 * @since 5.23.0
	 */
	public function remove_actions(): void {
		remove_action( 'admin_menu', [ $this, 'landing_page' ], 20 );
		remove_action( 'admin_init', [ $this, 'enqueue_assets' ] );
		remove_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
		remove_action( 'admin_post_' . Landing_Page::DISMISS_PAGE_ACTION, [ $this, 'handle_onboarding_page_dismiss' ] );
		remove_action( 'admin_notices', [ $this, 'remove_all_admin_notices_in_onboarding_page' ], -1 * PHP_INT_MAX );
		remove_action( 'tec_admin_headers_about_to_be_sent', [ $this, 'redirect_tec_pages_to_guided_setup' ] );
	}

	/**
	 * Handle the onboarding page dismiss.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function handle_onboarding_page_dismiss(): void {
		$this->container->make( Landing_Page::class )->handle_onboarding_page_dismiss();
	}

	/**
	 * Redirects users to the Guided Setup page when accessing any TEC settings or management page for the first time.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function redirect_tec_pages_to_guided_setup(): void {
		// Early bail if already on guided setup page.
		if ( Landing_Page::$slug === tec_get_request_var( 'page' ) ) {
			return;
		}

		/**
		 * Allow users to force-ignore the checks and redirect to the Guided Setup page.
		 *
		 * @since 5.23.0
		 *
		 * @param bool $force_redirect Whether to force the redirect to the Guided Setup page.
		 *
		 * @return bool
		 */
		$force_redirect = apply_filters( 'tec_tickets_onboarding_force_redirect_to_guided_setup', false );

		if ( $force_redirect ) {
			$this->do_redirect();
			return;
		}

		// Check transients first.
		$activation_redirect = get_transient( Landing_Page::ACTIVATION_REDIRECT_OPTION );
		$wizard_redirect     = get_transient( Landing_Page::BULK_ACTIVATION_REDIRECT_OPTION );

		if ( ! $activation_redirect && ! $wizard_redirect ) {
			return;
		}

		delete_transient( Landing_Page::ACTIVATION_REDIRECT_OPTION );
		delete_transient( Landing_Page::BULK_ACTIVATION_REDIRECT_OPTION );

		// If the wizard is completed, we don't need to redirect.
		if ( $this->container->get( Landing_Page::class )->is_tec_wizard_completed() ) {
			return;
		}

		if ( Landing_Page::is_dismissed() ) {
			return;
		}

		if ( count( (array) tribe_get_option( 'previous_event_tickets_versions', [] ) ) > 1 ) {
			return;
		}

		// For wizard redirect, verify we're on an ET admin page.
		if ( $wizard_redirect && ! $this->is_et_admin_page() ) {
			return;
		}

		$this->do_redirect();
	}

	/**
	 * Check if current page is an ET admin page.
	 *
	 * @since 5.23.0
	 *
	 * @return bool
	 */
	private function is_et_admin_page(): bool {
		$requested_page = tec_get_request_var( 'page', '' );
		$post_type      = tec_get_request_var( 'post_type', '' );
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Check main tec-tickets pages.
		if ( ! empty( $requested_page )
			&& ( strpos( $requested_page, 'tec-tickets' ) === 0 || $requested_page === 'tickets-setup' )
		) {
			return true;
		}

		// Check screen base.
		if ( null !== $current_screen && strpos( $current_screen->base, 'tec-tickets' ) === 0 ) {
			return true;
		}

		// Check ticket fieldsets page.
		return $post_type === 'ticket-meta-fieldset';
	}

	/**
	 * Handle the actual redirect
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	private function do_redirect(): void {
		$setup_url = add_query_arg(
			[ 'page' => Landing_Page::$slug ],
			admin_url( 'admin.php' )
		);

		// If we are about to redirect, delete our redirect transients - we don't need them any more.
		delete_transient( Landing_Page::ACTIVATION_REDIRECT_OPTION );
		delete_transient( Landing_Page::BULK_ACTIVATION_REDIRECT_OPTION );

		// phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit, StellarWP.CodeAnalysis.RedirectAndDie.Error
		wp_safe_redirect( $setup_url );
		tribe_exit();
	}

	/**
	 * Remove all admin notices in the onboarding page.
	 *
	 * @since 5.23.0
	 */
	public function remove_all_admin_notices_in_onboarding_page(): void {
		if ( ! Landing_Page::is_on_page() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
	}

	/**
	 * Settings page callback.
	 *
	 * @since 5.23.0
	 */
	public function landing_page() {
		$this->container->make( Landing_Page::class )->admin_page();
	}

	/**
	 * Enqueue scripts for the onboarding wizard.
	 *
	 * @since 5.23.0
	 */
	public function enqueue_assets(): void {
		$this->container->make( Landing_Page::class )->register_assets();
	}

	/**
	 * Registers the REST endpoints that will be used to return the Views HTML.
	 *
	 * @since 5.23.0
	 */
	public function register_rest_endpoints(): void {
		$this->container->make( API::class )->register();
	}

	/**
	 * Hide telemetry on the onboarding page by returning false when the page is detected.
	 *
	 * @since 5.23.0
	 *
	 * @param bool $is_et_admin_page Whether the current page is an ET admin page.
	 *
	 * @return bool
	 */
	public function hide_telemetry_on_onboarding_page( $is_et_admin_page ): bool {
		if ( Landing_Page::is_on_page() ) {
			return false;
		}

		return $is_et_admin_page;
	}
}
