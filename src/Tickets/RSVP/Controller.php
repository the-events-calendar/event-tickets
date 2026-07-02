<?php
/**
 * Main RSVP Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Migrations\Enums\Status;
use TEC\Tickets\Commerce\Payments_Tab;
use function TEC\Common\StellarWP\Migrations\migrations;

/**
 * Main controller for RSVP functionality.
 *
 * This controller decides whether to register V1 (full functionality)
 * or RSVP_Disabled (null-object) based on configuration.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	/**
	 * Constant name for disabling RSVP.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_RSVP_DISABLED';

	/**
	 * Name for version 1 of the RSVP implementation.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const VERSION_1 = 'v1';

	/**
	 * Name for version 2 of the RSVP implementation.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const VERSION_2 = 'v2';

	/**
	 * Version 2 of the RSVP feature requires Tickets Commerce to be active.
	 *
	 * This method is called early, before the Tickets Commerce provider is registered, to allow the feature
	 * to try and activate Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public static function maybe_activate_tickets_commerce(): void {
		$version = self::get_version();

		if ( $version !== self::VERSION_2 ) {
			// Nothing to do: do not force activate Tickets Commerce.
			return;
		}

		// Try and activate Tickets Commerce.
		add_filter( 'tec_tickets_commerce_is_enabled', [ self::class, 'enable_tickets_commerce' ] );
	}

	/**
	 * Enables Tickets Commerce and ensures the checkout and success pages exist.
	 *
	 * @since TBD
	 *
	 * @return bool Always returns true to enable Tickets Commerce.
	 */
	public static function enable_tickets_commerce(): bool {
		// Defer page creation to `init` because wp_insert_post() requires $wp_rewrite to be initialized.
		if ( did_action( 'init' ) || doing_action( 'init' ) ) {
			self::maybe_create_tickets_commerce_pages();
		} else {
			add_action( 'init', [ self::class, 'maybe_create_tickets_commerce_pages' ] );
		}

		return true;
	}

	/**
	 * Creates the Tickets Commerce checkout and success pages if they don't exist.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public static function maybe_create_tickets_commerce_pages(): void {
		tribe( Payments_Tab::class )->maybe_auto_generate_checkout_page();
		tribe( Payments_Tab::class )->maybe_auto_generate_order_success_page();
	}

	/**
	 * Checks if RSVP functionality is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool Whether RSVP is enabled.
	 */
	public function is_rsvp_enabled(): bool {
		// Check constant.
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			return false;
		}

		// Check environment variable.
		if ( getenv( self::DISABLED ) ) {
			return false;
		}

		$is_active = self::get_version_from_migration_status() !== self::DISABLED;

		// Check option (developer-only, no UI).
		$active = (bool) get_option( 'tec_tickets_rsvp_active', $is_active );

		/**
		 * Filters whether RSVP functionality is enabled.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether RSVP is active.
		 */
		return (bool) apply_filters( 'tec_tickets_rsvp_enabled', $active );
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		if ( ! $this->is_rsvp_enabled() ) {
			$this->register_disabled();

			return;
		}

		$version = self::get_version();

		if ( $version === self::VERSION_1 ) {
			$this->container->register( V1\Controller::class );

			return;
		}

		// RSVP v2 requires Tickets Commerce to be activated to work.
		if ( $version === self::VERSION_2 && tec_tickets_commerce_is_enabled() ) {
			$this->container->register( V2\Controller::class );
			// V2 uses TC infrastructure. Bind repositories but not tickets.rsvp
			// as V2 doesn't need a legacy RSVP provider.
			$this->container->bind( 'tickets.ticket-repository.rsvp', V2\Repositories\Ticket_Repository::class );
			$this->container->bind( 'tickets.attendee-repository.rsvp', V2\Repositories\Attendee_Repository::class );

			return;
		}

		// If the version is not supported, fallback to disable the feature.
		$this->register_disabled();
	}

	/**
	 * Register null-object implementations for disabled RSVP.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_disabled(): void {
		// Register null-object implementations.
		$this->container->singleton( 'tickets.rsvp', RSVP_Disabled::class );

		// Register null-object repositories that return empty results.
		// Repositories must use bind(), not singleton(), to return a fresh instance on each call.
		$this->container->bind( 'tickets.ticket-repository.rsvp', Repositories\Ticket_Repository_Disabled::class );
		$this->container->bind( 'tickets.attendee-repository.rsvp', Repositories\Attendee_Repository_Disabled::class );

		// Tell the Block Editor that RSVP is disabled so the JS block is not registered.
		add_filter( 'tribe_editor_config', [ $this, 'add_rsvp_disabled_editor_config' ] );

		// Disable the RSVP form toggle in the Classic Editor metabox.
		add_filter( 'tec_tickets_enabled_ticket_forms', [ $this, 'disable_rsvp_form_toggle' ] );
	}

	/**
	 * Adds the RSVP disabled flag to the Block Editor configuration.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $config The editor configuration.
	 *
	 * @return array<string,mixed> The modified editor configuration.
	 */
	public function add_rsvp_disabled_editor_config( array $config ): array {
		$config['tickets']                   ??= [];
		$config['tickets']['rsvpDisabled']     = true;
		$config['tickets']['migrationsTabUrl'] = admin_url( 'admin.php?page=tec-tickets-settings&tab=migrations' );

		return $config;
	}

	/**
	 * Disables the RSVP form toggle in the Classic Editor metabox.
	 *
	 * @since TBD
	 *
	 * @param array<string,bool> $enabled The enabled ticket forms.
	 *
	 * @return array<string,bool> The modified enabled ticket forms.
	 */
	public function disable_rsvp_form_toggle( array $enabled ): array {
		$enabled['rsvp'] = false;

		return $enabled;
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		if ( ! $this->is_rsvp_enabled() ) {
			remove_filter( 'tribe_editor_config', [ $this, 'add_rsvp_disabled_editor_config' ] );
			remove_filter( 'tec_tickets_enabled_ticket_forms', [ $this, 'disable_rsvp_form_toggle' ] );

			return;
		}

		$version = self::get_version();

		if ( $version === self::VERSION_1 ) {
			$this->container->get( V1\Controller::class )->unregister();

			return;
		}

		// RSVP v2 requires Tickets Commerce to be activated to work.
		if ( $version === self::VERSION_2 && tec_tickets_commerce_is_enabled() ) {
			$this->container->get( V2\Controller::class )->unregister();
		}
	}

	/**
	 * Returns the filtered RSVP version to use.
	 *
	 * @since TBD
	 *
	 * @return string The filtered RSVP version to use.
	 */
	private static function get_version(): string {
		$version = self::get_version_from_migration_status();

		if ( $version === self::DISABLED ) {
			// This should never happen! RSVP are disabled before we reach this point.
			return self::VERSION_1;
		}

		/**
		 * Filters the RSVP version to register.
		 *
		 * If the provided version is not one of the supported versions, the feature will be disabled.
		 *
		 * @since TBD
		 *
		 * @param string $version The RSVP version to register.
		 */
		return (string) apply_filters( 'tec_tickets_rsvp_version', $version );
	}

	/**
	 * The option key used to store the RSVP version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const VERSION_OPTION_KEY = 'tickets_rsvp_version';

	/**
	 * Returns the RSVP version based on the migration status.
	 *
	 * The version is stored in a tribe option and updated by the migration's
	 * before/after hooks. On first load (no option set), live detection runs
	 * once from the migration status and saves the result.
	 *
	 * @since TBD
	 *
	 * @return string The RSVP version based on the migration status.
	 */
	private static function get_version_from_migration_status(): string {
		$version = tribe_get_option( self::VERSION_OPTION_KEY, null );

		if ( is_string( $version ) && in_array( $version, [ self::VERSION_1, self::VERSION_2, self::DISABLED ], true ) ) {
			return $version;
		}

		// First load: detect from migration status and persist.
		$version = self::detect_version_from_migration_status();
		tribe_update_option( self::VERSION_OPTION_KEY, $version );

		return $version;
	}

	/**
	 * Detects the RSVP version from the migration status.
	 *
	 * @since TBD
	 *
	 * @return string The detected RSVP version.
	 */
	private static function detect_version_from_migration_status(): string {
		$registry = migrations()->get_registry();

		$rsvp_to_tc = $registry->get( 'rsvp-to-tc' );

		if ( ! $rsvp_to_tc ) {
			// Assume that it has been completed and removed in the future.
			return self::VERSION_2;
		}

		$migration_status = $rsvp_to_tc->get_status();

		if ( Status::COMPLETED()->equals( $migration_status ) ) {
			return self::VERSION_2;
		} elseif ( Status::FAILED()->equals( $migration_status ) ) {
			return self::VERSION_1;
		} elseif ( Status::NOT_APPLICABLE()->equals( $migration_status ) ) {
			return self::VERSION_2;
		} elseif ( Status::PAUSED()->equals( $migration_status ) ) {
			return self::DISABLED;
		} elseif ( Status::PENDING()->equals( $migration_status ) ) {
			return self::VERSION_1;
		} elseif ( Status::RUNNING()->equals( $migration_status ) ) {
			return self::DISABLED;
		} elseif ( Status::SCHEDULED()->equals( $migration_status ) ) {
			return self::VERSION_1;
		} elseif ( Status::CANCELED()->equals( $migration_status ) ) {
			return self::VERSION_1;
		} elseif ( Status::REVERTED()->equals( $migration_status ) ) {
			return self::VERSION_1;
		}

		// Unknown status: determine version from the actual data state.
		return $rsvp_to_tc->is_applicable() ? self::VERSION_1 : self::VERSION_2;
	}
}
