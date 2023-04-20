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
	 * Undocumented function
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register() {
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
		// Modal args.
		add_filter( 'tec_common_telemetry_optin_args', [ $this, 'filter_tec_common_telemetry_optin_args' ], 20 );

		add_filter( 'tec_common_telemetry_event-tickets_optin_tab', [ $this, 'filter_tec_common_telemetry_event_tickets_optin_tab' ] );
		// adds opt out field
		add_filter( 'tec_tickets_authentication_settings', [ $this, 'filter_tec_tickets_authentication_settings' ] );
		add_filter( 'tribe_field_value', [ $this, 'filter_tribe_field_opt_in_status' ], 10, 2 );
		add_filter( 'tec_telemetry_slugs', [ $this, 'filter_tec_telemetry_slugs' ] );
		add_filter( 'stellarwp/telemetry/exit_interview_args', [ $this, 'filter_exit_interview_args' ], 10, 2 );
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
	 * @todo: Why must this be hard-coded everywhere?
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

	/**
	 * Ensures the admin control reflects the actual opt-in status.
	 * Note this filter is defined twice with different signatures.
	 * We take the "low road" - 2 params and test them in the later function
	 * to ensure we're only changing the thing we expect.
	 *
	 * @since TBD
	 *
	 * @param mixed  $value  The value of the attribute.
	 * @param string $field  The field object id.
	 *
	 * @return mixed $value
	 */
	public function filter_tribe_field_opt_in_status( $value, $id )  {
		return $this->container->get( Telemetry::class )->filter_tribe_field_opt_in_status( $value, $id );
	}

	/**
	 * Let The Events Calendar add itself to the list of registered plugins for Telemetry.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $slugs The existing array of slugs.
	 *
	 * @return array<string,string> $slugs The modified array of slugs.
	 */
	public function filter_tec_telemetry_slugs( $slugs ) {
		return $this->container->get( Telemetry::class )->filter_tec_telemetry_slugs( $slugs );
	}

	/**
	 * Filters the exit questionnaire shown during plugin deactivation/uninstall.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $args The default args.
	 *
	 * @return array<string,mixed> $args The modified args.
	 */
	public function filter_exit_interview_args( $args, $slug ) {
		return $this->container->make( Telemetry::class )->filter_exit_interview_args( $args, $slug );
	}
}
