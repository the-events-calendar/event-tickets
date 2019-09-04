<?php
/**
 * Attendee Registration Modal class
 *
 * @since TBD
 */
class Tribe__Tickets__Attendee_Registration__Modal {

	/**
	 * Setup Modal Cart Template
	 *
	 * @since TBD
	 */
	public function hook() {

		add_filter( 'tribe_events_tickets_attendee_registration_modal_content', [ $this, 'modal_cart_template' ], 10, 2 );
	}

	/**
	 * Add Cart Template for Modal
	 *
	 * @since TBD
	 *
	 * @param string $content a string of default content
	 * @param Tribe__Tickets__Editor__Template $template_obj the Template object
	 *
	 * @return string
	 */
	function modal_cart_template( $content, $template_obj ) {

		$main = Tribe__Tickets__Main::instance();

		$template = 'modal/cart.php';
		if ( $theme_file = locate_template( array( 'tribe-events/' . $template ) ) ) {
			$file = $theme_file;
		} else {
			$file = $main->plugin_path . 'src/views/' . $template;
		}

		/**
		 * Filter Modal Template Template
		 *
		 * @since TBD
		 *
		 * @param string $template the template name
		 * @param string $file the template location
		 *
		 */
		$file = apply_filters( 'tribe_events_tickets_template_' . $template, $file );

		$post_id             = $template_obj->get( 'post_id' );
		$tickets             = $template_obj->get( 'tickets', array() );
		$provider            = $template_obj->get( 'provider' );
		$provider_id         = $template_obj->get( 'provider_id' );
		$cart_url            = $template_obj->get( 'cart_url' );
		$tickets_on_sale     = $template_obj->get( 'tickets_on_sale' );
		$has_tickets_on_sale = $template_obj->get( 'has_tickets_on_sale' );
		$is_sale_past        = $template_obj->get( 'is_sale_past' );

		ob_start();

		include $file;

		return ob_get_clean();
	}
}
