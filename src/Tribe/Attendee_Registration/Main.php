<?php
/**
 * Attendee Registration core class
 *
 * @since 4.9
 */
class Tribe__Tickets__Attendee_Registration__Main {
	/**
	 * The query var
	 *
	 * @since 4.9
	 *
	 */
	public $key_query_var = 'attendee-registration';

	/**
	 * Default attendee registration slug
	 *
	 * @since 4.9
	 *
	 */
	public $default_page_slug = 'attendee-registration';

	/**
	 * The attendee modal option slug
	 *
	 * @since TBD
	 *
	 */
	public $modal_option_slug = 'ticket-attendee-modal';

	/**
	 * Retrieve the attendee registration slug
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function get_slug() {
		$page = $this->get_attendee_registration_page();

		$slug = $page ? $page->post_name : '';

		if (
			empty( $slug )
			|| (
				! empty( $page )
				&& ! has_shortcode( $page->post_content, 'tribe_attendee_registration' )
			)
		) {
			$slug = Tribe__Settings_Manager::get_option( 'ticket-attendee-info-slug', $this->default_page_slug );
		}

		return $slug;
	}

	/**
	 * Returns whether or not the user is on the attendee registration page
	 *
	 * @since 4.9
	 *
	 * @return bool
	 */
	public function is_on_page() {
		global $wp_query;

		return ! empty( $wp_query->query_vars[ $this->key_query_var ] ) ;
	}

	/**
	 * Returns whether the user is on the /cart/ REST API endpoint.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the user is on the /cart/ REST API endpoint.
	 */
	public function is_cart_rest() {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST || empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return false;
		}

		/** @var Tribe__Tickets__REST__V1__Endpoints__Cart $cart */
		$cart = tribe( 'tickets.rest-v1.endpoints.cart' );

		return $cart->is_active;
	}

	/**
	 * Returns whether or not the user is on a page using the attendee registration shortcode
	 *
	 * @since 4.10.2
	 *
	 * @return boolean
	 */
	public function is_using_shortcode() {
		global $wp_query;

		return ! empty( $wp_query->queried_object->post_content ) && has_shortcode( $wp_query->queried_object->post_content, 'tribe_attendee_registration' );
	}

	/**
	 * Returns a list of providers in the "cart" (AR page)
	 *
	 * @since 4.10.2
	 *
	 * @return array
	 */
	public function providers_in_cart() {
		/**
		 * Allow filtering of commerce providers in cart.
		 *
		 * @since 4.10.2
		 *
		 * @param array $providers List of commerce providers in cart.
		 */
		$providers = apply_filters( 'tribe_providers_in_cart', [] );

		return $providers;
	}

	/**
	 * Returns whether or not the "cart" (AR page) has tickets from multiple providers in it
	 *
	 * @since 4.10.2
	 *
	 * @return boolean
	 */
	public function has_mixed_providers_in_cart() {
		$providers_in_cart = $this->providers_in_cart();
		if ( empty( $providers_in_cart ) ) {
			return false;
		}

		return 1 < count( $providers_in_cart );
	}

	/**
	 * Gets the URL for the attendee registration page
	 *
	 * @since 4.9
	 * @return string
	 */
	public function get_url() {
		$slug = $this->get_slug();

		return home_url( "/{$slug}/" );
	}

	/**
	 * Gets the URL for the checkout url
	 *
	 * @since 4.9
	 * @return string
	 */
	public function get_checkout_url() {
		/**
		 * Gets the attendee registration checkout URL
		 * @since 4.9
		 */
		$checkout_url = apply_filters( 'tribe_tickets_attendee_registration_checkout_url', null );

		if ( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT === tribe_get_request_var( 'provider' ) ) {
			return null;
		}

		// When we want to change where we send folks based on providers, use
		// $this->has_mixed_providers_in_cart();

		return $checkout_url;
	}

	/**
	 * Get the Attendee Registration page object in a backwards compatible way with slug / ID options.
	 *
	 * @since 4.10.4
	 *
	 * @return WP_Post|null The Attendee Registration page object if found, null if not found.
	 */
	public function get_attendee_registration_page() {
		$id   = Tribe__Settings_Manager::get_option( 'ticket-attendee-page-id', false );

		if ( ! empty( $id ) ) {
			return get_post( $id );
		}

		$slug = Tribe__Settings_Manager::get_option( 'ticket-attendee-page-slug', false );

		return get_page_by_path( $slug );
	}

	/**
	 * Check if the modal is enabled.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post|null $post The post (or its ID) we're testing. Defaults to null.
	 *
	 * @return boolean
	 */
	public function is_modal_enabled( $post = null ) {
		$option = tribe( 'settings.manager' )::get_option( 'ticket-attendee-modal' );

		/**
		 * Allow filtering of the modal setting, on a post-by-post basis if desired.
		 *
		 * @since TBD
		 *
		 * @param boolean $option The option value from ticket settings.
		 * @param int|WP_Post|null $post The passed post or null if none passed.
		 */
		return apply_filters( 'tribe_tickets_modal_setting', $option, $post );
	}
}
