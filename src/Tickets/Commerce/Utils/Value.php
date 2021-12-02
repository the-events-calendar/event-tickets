<?php

namespace TEC\Tickets\Commerce\Utils;

use Tribe\Values\Abstract_Currency;

class Value extends Abstract_Currency  {

	private $currency_code = 'USD';

	private $currency_separator_decimal = '.';

	private $currency_separator_thousands = ',';

	private $currency_symbol = '$';

	private $currency_symbol_position = 'prefix';

	public function set_up_currency_details() {
		$this->currency_code = Currency::get_currency_code();
		$this->currency_symbol = Currency::get_currency_symbol( $this->get_currency_code() );
		$this->currency_symbol_position = Currency::get_currency_symbol_position( $this->get_currency_code() );
		$this->currency_separator_decimal = Currency::get_currency_separator_decimal( $this->get_currency_code() );
		$this->currency_separator_thousands = Currency::get_currency_separator_thousands( $this->get_currency_code() );
	}

	public function get_currency_code() {
		return $this->currency_code;
	}

	public function get_currency_symbol() {
		return $this->currency_symbol;
	}

	public function get_currency_symbol_position() {
		return $this->currency_symbol_position;
	}

	public function get_currency_separator_decimal() {
		return $this->currency_separator_decimal;
	}

	public function get_currency_separator_thousands() {
		return $this->currency_separator_thousands;
	}
}