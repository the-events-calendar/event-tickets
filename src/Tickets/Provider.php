<?php

/**
 * The main service provider for the Tickets updated and new code.
 *
 * @since   5.1.6
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Custom_Tables\V1\Provider as ET_CT1_Provider;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Class Provider for all the Tickets loading.
 *
 * @since   5.1.6
 * @package TEC\Tickets
 */
class Provider extends Service_Provider {
	/**
	 * The action that will fire once this provider has registered.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_provider_registered';

	/**
	 * @var bool Flag whether this provider has registered itself and dependencies yet or not.
	 */
	private $has_registered = false;

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.6.4   Use `register_on_action` method to register custom table providers.
	 * @since 5.5.0
	 * @since 5.1.6
	 */
	public function register() {
		if ( $this->has_registered ) {

			return false;
		}

		require_once Tickets_Plugin::instance()->plugin_path . 'src/functions/commerce/provider.php';
		require_once Tickets_Plugin::instance()->plugin_path . 'src/functions/emails/provider.php';
		require_once Tickets_Plugin::instance()->plugin_path . 'src/functions/attendees/provider.php';

		$this->register_hooks();
		$this->register_assets();

		// Register the SP on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets.provider', $this );

		// Loads the QR code controller.
		$this->container->register( QR\Controller::class );

		// Loads all of tickets commerce.
		$this->container->register( Commerce\Provider::class );

		// Load compatibility with ECP Recurrence engine.
		$this->container->register( Recurrence\Provider::class );

		// Loads all of tickets emails.
		$this->container->register( Emails\Provider::class );

		// Loads admin area.
		$this->container->register( Admin\Provider::class );

		// Loads admin area.
		$this->container->register( Site_Health\Provider::class );

		// Loads admin area.
		$this->container->register( Telemetry\Provider::class );

		// Loads Integrations.
		$this->container->register( Integrations\Provider::class );

		// CT1 only Providers here.
		$this->container->register_on_action( 'tec_events_custom_tables_v1_fully_activated', ET_CT1_Provider::class );

		// Flexible Tickets Providers.
		// Always register the CT1 migration component of Flexible Tickets.
		$this->container->register_on_action( 'tec_pro_ct1_provider_registered', Flexible_Tickets\Series_Passes\CT1_Migration::class );
		// Upon CT1 full activation register the rest of the components.
		$this->container->register_on_action(
			'tec_events_pro_custom_tables_v1_fully_activated',
			Flexible_Tickets\Provider::class
		);

		// Blocks.
		$this->container->register( Blocks\Controller::class );

		// Seating.
		$this->container->register( Seating\Controller::class );

		// Ticket Action hooks.
		$this->container->register( Ticket_Actions::class );

		$this->has_registered = true;

		return true;
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets.
	 *
	 * @since 5.1.6
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets.
	 *
	 * @since 5.1.6
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets.hooks', $hooks );
	}
}
