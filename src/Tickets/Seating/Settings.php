<?php
/**
 * Seating settings.
 *
 * @since 5.17.0
 *
 * @package TEC\Tickets\Seating
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Arrays\Arr;
use TEC\Tickets\Seating\Service\Service;

/**
 * Class Settings.
 *
 * @since 5.17.0
 *
 * @package TEC\Tickets\Seating
 */
class Settings extends Controller_Contract {
	/**
	 * The option name for the frontend timer setting.
	 *
	 * @since 5.17.0
	 *
	 * @var string
	 */
	private const TIMER_LIMIT_OPTION = 'tickets-seating-timer-limit';

	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 5.17.0
	 */
	protected function do_register(): void {
		add_filter( 'tribe_tickets_settings_tab_fields', [ $this, 'add_frontend_timer_settings' ] );
	}

	/**
	 * Unregisters the Controller.
	 *
	 * @since 5.17.0
	 */
	public function unregister(): void {
		remove_filter( 'tribe_tickets_settings_tab_fields', [ $this, 'add_frontend_timer_settings' ] );
	}

	/**
	 * Add display settings for Event Tickets.
	 *
	 * @since 5.17.0
	 * @since 5.18.0 Only add settings if the seating service has a valid license.
	 *
	 * @param array $settings List of display settings.
	 *
	 * @return array List of display settings.
	 */
	public function add_frontend_timer_settings( array $settings ): array {
		$service_status = tribe( Service::class )->get_status();

		if ( $service_status->has_no_license() || $service_status->is_license_invalid() ) {
			return $settings;
		}

		$timer_settings = [
			'ticket-seating-options-heading' => [
				'type' => 'html',
				'html' => '<h3>' . __( 'Seating', 'event-tickets' ) . '</h3>',
			],
			self::TIMER_LIMIT_OPTION         => [
				'type'            => 'text',
				'label'           => __( 'Reservation Time Limit', 'event-tickets' ),
				'tooltip'         => __( 'The number of minutes a customer has to choose seats and complete checkout.', 'event-tickets' ),
				'validation_type' => 'positive_int',
				'size'            => 'small',
				'default'         => 15,
			],
		];

		return Arr::insert_after_key( 'ticket-authentication-requirements', $settings, $timer_settings );
	}

	/**
	 * Get the reservation time limit in minutes.
	 *
	 * @since 5.17.0
	 *
	 * @return int
	 */
	public function get_reservation_time_limit(): int {
		return (int) tribe_get_option( self::TIMER_LIMIT_OPTION, 15 );
	}
}
