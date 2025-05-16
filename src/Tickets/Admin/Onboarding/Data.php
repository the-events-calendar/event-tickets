<?php
/**
 * Class that holds some data functions for the Wizard.
 *
 * @since TBD
 */

namespace TEC\Tickets\Admin\Onboarding;

use TEC\Common\Admin\Onboarding\Abstract_Data;
/**
 * Class Data
 *
 * @since TBD
 * @package TEC\Tickets\Admin\Onboarding
 */
class Data extends Abstract_Data {

	/**
	 * The option name for the wizard settings.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const OPTION_NAME = 'tec_tickets_onboarding_wizard_data';

	/**
	 * Get the saved wizard settings.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_wizard_settings() {
		return get_option( self::OPTION_NAME, [] );
	}

	/**
	 * Update the wizard settings.
	 *
	 * @since TBD
	 *
	 * @param array $settings The settings to update.
	 */
	public function update_wizard_settings( $settings ): bool {
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Get a specific wizard setting by key.
	 *
	 * @since TBD
	 *
	 * @param string $key           The setting key.
	 * @param mixed  $default_value The default value.
	 *
	 * @return mixed
	 */
	public function get_wizard_setting( $key, $default_value = null ) {
		$settings = $this->get_wizard_settings();

		return $settings[ $key ] ?? $default_value;
	}

	/**
	 * Update a specific wizard setting.
	 *
	 * @since TBD
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 */
	public function update_wizard_setting( $key, $value ) {
		$settings         = $this->get_wizard_settings();
		$settings[ $key ] = $value;

		$this->update_wizard_settings( $settings );
	}
}
