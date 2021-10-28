<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Success
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce
 */
class Success {
	/**
	 * Param we use to store the order ID.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $order_id_query_arg = 'tc-order-id';

	/**
	 * Get the Success page ID.
	 *
	 * @since 5.1.9
	 *
	 *
	 * @return int|null
	 */
	public function get_page_id() {
		$success_page = (int) tribe_get_option( Settings::$option_success_page );

		if ( empty( $success_page ) ) {
			return null;
		}

		/**
		 * Allows filtering of the Page ID for the Success page.
		 *
		 * @since 5.1.9
		 *
		 * @param int|null $success_page Which page is used in the settings.
		 */
		return apply_filters( 'tec_tickets_commerce_success_page_id', $success_page );
	}

	/**
	 * Determine the Current success URL.
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_url() {
		$url = home_url( '/' );
		$success_page = $this->get_page_id();

		if ( is_numeric( $success_page ) ) {
			$success_page = get_post( $success_page );
		}

		// Only modify the URL in case we have a success page setup in the settings.
		if ( $success_page instanceof \WP_Post ) {
			$url = get_the_permalink( $success_page );
		}

		/**
		 * Allows modifications to the success url for Tickets Commerce.
		 *
		 * @since 5.1.9
		 *
		 * @param string $url URL for the cart.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_success_url', $url );
	}

	/**
	 * Determines if the current page is the success page.
	 *
	 * @since 5.1.9
	 *
	 *
	 * @return bool
	 */
	public function is_current_page() {
		if ( is_admin() ) {
			return false;
		}

		$current_page = get_queried_object_id();
		$is_current_page = $this->get_page_id() === $current_page;

		/**
		 * @todo determine hte usage of tribe_ticket_redirect_to
		 * 		$redirect = tribe_get_request_var( 'tribe_tickets_redirect_to', null );
		 */

		/**
		 * Allows modifications to the conditional of if we are in the success page.
		 *
		 * @since 5.1.9
		 *
		 * @param bool $is_current_page Are we in the current page for checkout.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_commerce_success_is_current_page', $is_current_page ) );
	}

	/**
	 * If there is any data or request management or parsing that needs to happen on the success page here is where
	 * we do it.
	 *
	 * @since 5.1.9
	 */
	public function parse_request() {
		if ( ! $this->is_current_page() ) {
			return;
		}

		// In case the ID is passed we set the cookie for usage.
		$cookie_param = tribe_get_request_var( Cart::$cookie_query_arg, false );
		if ( $cookie_param ) {
			tribe( Cart::class )->set_cart_hash_cookie( $cookie_param );
		}
	}

	/**
	 * Maybe add a post display state for special Tickets Commerce Success Page in the page list table.
	 *
	 * @since 5.1.10
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 *
	 * @return array  $post_states An array of post display states.
	 */
	public function maybe_add_display_post_states( $post_states, $post ) {

		if ( $this->get_page_id() === $post->ID ) {
			$post_states['tec_tickets_commerce_page_success'] = __( 'Tickets Commerce Success Page', 'event-tickets' );
		}

		return $post_states;
	}

	/**
	 * Determines whether or not the success page option is set.
	 * 
	 * @since TBD
	 * 
	 * @return bool
	 */
	public function is_option_set() {
		return ! empty( $this->get_page_id() );
	}

	/**
	 * Determines whether or not the success page has the appropriate shortcode in the content.
	 * 
	 * @since TBD
	 * 
	 * @return bool
	 */
	public function page_has_shortcode() {
		$page = get_post( $this->get_page_id() );
		$shortcode = Shortcodes\Success_Shortcode::get_wp_slug();
		return has_shortcode( $page->post_content, $shortcode );
	}

	/**
	 * Determines whether or not we need to show the unset notice.
	 * 
	 * @since TBD
	 * 
	 * @return bool
	 */
	public function show_unset_notice() {
		return ! $this->is_option_set() || ! $this->page_has_shortcode();
	}

	/**
	 * Gets the HTML for the notice that is shown when checkout setting is not set.
	 * 
	 * @since TBD
	 * 
	 * @return string Notice HTML.
	 */
	public function unset_notice() {
		$notice_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/1axv' ),
			esc_html__( 'Learn More', 'event-tickets' )
		);
		$notice_header = esc_html__( 'Set up your order success page', 'event-tickets' );
		$notice_text = sprintf( 
			// translators: %1$s: Link to knowledgebase article.
			esc_html__( 'In order to start selling with Tickets Commerce, you\'ll need to set up your order success page. Please configure the setting on Settings > Payments and confirm that the page you have selected has the proper shortcode. %1$s', 'event-tickets' ),
			$notice_link
		);
		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);
	}
}