<?php
/**
 * Controller for interfacing with TEC\Common\Onboarding.
 *
 * @since TBD
 */

namespace TEC\Tickets\Admin\Onboarding;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Admin\Onboarding\API;
use TEC\Tickets\Admin\Onboarding\Steps\Optin;
use TEC\Tickets\Admin\Onboarding\Steps\Settings;
use TEC\Tickets\Admin\Onboarding\Data;
use TEC\Tickets\Admin\Onboarding\Tickets_Landing_Page as Landing_Page;
use TEC\Common\StellarWP\Assets\Config as Asset_Config;

/**
 * Class Controller
 *
 * @since TBD
 * @package TEC\Tickets\Admin\Onboarding
 */
class Controller extends Controller_Contract {

	/**
	 * Register the provider.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		Asset_Config::add_group_path( 'tec-tickets-onboarding', tribe( 'tickets.main' )->plugin_path, 'build' );

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
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return apply_filters( 'tec_tickets_onboarding_is_active', true );
	}

	/**
	 * Unhooks actions and filters.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		$this->remove_filters();
		$this->remove_actions();
	}

	/**
	 * Add the filter hooks.
	 *
	 * @since TBD
	 */
	public function add_filters(): void {
		// Add the step handlers.
		add_filter( 'tec_tickets_onboarding_wizard_handle', [ Optin::class, 'handle' ], 10, 2 );
		add_filter( 'tec_tickets_onboarding_wizard_handle', [ Settings::class, 'handle' ], 11, 2 );
	}

	/**
	 * Add the action hooks.
	 *
	 * @since TBD
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
	 * @since TBD
	 */
	public function remove_filters(): void {
		// Remove the step handlers.
		remove_filter( 'tec_tickets_onboarding_wizard_handle', [ Optin::class, 'handle' ], 10 );
		remove_filter( 'tec_tickets_onboarding_wizard_handle', [ Settings::class, 'handle' ], 11 );
	}

	/**
	 * Remove the action hooks.
	 *
	 * @since TBD
	 */
	public function remove_actions(): void {
		remove_action( 'admin_menu', [ $this, 'landing_page' ], 20 );
		remove_action( 'admin_init', [ $this, 'enqueue_scripts' ] );
		remove_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
		remove_action( 'admin_notices', [ $this, 'remove_all_admin_notices_in_onboarding_page' ], -1 * PHP_INT_MAX );
		remove_action( 'tec_admin_headers_about_to_be_sent', [ $this, 'redirect_tec_pages_to_guided_setup' ] );
	}

	/**
	 * Handle the onboarding page dismiss.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function handle_onboarding_page_dismiss(): void {
		$this->container->make( Landing_Page::class )->handle_onboarding_page_dismiss();
	}

	/**
	 * Redirects users to the Guided Setup page when accessing any TEC settings or management page for the first time.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function redirect_tec_pages_to_guided_setup(): void {
		// Do not redirect if they are already on the Guided Setup page. Also prevents an infinite loop if $force is true.
		if ( Landing_Page::$slug === tec_get_request_var( 'page' ) ) {
			return;
		}

		/**
		 * Allow users to force-ignore the checks and redirect to the Guided Setup page.
		 * Note this will potentially redirect ALL admin requests - so use sparingly!
		 *
		 * @since TBD
		 *
		 * @param bool $force Whether to force the redirect to the Guided Setup page.
		 *
		 * @return bool
		 */
		$force = apply_filters( 'tec_tickets_onboarding_force_redirect_to_guided_setup', false );

		if ( ! $force ) {
			// For single plugin activation, check the activation redirect transient.
			$activation_redirect = get_transient( Landing_Page::ACTIVATION_REDIRECT_OPTION );

			// For bulk activation, only redirect if they're on an ET admin page and have the wizard redirect transient.
			$wizard_redirect = get_transient( Landing_Page::BULK_ACTIVATION_REDIRECT_OPTION );

			// If neither transient is set, don't redirect.
			if ( ! $activation_redirect && ! $wizard_redirect ) {
				return;
			}

			// If it's a bulk activation (wizard redirect), only proceed if we're on an ET admin page.
			if ( ! $activation_redirect && $wizard_redirect ) {
				$requested_page = tec_get_request_var( 'page', '' );
				$post_type      = tec_get_request_var( 'post_type', '' );
				$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

				// Check if we're on any ET admin page.
				$is_et_page = false;

				// Check main tec-tickets pages.
				if (
					(
						! empty( $requested_page )
						&& (
							strpos( $requested_page, 'tec-tickets' ) === 0
							|| $requested_page === 'tickets-setup'
						)
					)
					|| (
						null !== $current_screen
						&& strpos( $current_screen->base, 'tec-tickets' ) === 0
					)
				) {
					$is_et_page = true;
				} elseif ( $post_type === 'ticket-meta-fieldset' ) {
					// Check if we're on the ticket fieldsets page.
					$is_et_page = true;
				}

				if ( ! $is_et_page ) {
					return;
				}
			}

			// Do not redirect if they have been to the Guided Setup page already.
			if ( (bool) tribe_get_option( Landing_Page::VISITED_GUIDED_SETUP_OPTION, false ) ) {
				return;
			}

			// Do not redirect if they dismissed the Guided Setup page.
			if ( Landing_Page::is_dismissed() ) {
				return;
			}

			// Do not redirect if they have older versions and are probably already set up.
			$versions = (array) tribe_get_option( 'previous_event_tickets_versions', [] );
			if ( count( $versions ) > 1 ) {
				return;
			}
		}

		// If we're still here, redirect to the Guided Setup page.
		$setup_url = add_query_arg(
			[
				'page' => Landing_Page::$slug,
			],
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
	 * @since TBD
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
	 * @since TBD
	 */
	public function landing_page() {
		$this->container->make( Landing_Page::class )->admin_page();
	}

	/**
	 * Enqueue scripts for the onboarding wizard.
	 *
	 * @since TBD
	 */
	public function enqueue_assets(): void {
		$this->container->make( Landing_Page::class )->register_assets();
	}

	/**
	 * Registers the REST endpoints that will be used to return the Views HTML.
	 *
	 * @since TBD
	 */
	public function register_rest_endpoints(): void {
		$this->container->make( API::class )->register();
	}
}
