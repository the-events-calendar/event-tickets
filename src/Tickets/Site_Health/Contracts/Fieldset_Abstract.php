<?php
/**
 * Abstract Contract for the Fieldset that handles setting up a group of fields for the Site Health page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Contracts
 */
namespace TEC\Tickets\Site_Health\Contracts;

use TEC\Common\Site_Health\Fields\Generic_Info_Field;
use TEC\Common\Site_Health\Info_Field_Abstract;
use TEC\Common\Site_Health\Info_Section_Abstract;

/**
 * Abstract Class Fieldset_Abstract
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Contracts
 */
abstract class Fieldset_Abstract implements Fieldset_Interface {

	/**
	 * The value for yes, intentionally not translated.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const YES = 'yes';

	/**
	 * The value for no, intentionally not translated.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const NO = 'no';

	/**
	 * Stores the base priority for the fields in this set.
	 *
	 * @since TBD
	 *
	 * @var float $priority
	 */
	protected float $priority = 10.0;

	/**
	 * Every time you get the priority it adds a bit to it, allowing fields to be grouped together.
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	protected function get_priority(): float {
		return $this->priority + 0.1;
	}

	/**
	 * @inheritdoc
	 */
	public function register_fields_to( Info_Section_Abstract $section ): void {
		$fields = $this->to_array();

		foreach ( $fields as $field ) {
			$section->add_field( $field );
		}
	}

	/**
	 * Get the fields for this fieldset.
	 *
	 * @since TBD
	 *
	 * @return array<Info_Field_Abstract|array>
	 */
	abstract protected function get_fields(): array;

	/**
	 * @inheritdoc
	 */
	public function to_array(): array {
		$fields = array_map(
			function ( $field ) {
				if ( $field instanceof Info_Field_Abstract ) {
					return $field;
				}

				if ( ! is_array( $field ) ) {
					return null;
				}

				if ( is_callable( $field['value'] ) ) {
					$field['value'] = call_user_func_array( $field['value'], [] );
				}

				if ( ! isset( $field['priority'] ) ) {
					$field['priority'] = $this->get_priority();
				}

				return Generic_Info_Field::from_array( $field );
			},
			$this->get_fields()
		);

		return array_filter(
			$fields,
			static function ( $field ) {
				return $field instanceof Info_Field_Abstract;
			}
		);
	}
}