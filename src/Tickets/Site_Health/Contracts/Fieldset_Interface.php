<?php

namespace TEC\Tickets\Site_Health\Contracts;

use TEC\Common\Site_Health\Info_Section_Abstract;

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