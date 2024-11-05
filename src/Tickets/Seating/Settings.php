<?php
/**
 * Seating settings.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Arrays\Arr;

/**
 * Class Settings.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating
 */
class Settings extends Controller_Contract {
	/**
	 * The option name for the frontend timer setting.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const TIMER_LIMIT_OPTION = 'tickets-seating-timer-limit';
	
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since TBD
	 */
	protected function do_register(): void {
		add_filter( 'tribe_tickets_settings_tab_fields', [ $this, 'add_frontend_timer_settings' ] );
	}
	
	/**
	 * Unregisters the Controller.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		remove_filter( 'tribe_tickets_settings_tab_fields', [ $this, 'add_frontend_timer_settings' ] );
	}
	
	/**
	 * Add display settings for Event Tickets.
	 *
	 * @since TBD
	 *
	 * @param array $settings List of display settings.
	 *
	 * @return array List of display settings.
	 */
	public function add_frontend_timer_settings( array $settings ): array {
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
}
