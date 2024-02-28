<?php
/**
 * Abstract class that handles adding fields to Site Health.
 *
 * This class serves as a base for specific subsections within the Site Health
 * info page, allowing for organized and modular representation of different data sets.
 *
 * @since   5.8.1
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health;

use Tribe__Tickets__Tickets as Tickets;

/**
 * Abstract class for creating subsections in the Site Health information section.
 *
 * It provides a standard way to add and manage fields specific to different subsections
 * in the Site Health info panel.
 *
 * @since   5.8.1
 */
abstract class Abstract_Info_Subsection {

	/**
	 * An array to hold the fields for the subsection.
	 *
	 * @var array
	 */
	private array $fields = [];

	/**
	 * Determines if the subsection is enabled and should be displayed.
	 *
	 * The method should be overridden in the subclass to provide specific logic
	 * determining whether the subsection should be active.
	 *
	 * @return bool True if the subsection is enabled, false otherwise.
	 */
	abstract protected function is_subsection_enabled(): bool;

	/**
	 * Retrieves the fields for the subsection.
	 *
	 * This method checks if the subsection is enabled and returns the generated fields.
	 *
	 * @return array An array of fields for the subsection.
	 */
	public function get_subsection(): array {
		if ( ! $this->is_subsection_enabled() ) {
			return [];
		}

		$this->fields = $this->generate_subsection();
		return $this->fields;
	}

	/**
	 * Generates the fields for the subsection.
	 *
	 * This method should be implemented in the subclass to define the specific fields
	 * for the subsection.
	 *
	 * @return array An array of fields.
	 */
	abstract protected function generate_subsection(): array;

	/**
	 * Simplified method to return 'True' or 'False' string with translation.
	 *
	 * @param bool $condition The condition to check.
	 *
	 * @return string Translated 'True' or 'False'.
	 */
	protected function get_boolean_string( bool $condition ): string {
		return $condition ? esc_html__(
			'True',
			'event-tickets'
		) : esc_html__(
			'False',
			'event-tickets'
		);
	}

	/**
	 * Checks if ticketing providers other than RSVP enabled.
	 *
	 * @since TBD
	 *
	 * @return bool Returns false if the only active ticketing provider is 'Tribe__Tickets__RSVP', indicating a minimal setup. Returns true if additional ticketing providers are active, suggesting a fully enabled ticketing environment.
	 */
	protected function are_ticketed_providers_enabled(): bool {
		$rsvp_module    = 'Tribe__Tickets__RSVP';
		$active_modules = Tickets::modules();

		// Check if only one provider is returned and if that is Tribe__Tickets__RSVP.
		if ( count( $active_modules ) === 1 && array_key_exists( $rsvp_module, $active_modules ) ) {
			return false;
		}

		return true;
	}
}
