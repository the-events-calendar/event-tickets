<?php
/**
 * The main provider for the Recurring Ticket feature.
 *
 * The whole feature is behind the `` constant.
 *
 * Setting `define( '', false )` in the site wp-config.php file will disable the feature.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Recurring_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\lucatume\DI52\ServiceProvider;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;

/**
 * Class Provider.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Provider extends ServiceProvider {
	/**
	 * The action that will be dispatched when the provider is registered.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_flexible_tickets_registered';

	/**
	 * The name of the constant that will be used to disable the feature.
	 * Setting it to a truthy value will disable the feature.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_FLEXIBLE_TICKETS_DISABLED';

	/**
	 * Whether the provider did register or not.
	 *
	 * @since 5.8.0
	 *
	 * @var bool
	 */
	private bool $did_register = false;

	/**
	 * Registers the bindings, service providers and controllers part of the feature.
	 *
	 * @since 5.8.0
	 *
	 * @return void The bindings, service providers and controllers are registered in the container.
	 */
	public function register() {
		if ( $this->did_register ) {
			return;
		}

		$this->did_register = true;

		// Whether the feature is enabled or not, allow fetching this provider.
		$this->container->singleton( self::class, $this );

		// Bind some implementations common to all Controllers.
		$this->container->singleton( Admin_Views::class, Admin_Views::class );

		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'tec_debug_data', function ( $data ) {
			$data['tec_flexible_tickets'] = true;

			return $data;
		} );

		/**
		 * Fires when the TEC Flexible Tickets feature is activated.
		 *
		 * @since 5.8.0
		 */
		do_action( 'tec_flexible_tickets_activated' );

		require_once __DIR__ . '/template-tags.php';

		$this->container->register( Custom_Tables::class );
		$this->container->register( WP_Cli::class );
		$this->container->register( Base::class );

		$series_are_ticketable = in_array(
			Series::POSTTYPE,
			(array) tribe_get_option( 'ticket-enabled-post-types', [] ),
			true
		);

		if ( $series_are_ticketable ) {
			$this->container->register( Series_Passes\Base::class );
			$this->container->register( Series_Passes\Series_Passes::class );
			$this->container->register( Series_Passes\CT1_Integration::class );
			$this->container->register( Series_Passes\CT1_Migration::class );
			$this->container->register( Series_Passes\Editor::class );
			$this->container->register( Series_Passes\Emails::class );
		}
	}

	/**
	 * Unregisters the bindings, service providers and controllers part of the feature.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Custom_Tables::class )->unregister();
		$this->container->get( WP_Cli::class )->unregister();
		$this->container->get( Base::class )->unregister();

		/*
		 * In the course of the current request, the Series Pass provider might have registered.
		 * Unregister them just to make sure, even if Series might currently not be ticketable.
		 */
		$this->container->get( Series_Passes\Base::class )->unregister();
		$this->container->get( Series_Passes\Series_Passes::class )->unregister();
		$this->container->get( Series_Passes\CT1_Integration::class )->unregister();
		$this->container->get( Series_Passes\CT1_Migration::class )->unregister();
		$this->container->get( Series_Passes\Editor::class )->unregister();
		$this->container->get( Series_Passes\Emails::class )->unregister();
	}

	/**
	 * Determines if the feature is enabled or not.
	 *
	 * The method will check if the feature has been disabled via a constant, an environment variable,
	 * an option or a filter.
	 *
	 * @since 5.8.0
	 *
	 * @return bool Whether the feature is enabled or not.
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
		 * @since 5.8.0
		 *
		 * @param bool $activate Defaults to `true`.
		 *
		 */
		return (bool) apply_filters( 'tec_recurring_tickets_enabled', $active );
	}
}
