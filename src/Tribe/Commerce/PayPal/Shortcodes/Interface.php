<?php
/**
 * Class Tribe__Tickets__Commerce__PayPal__Shortcodes__Success
 *
 * @since TBD
 */
interface Tribe__Tickets__Commerce__PayPal__Shortcodes__Interface {

	/**
	 * Returns the shortcode tag.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function tag();

	/**
	 * Renders the shortcode.
	 *
	 * @since TBD
	 *
	 * @param string|array $attributes An array of shortcode attributes.
	 * @param string       $content    The shortcode content if any.
	 *
	 * @return string
	 */
	public function render( $attributes, $content );
}