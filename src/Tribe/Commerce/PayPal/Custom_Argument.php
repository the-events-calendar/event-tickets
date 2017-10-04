<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Custom_Argument
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Custom_Argument {

	/**
	 * @var int The total char limit imposed by PayPal for the `custom` field
	 */
	public static $char_limit = 256;

	/**
	 * Builds a JSON and URL encoded entry usable in PayPal `custom` argument.
	 *
	 * @since TBD
	 *
	 * @param array $args
	 *
	 * @return string The encoded array.
	 *
	 * @throws \InvalidArgumentException If the provided arguments array is over the PayPal char limit
	 *                                   after JSON and URL encoding have been applied.
	 */
	public static function encode( array $args ) {
		$encoded = urlencode_deep( json_encode( $args ) );

		$strlen = strlen( $encoded );

		if ( $strlen > self::$char_limit ) {
			$message = 'PayPal imposes a char limit on the custom argument of ' . self::$char_limit . ", the total length of the encoded data provided is {$strlen}.";

			throw new InvalidArgumentException( $message );
		}

		return $encoded;
	}

	/**
	 * Decodes an array of arguments encoded using the `encode` method.
	 *
	 * @since TBD
	 *
	 * @param      string $encoded
	 * @param bool        $assoc_array Same as `json_decode` argument; whether the returned value
	 *                                 should be an object (`false`) or an associative array (`true`).
	 *
	 * @return array|stdClass The decoded object if a valid decoded string was passed or a empty
	 *                        array/object if the passed string is invalid.
	 *
	 * @see   Tribe__Tickets__Commerce__PayPal__Custom_Argument::encode
	 */
	public static function decode( $encoded, $assoc_array = false ) {
		$decoded = json_decode( urldecode_deep( $encoded ), $assoc_array );

		if ( null === $decoded ) {
			return $assoc_array ? array() : new stdClass();
		}

		return $decoded;
	}
}