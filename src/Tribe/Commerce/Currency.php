<?php
/**
 * Created by PhpStorm.
 * User: camwyn
 * Date: 1/18/18
 * Time: 3:35 PM
 */

class Tribe__Tickets__Commerce__Currency {
	public $currency_code;

	/* Currency mapping code to symbol and position */
	public $currency_code_options_map = array();


	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @since TBD
	 *
	 * @static
	 * @return Tribe__Tickets__Commerce__Currency
	 */
	public static function get_instance() {
		return tribe( 'tickets.commerce.currency' );
	}

	/**
	 * Class constructor
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->generate_default_currency_map();

		$this->currency_code = tribe_get_option( 'ticket-commerce-currency-code', 'USD' );

		add_filter( 'tribe_events_cost_utils_formatted_events_cost_symbol', array( $this, 'get_currency_symbol') );
		add_filter( 'tribe_events_cost_utils_formatted_events_cost_symbol_position', array( $this, 'get_currency_symbol_position') );
	}

	/**
	 * Get and allow filtering of the currency symbol
	 * @param int|null $post_id
	 *
	 * @return string
	 */
	public function get_currency_symbol( $post_id = null ) {
		$code = tribe_get_option( 'ticket-commerce-currency-code', 'USD' );
		$symbol = $this->currency_code_options_map[ $this->currency_code ]['symbol'];
	error_log(
		print_r(
			$code,
			true
		)
	);
		return apply_filters( 'tribe_commerce_currency_symbol', $symbol, $post_id );
	}

	/**
	 * Get and allow filtering of the currency symbol position
	 * @param int|null $post_id
	 *
	 * @return string
	 */
	public function get_currency_symbol_position( $post_id = null ) {
		if ( isset( $this->currency_code_options_map[ $this->currency_code ]['position'] ) ) {
			$currency_position = $this->currency_code_options_map[ $this->currency_code ]['position'];
		} else {
			$currency_position = 'prefix';
		}

		return apply_filters( 'tribe_commerce_currency_symbol_position', $currency_position, $post_id );
	}

	public function format_currency( $cost, $post_id = null ) {
		$post_id = Tribe__Main::post_id_helper( $post_id );
		$currency_symbol   = $this->get_currency_symbol( $post_id );
		$currency_position = $this->get_currency_symbol_position( $post_id );

		$cost = $currency_position === 'after' ? $cost . $currency_symbol : $currency_symbol . $cost;

		return $cost;
	}

	/**
	 * Generates the default map and allows for filtering
	 *
	 * @since TBD
	 */
	public function generate_default_currency_map() {
		$default_map = array(
			'AUD' => array(
				'name'   => __( 'Australian Dollar (AUD)', 'event-tickets' ),
				'symbol' => '&#x41;&#x24;',
			),
			'BRL' => array(
				'name'   => __( 'Brazilian Real  (BRL)', 'event-tickets' ),
				'symbol' => '&#82;&#x24;',
			),
			'CAD' => array(
				'name'   => __( 'Canadian Dollar (CAD)', 'event-tickets' ),
				'symbol' => '&#x24;',
			),
			'CHF' => array(
				'name'   => __( 'Swiss Franc (CHF)', 'event-tickets' ),
				'symbol' => '&#x43;&#x48;&#x46;',
			),
			'CZK' => array(
				'name'     => __( 'Czech Koruna (CZK)', 'event-tickets' ),
				'symbol'   => '&#x4b;&#x10d;',
				'position' => 'after',
			),
			'DKK' => array(
				'name'   => __( 'Danish Krone (DKK)', 'event-tickets' ),
				'symbol' => '&#107;&#114;',
			),
			'EUR' => array(
				'name'   => __( 'Euro (EUR)', 'event-tickets' ),
				'symbol' => '&#8364;',
			),
			'GBP' => array(
				'name'   => __( 'Pound Sterling (GBP)', 'event-tickets' ),
				'symbol' => '&#163;',
			),
			'HKD' => array(
				'name'   => __( 'Hong Kong Dollar (HKD)', 'event-tickets' ),
				'symbol' => '&#x24;',
			),
			'HUF' => array(
				'name'   => __( 'Hungarian Forint (HUF)', 'event-tickets' ),
				'symbol' => '&#x46;&#x74;',
			),
			'ILS' => array(
				'name'   => __( 'Israeli New Sheqel (ILS)', 'event-tickets' ),
				'symbol' => '&#x20aa;',
			),
			'JPY' => array(
				'name'   => __( 'Japanese Yen (JPY)', 'event-tickets' ),
				'symbol' => '&#165;',
			),
			'MYR' => array(
				'name'   => __( 'Malaysian Ringgit (MYR)', 'event-tickets' ),
				'symbol' => '&#82;&#77;',
			),
			'MXN' => array(
				'name'   => __( 'Mexican Peso (MXN)', 'event-tickets' ),
				'symbol' => '&#x24;',
			),
			'NOK' => array(
				'name'   => __( 'Norwegian Krone (NOK)', 'event-tickets' ),
				'symbol' => '',
			),
			'NZD' => array(
				'name'   => __( 'New Zealand Dollar (NZD)', 'event-tickets' ),
				'symbol' => '&#x24;',
			),
			'PHP' => array(
				'name'   => __( 'Philippine Peso (PHP)', 'event-tickets' ),
				'symbol' => '&#x20b1;',
			),
			'PLN' => array(
				'name'   => __( 'Polish Zloty (PLN)', 'event-tickets' ),
				'symbol' => '&#x7a;&#x142;',
			),
			'SEK' => array(
				'name'   => __( 'Swedish Krona (SEK)', 'event-tickets' ),
				'symbol' => '&#x6b;&#x72;',
			),
			'SGD' => array(
				'name'   => __( 'Singapore Dollar (SGD)', 'event-tickets' ),
				'symbol' => '&#x53;&#x24;',
			),
			'THB' => array(
				'name'   => __( 'Thai Baht (THB)', 'event-tickets' ),
				'symbol' => '&#x0e3f;',
			),
			'TWD' => array(
				'name'   => __( 'Taiwan New Dollar (TWD)', 'event-tickets' ),
				'symbol' => '&#x4e;&#x54;&#x24;',
			),
			'USD' => array(
				'name'   => __( 'U.S. Dollar (USD)', 'event-tickets' ),
				'symbol' => '&#x24;',
			),
		);

		$this->currency_code_options_map = apply_filters(
			'tribe_tickets_commerce_currency_code_options_map',
			$default_map
		);
	}

	/**
	 * Creates the array for a currency drop-down using only code & name
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function generate_currency_code_options() {
		// Filtered in generate_default_currency_map() above and in specific implementations.
		return array_map(
			function( $a ) {
				return array_reduce(
					array_keys( $a ),
					function ( $carry, $key ) use ( $a ) {
						if ( 'name' === $key ) {
							$carry = $a[ $key ];
						}
						return $carry;
					}
				);
			},
			$this->currency_code_options_map
		);
	}

}