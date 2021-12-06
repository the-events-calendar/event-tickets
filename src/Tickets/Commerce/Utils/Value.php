<?php

namespace TEC\Tickets\Commerce\Utils;

use Tribe\Values\Abstract_Currency;
use Tribe\Values\Value_Update;

class Value extends Abstract_Currency  {

	use Value_Update;

	/**
	 * @var string
	 */
	private $currency_code = 'USD';

	/**
	 * @var string
	 */
	private $currency_separator_decimal = '.';

	/**
	 * @var string
	 */
	private $currency_separator_thousands = ',';

	/**
	 * @var string
	 */
	private $currency_symbol = '$';

	/**
	 * @var string
	 */
	private $currency_symbol_position = 'prefix';

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

	/**
	 * @inheritDoc
	 */
	public function get_currency_code() {
		return $this->currency_code;
	}

	/**
	 * @inheritDoc
	 */
	public function get_currency_symbol() {
		return $this->currency_symbol;
	}

	/**
	 * @inheritDoc
	 */
	public function get_currency_symbol_position() {
		return $this->currency_symbol_position;
	}

	/**
	 * @inheritDoc
	 */
	public function get_currency_separator_decimal() {
		return $this->currency_separator_decimal;
	}

	/**
	 * @inheritDoc
	 */
	public function get_currency_separator_thousands() {
		return $this->currency_separator_thousands;
	}

	/**
	 * @inheritDoc
	 */
	public function set_class_name() {
		$this->class_name = 'tickets_commerce_value';
	}

	/**
	 * @inheritDoc
	 */
	public function get_class_name() {
		return $this->class_name;
	}
}