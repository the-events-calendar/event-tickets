<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Shortcodes__Success
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Shortcodes__Success implements Tribe__Tickets__Commerce__PayPal__Shortcodes__Interface {

	/**
	 * Returns the shortcode tag.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function tag() {
		return 'tribe-tpp-success';
	}

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
	public function render( $attributes, $content ) {
		$template = tribe( 'tickets.commerce.paypal.endpoints.templates.success' );
		$template->enqueue_resources();
		$rendered = $template->render();

		return $rendered;
	}
}