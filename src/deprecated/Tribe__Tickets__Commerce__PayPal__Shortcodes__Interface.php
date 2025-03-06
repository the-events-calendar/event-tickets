<?php
_deprecated_file( __FILE__, 'TBD' );

/**
 * Class Tribe__Tickets__Commerce__PayPal__Shortcodes__Interface
 *
 * @since 4.7
 * @deprecated TBD
 */
interface Tribe__Tickets__Commerce__PayPal__Shortcodes__Interface {

	/**
	 * Returns the shortcode tag.
	 *
	 * @since 4.7
	 *
	 * @return string
	 */
	public function tag();

	/**
	 * Renders the shortcode.
	 *
	 * @since 4.7
	 *
	 * @param string|array $attributes An array of shortcode attributes.
	 * @param string       $content    The shortcode content if any.
	 *
	 * @return string
	 */
	public function render( $attributes, $content );
}
