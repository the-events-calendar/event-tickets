<?php
/**
 * Attendee Registration core class
 *
 * @since TBD
 */
class Tribe__Tickets__Attendee_Registration__Main {
	const QUERY_VAR = 'attendee-registration';
	const DEFAULT_PAGE_SLUG = 'attendee-registration';

	/**
	 * Retrieve the attendee registration slug
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_slug() {
		return Tribe__Settings_Manager::get_option( 'ticket-attendee-info-slug', self::DEFAULT_PAGE_SLUG );
	}

	/**
	 * Returns whether or not the user is on the attendee registration page
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_on_page() {
		global $wp_query;

		return ! empty( $wp_query->query_vars[ self::QUERY_VAR ] );
	}

	/**
	 * Gets the URL for the attendee registration page
	 *
	 * @return string
	 */
	public function get_url() {
		$slug = $this->get_slug();

		return home_url( "/{$slug}/" );
	}

	public function get_checkout_url() {
	/**
	 * Gets the attendee registration checkout URL
	 */
		$checkout_url = apply_filters( 'tribe_tickets_attendee_registration_checkout_url', null );

		return $checkout_url;
	}
}