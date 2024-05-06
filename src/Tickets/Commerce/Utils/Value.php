<?php

namespace TEC\Tickets\Commerce\Utils;

use Tribe\Values\Abstract_Currency;
use Tribe\Values\Value_Update;

class Value extends Abstract_Currency {

	use Value_Update;

	/**
	 * @inheritdoc
	 */
	public $value_type = 'tickets-commerce';

	/**
	 * @inheritDoc
	 */
	public function set_up_currency_details() {
		$this->currency_code                = Currency::get_currency_code();
		$this->currency_symbol              = Currency::get_currency_symbol( $this->get_currency_code() );
		$this->currency_symbol_position     = Currency::get_currency_symbol_position( $this->get_currency_code() );
		$this->currency_separator_decimal   = Currency::get_currency_separator_decimal( $this->get_currency_code() );
		$this->currency_separator_thousands = Currency::get_currency_separator_thousands( $this->get_currency_code() );
		$this->set_precision( Currency::get_currency_precision( $this->get_currency_code() ) );
	}

	/**
	 * Builds a list of Value objects from a list of numeric values.
	 *
	 * @since 5.2.3
	 *
	 * @param int[]|float[] $values
	 *
	 * @return Value[]
	 */
	public static function build_list( $values ) {
		return array_map( function ( $value ) {

			if ( $value instanceof Value ) {
				return $value;
			}

			return new self( $value );
		}, $values );
	}

	/**
	 * Get formatted html block with formatted currency and symbol.
	 *
	 * @since 5.2.3
	 *
	 * @return string
	 */
	public function get_shortcode_price_html() {

		$position = 'prefix' === $this->get_currency_symbol_position() ? 'prefix' : 'postfix';

		$html[] = "<span class='tribe-formatted-currency-wrap tribe-currency-{$position}'>";
		$html[] = '<span class="tribe-currency-symbol">%1$s</span>';
		$html[] = '<span class="tribe-amount">%2$s</span>';
		$html[] = '</span>';

		if ( 'prefix' !== $position ) {
			// If position is not prefix, swap the symbol and amount span tags.
			$hold    = $html[1];
			$html[1] = $html[2];
			$html[2] = $hold;
		}

		return sprintf( implode( '', $html ),
			esc_html( $this->get_currency_symbol() ),
			esc_html( $this->get_string() )
		);

	}

	/**
	 * Get the display currency.
	 *
	 * @since 5.10.0
	 *
	 * @return string The display text for this value.
	 */
	public function get_currency_display() {
		$currency_display = $this->get_currency();

		if ( $this->get_decimal() == 0 ) {
			$currency_display = _x( 'Free', 'No cost', 'event-tickets' );
		}

		/**
		 * Filter the currency display.
		 *
		 * @since 5.10.0
		 *
		 * @param string $currency_display The currency display.
		 * @param Value  $value            The value object.
		 */
		return apply_filters( 'tec_tickets_commerce_value_get_currency_display', $currency_display, $this );
	}
}
