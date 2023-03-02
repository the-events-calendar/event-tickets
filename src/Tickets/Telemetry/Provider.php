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

	public function add_filters() {
		add_filter( 'tec_common_telemetry_optin_args', [ $this, 'filter_tec_common_telemetry_optin_args' ], 20 );
	}

	public function filter_tec_common_telemetry_optin_args( $optin_args ) {
		return $this->container->get( Telemetry::class )->filter_tec_common_telemetry_optin_args(  $optin_args );
	}

}
