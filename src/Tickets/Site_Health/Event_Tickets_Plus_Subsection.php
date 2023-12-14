<?php
/**
 * Class that handles interfacing with core Site Health.
 *
 * @since   5.6.0.1
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health;

use TEC\Tickets\QR\Settings as QR_Settings;
use Tribe\Tickets\Plus\Attendee_Registration\IAC;

/**
 * Class The_Events_Calendar_Fields
 *
 * @since   TBD
 * @package TEC\Tickets\Site_Health
 */
class Event_Tickets_Plus_Subsection extends Abstract_Info_Subsection {

	/**
	 * @inheritDoc
	 */
	protected function is_subsection_enabled(): bool {
		return class_exists(
			'Tribe__Tickets_Plus__Main',
			false
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function generate_subsection() {
		return [
			[
				'id'       => 'qr_codes_enabled',
				'title'    => 'QR Codes Enabled',
				'value'    => $this->are_qr_codes_enabled(),
				'priority' => 340,
			],
			[
				'id'       => 'iac_default_option',
				'title'    => 'IAC Default Option',
				'value'    => $this->get_iac_default_option(),
				'priority' => 350,
			],
			[
				'id'       => 'attendee_registration_modal_enabled',
				'title'    => 'Attendee Registration Modal Enabled',
				'value'    => $this->is_attendee_registration_modal_enabled(),
				'priority' => 360,
			],
		];
	}

	/**
	 * Checks if QR codes are enabled in the system.
	 *
	 * @return string 'True' if QR codes are enabled, 'False' otherwise.
	 */
	private function are_qr_codes_enabled(): string {
		// Assuming the setting is stored in a boolean format.
		return tribe( QR_Settings::class )->is_enabled() ? 'True' : 'False';
	}

	/**
	 * Fetches the default IAC (Individual Attendee Collection) option value.
	 *
	 * @return string The IAC default option value.
	 */
	private function get_iac_default_option(): string {
		// Fetch the IAC default option value.
		return tribe( IAC::class )->get_default_iac_setting();
	}

	/**
	 * Determines if the attendee registration modal is enabled.
	 *
	 * @return string 'True' if the modal is enabled, 'False' otherwise.
	 */
	private function is_attendee_registration_modal_enabled(): string {
		// Check if attendee registration modal is enabled.
		return tribe_get_option( 'ticket-attendee-modal' ) ? 'True' : 'False';
	}
}
