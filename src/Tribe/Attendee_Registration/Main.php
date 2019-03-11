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
	 * Retrieve the attendee registration slug
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function get_slug() {
		$slug = Tribe__Settings_Manager::get_option( 'ticket-attendee-page-slug', false );

		$page = get_page_by_path( $slug );

		if ( empty( $slug ) || ! has_shortcode( $page->post_content, 'tribe_attendee_registration' ) ) {
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

		return ! empty( $wp_query->query_vars[ $this->key_query_var ] ) || ( ! empty( $wp_query->queried_object->post_content ) && has_shortcode( $wp_query->queried_object->post_content, 'tribe_attendee_registration' ) );
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

		return $checkout_url;
	}
}
