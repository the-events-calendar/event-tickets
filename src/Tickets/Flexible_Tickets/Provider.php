<?php
/**
 * The main provider for the Recurring Ticket feature.
 *
 * The whole feature is behind the `` constant.
 *
 * Setting `define( '', false )` in the site wp-config.php file will disable the feature.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Recurring_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use tad_DI52_ServiceProvider as ServiceProvider;

/**
 * Class Provider.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Provider extends ServiceProvider {
	/**
	 * The name of the constant that will be used to disable the feature.
	 * Setting it to a truthy value will disable the feature.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_FLEXIBLE_TICKETS_DISABLED';

	/**
	 * Whether the provider did register or not.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $did_register = false;

	/**
	 * Registers the bindings, service providers and controllers part of the feature.
	 *
	 * @return void The bindings, service providers and controllers are registered in the container.
	 * @since TBD
	 *
	 */
	public function register() {
		if ( $this->did_register ) {
			return;
		}

		$this->did_register = true;

		// Whether the feature is enabled or not, allow fetching this provider.
		$this->container->singleton( self::class, self::class );

		if ( ! $this->is_enabled() ) {
			return;
		}

		do_action( 'tribe_log', 'debug', 'TEC Flexible Tickets activated.' );

		/**
		 * Fires when the TEC Flexible Tickets feature is activated.
		 *
		 * @since TBD
		 */
		do_action( 'tec_flexible_tickets_activated' );

		$this->container->register( Custom_Tables::class );
	}

	/**
	 * Determines if the feature is enabled or not.
	 *
	 * The method will check if the feature has been disabled via a constant, an environment variable,
	 * an option or a filter.
	 *
	 * @return bool Whether the feature is enabled or not.
	 * @since TBD
	 *
	 */
	private function is_enabled(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			// The constant to disable the feature is defined and it's truthy.
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			// The environment variable to disable the feature is truthy.
			return false;
		}

		// Finally read an option value to determine if the feature should be active or not.
		$active = (bool) get_option( 'tec_recurring_tickets_active', true );

		/**
		 * Allows filtering whether the whole Recurring Tickets feature
		 * should be activated or not.
		 *
		 * Note: this filter will only apply if the disable constant or env var
		 * are not set or are set to falsy values.
		 *
		 * @param bool $activate Defaults to `true`.
		 *
		 * @since TBD
		 *
		 */
		return (bool) apply_filters( 'tec_recurring_tickets_enabled', $active );
	}
}
