<?php
/**
 * Template tags defined by the Flexible Tickets feature.
 *
 * @since TBD
 */

if ( ! function_exists( 'tec_tickets_get_series_pass_singular_lowercase' ) ) {
	/**
	 * Returns the filtered, lowercase singular version of the Series Pass label.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The lowercase singular version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_singular_lowercase( string $context = '' ): string {
		/**
		 * Allows customization of the lowercase singular version of the Series Pass label.
		 *
		 * @since TBD
		 *
		 * @param string $label   The lowercase singular version of the Series Pass label, defaults to "series pass".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_series_pass_singular_lowercase',
			_x( 'series pass', 'lowercase singular label for Series Pass', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tec_tickets_get_series_pass_singular_uppercase' ) ) {
	/**
	 * Returns the filtered, uppercase singular version of the Series Pass label.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The uppercase singular version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_singular_uppercase( string $context = '' ): string {
		/**
		 * Allows customization of the uppercase singular version of the Series Pass label.
		 *
		 * @since TBD
		 *
		 * @param string $label   The uppercase singular version of the Series Pass label, defaults to "Series Pass".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_series_pass_singular_uppercase',
			_x( 'Series Pass', 'Uppercase singular label for Series Pass', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tec_tickets_get_series_pass_plural_lowercase' ) ) {
	/**
	 * Returns the filtered, lowercase plural version of the Series Pass label.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The lowercase plural version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_plural_lowercase( string $context = '' ): string {
		/**
		 * Allows customization of the lowercase plural version of the Series Pass label.
		 *
		 * @since TBD
		 *
		 * @param string $label   The lowercase plural version of the Series Pass label, defaults to "series passes".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_series_pass_plural_lowercase',
			_x( 'series passes', 'lowercase plural label for Series Pass', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tec_tickets_get_series_pass_plural_uppercase' ) ) {
	/**
	 * Returns the filtered, uppercase plural version of the Series Pass label.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The uppercase plural version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_plural_uppercase( string $context = '' ): string {
		/**
		 * Allows customization of the uppercase plural version of the Series Pass label.
		 *
		 * @since TBD
		 *
		 * @param string $label   The uppercase plural version of the Series Pass label, defaults to "Series Passes".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_series_pass_plural_uppercase',
			_x( 'Series Passes', 'uppercase plural label for Series Pass', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tec_tickets_get_default_ticket_type_label' ) ) {
	/**
	 * Returns the filtered default Ticket Type label.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The Ticket type label.
	 */
	function tec_tickets_get_default_ticket_type_label( string $context = '' ): string {
		/**
		 * Allows customization of the default ticket type label.
		 *
		 * @since TBD
		 *
		 * @param string $label   The default ticket type label, defaults to "Single Ticket".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_get_default_ticket_type_label',
			_x( 'Single Ticket', 'default ticket type label', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tec_tickets_get_default_ticket_type_label_lowercase' ) ) {
	/**
	 * Returns the filtered default Ticket Type label in lowercase.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The lowercase version of default ticket type label.
	 */
	function tec_tickets_get_default_ticket_type_label_lowercase( string $context = '' ): string {
		/**
		 * Allows customization of the default ticket type label lowercase.
		 *
		 * @since TBD
		 *
		 * @param string $label   The default ticket type label, defaults to "single ticket".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_get_default_ticket_type_label_lowercase',
			_x( 'single ticket', 'default ticket type label in lowercase', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tec_tickets_get_default_ticket_type_label_plural' ) ) {
	/**
	 * Returns the filtered default Ticket Type label in plural.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The plural version of default ticket type label.
	 */
	function tec_tickets_get_default_ticket_type_label_plural( string $context = '' ): string {
		/**
		 * Allows customization of the default ticket type label plural.
		 *
		 * @since TBD
		 *
		 * @param string $label   The default ticket type label, defaults to "Single Tickets".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_get_default_ticket_type_label_plural',
			_x( 'Single Tickets', 'default ticket type label in plural', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tec_tickets_get_default_ticket_type_label_plural_lowercase' ) ) {
	/**
	 * Returns the filtered default Ticket Type label in plural and lowercase.
	 *
	 * @since TBD
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The plural and lowercase version of default ticket type label.
	 */
	function tec_tickets_get_default_ticket_type_label_plural_lowercase( string $context = '' ): string {
		/**
		 * Allows customization of the default ticket type label plural and lowercase.
		 *
		 * @since TBD
		 *
		 * @param string $label   The default ticket type label, defaults to "single tickets".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tec_tickets_get_default_ticket_type_label_plural_lowercase',
			_x( 'single tickets', 'default ticket type label in plural and lowercase', 'event-tickets' ),
			$context
		);
	}
}
