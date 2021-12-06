<?php

namespace TEC\Tickets\Commerce\Utils;

use Tribe\Values\Abstract_Currency;
use Tribe\Values\Value_Update;

class Value extends Abstract_Currency  {

	use Value_Update;

	/**
	 * @inheritdoc
	 */
	public $value_type = 'tec_tc_value';

	/**
	 * @inheritDoc
	 */
	public function set_up_currency_details() {
		$this->currency_code = Currency::get_currency_code();
		$this->currency_symbol = Currency::get_currency_symbol( $this->get_currency_code() );
		$this->currency_symbol_position = Currency::get_currency_symbol_position( $this->get_currency_code() );
		$this->currency_separator_decimal = Currency::get_currency_separator_decimal( $this->get_currency_code() );
		$this->currency_separator_thousands = Currency::get_currency_separator_thousands( $this->get_currency_code() );
	}

	/**
	 * Builds a list of Value objects from a list of numeric values
	 *
	 * @since TBD
	 *
	 * @param int[]|float[] $values
	 *
	 * @return Value[]
	 */
	public static function build_list( $values ) {
		return array_map( function( $value ) {

			if ( \is_a(  $value,'Tribe\Values\Abstract_Currency' ) ) {
				return $value;
			}

			return new self( $value );
		}, $values );
	}

}