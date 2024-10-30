<?php
/**
 * Template tags defined by the Flexible Tickets feature.
 *
 * @since 5.8.0
 */

if ( ! function_exists( 'tec_tickets_get_series_pass_singular_lowercase' ) ) {
	/**
	 * Returns the filtered, lowercase singular version of the Series Pass label.
	 *
	 * @since 5.8.0
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The lowercase singular version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_singular_lowercase( string $context = '' ): string {
		/**
		 * Allows customization of the lowercase singular version of the Series Pass label.
		 *
		 * @since 5.8.0
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
	 * @since 5.8.0
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The uppercase singular version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_singular_uppercase( string $context = '' ): string {
		/**
		 * Allows customization of the uppercase singular version of the Series Pass label.
		 *
		 * @since 5.8.0
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
	 * @since 5.8.0
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The lowercase plural version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_plural_lowercase( string $context = '' ): string {
		/**
		 * Allows customization of the lowercase plural version of the Series Pass label.
		 *
		 * @since 5.8.0
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
	 * @since 5.8.0
	 *
	 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
	 *
	 * @return string The uppercase plural version of the Series Pass label.
	 */
	function tec_tickets_get_series_pass_plural_uppercase( string $context = '' ): string {
		/**
		 * Allows customization of the uppercase plural version of the Series Pass label.
		 *
		 * @since 5.8.0
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
