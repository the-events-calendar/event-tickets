<?php
/**
 * Service Provider for interfacing with tec-common Telemetry.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Telemetry;

use TEC\Common\lucatume\DI52\ServiceProvider as ServiceProvider;
use Tribe\Tickets\Admin\Settings;

 /**
  * Class Provider
  *
  * @since   TBD

  * @package TEC\Tickets\Telemetry
  */
class Provider extends ServiceProvider {
	/**
	 * Slug for the section.
	 *
	 * @since TBD
	 *
	 * @var string $slug
	 */
	protected static string $slug = 'tec-tickets';


	public function register() {
		// wp-admin/admin.php?page=tec-tickets-settings
		if ( ! tribe( Settings::class )->is_tec_tickets_settings() ) {
			return;
		}

		$this->add_filters();
	}

	/**
	 * Hooks filters for Telemetry
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function add_filters() {
		add_filter( 'tec_common_telemetry_optin_args', [ $this, 'filter_tec_common_telemetry_optin_args' ], 20 );
		add_filter( 'tec_common_telemetry_event-tickets_optin_tab', [ $this, 'filter_tec_common_telemetry_event_tickets_optin_tab' ] );
		add_filter( 'tec_tickets_authentication_settings', [ $this, 'filter_tec_tickets_authentication_settings' ] );
	}

	/**
	 * Filters the modal optin args to be specific to ET
	 *
	 * @since TBD
	 *
	 * @param array<string|mixed> $original_optin_args The original args, provided by Common.
	 *
	 * @return array<string|mixed> The filtered args.
	 */
	public function filter_tec_common_telemetry_optin_args( $optin_args ): array {
		return $this->container->get( Telemetry::class )->filter_tec_common_telemetry_optin_args(  $optin_args );
	}

	/**
	 * Filters the expected settings tab for the Telemetry control.
	 *
	 * @since TBD
	 *
	 * @param string $tab The current expected tab slug
	 *
	 * @return string The filtered tab slug
	 */
	public function filter_tec_common_telemetry_event_tickets_optin_tab( $tab ): string {
		return 'event-tickets';
	}

	/**
	 * Append the opt-in/out control to the first page of ticket settings,
	 * just below the Login Requirements section.
	 *
	 * @since TBD
	 *
	 * @param array,string|mixed $fields The current array of fields for the "Login Requirements" section.
	 *
	 * @return array
	 */
	public function filter_tec_tickets_authentication_settings( $fields ): array {
		return $this->container->get( Telemetry::class )->filter_tec_tickets_authentication_settings(  $fields );
	}
}
