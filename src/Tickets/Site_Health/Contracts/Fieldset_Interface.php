<?php
/**
 * Interface Contract for the Fieldset that handles setting up a group of fields for the Site Health page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Contracts
 */
namespace TEC\Tickets\Site_Health\Contracts;

use TEC\Common\Site_Health\Info_Section_Abstract;

/**
 * Interface Fieldset_Interface
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Contracts
 */
interface Fieldset_Interface {
	/**
	 * Register the fields to the section.
	 *
	 * @since TBD
	 *
	 * @param Info_Section_Abstract $section
	 *
	 */
	public function register_fields_to( Info_Section_Abstract $section ): void;

	/**
	 * Convert the fields to an array of Info_Field_Abstract.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function to_array(): array;
}