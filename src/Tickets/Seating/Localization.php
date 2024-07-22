<?php
/**
 * A class that holds all the localized strings for the plugin.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

/**
 * Class Localization.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */
class Localization {
	/**
	 * A
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private array $built_strings = [];

	/**
	 * Get the capacity form strings.
	 *
	 * @since TBD
	 *
	 * @param bool $force If true, the strings will be rebuilt.
	 *
	 * @return array<string, string> The capacity form strings.
	 */
	public function get_capacity_form_strings( $force = false ): array {
		if ( ! $force && isset( $this->built_strings['capacity-form'] ) ) {
			return $this->built_strings['capacity-form'];
		}

		$strings = [
			'general-admission-label'          => _x( 'General Admission', 'Capacity option label', 'event-tickets' ),
			'seat-option-label'                => _x( 'Assigned Seating', 'Capacity option label', 'event-tickets' ),
			'event-layouts-select-placeholder' => _x( 'Choose event layout', 'Capacity form label', 'event-tickets' ),
			'view-layouts-link-label'          => _x( 'View layouts', 'Capacity form label', 'event-tickets' ),
			'seat-types-select-placeholder'    => _x( 'Choose seat type', 'Capacity form label', 'event-tickets' ),
			'event-layouts-capacity-info'      => _x( 'Capacity is defined by seat layout options.', 'Capacity form info', 'event-tickets' ),
			'seat-layout-label'                => _x( 'Seat Layout', 'Capacity form label', 'event-tickets' ),
			'no-layouts-available'             => _x( 'No active Seat Layouts.', 'Capacity form empty layouts', 'event-tickets' ),
			'no-layouts-available-info'        => _x( 'You must create a Seat Layout to use this feature.', 'Capacity form empty layouts info', 'event-tickets' ),
			'no-layouts-available-link-label'  => _x( 'Go to Seat Layouts', 'Capacity form empty layouts label', 'event-tickets' ),
		];

		$this->built_strings['capacity-form'] = $strings;

		return $strings;
	}

	/**
	 * Get the service errors strings.
	 *
	 * @since TBD
	 *
	 * @param bool $force If true, the strings will be rebuilt.
	 *
	 * @return array<string, string> The service errors strings.
	 */
	public function get_service_error_strings( bool $force = false ): array {
		if ( ! $force && isset( $this->built_strings['service-errors'] ) ) {
			return $this->built_strings['service-errors'];
		}

		$strings = [
			'bad-service-response'          => _x(
				'Bad service response',
				'Error message',
				'event-tickets' 
			),
			'missing-request-parameters'    => _x(
				'Missing request parameters',
				'Error message',
				'event-tickets' 
			),
			'invalid-site-parameter'        => _x(
				'Invalid site parameter',
				'Error message',
				'event-tickets' 
			),
			'invalid-expire-time-parameter' => _x(
				'Invalid expire time parameter',
				'Error message',
				'event-tickets' 
			),
			'missing-ephemeral-token'       => _x(
				'Ephemeral token not found in response',
				'Error message',
				'event-tickets' 
			),
			'site-not-found'                => _x(
				'Site not found',
				'Error message',
				'event-tickets' 
			),
			'ephemeral-token-store-error'   => _x(
				'Ephemeral token store error',
				'Error message',
				'event-tickets' 
			),
			'site-not-authorized'           => _x(
				'Site not authorized',
				'Error message',
				'event-tickets' 
			),
		];

		$this->built_strings['service-errors'] = $strings;

		return $strings;
	}
}
