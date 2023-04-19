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
		 * @param string $label The lowercase singular version of the Series Pass label, defaults to "series pass".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tribe_get_ticket_label_singular_lowercase',
			_x( 'series pass', 'lowercase singular label for Series Pass', 'event-tickets' ),
			$context
		);
	}
}