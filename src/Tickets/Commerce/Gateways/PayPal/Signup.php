<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;
use Tribe__Utils__Array as Arr;

/**
 * Class Signup
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Signup {

	/**
	 * Holds the transient key used to store hash passed to PayPal.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $signup_hash_meta_key = 'tec_tc_paypal_signup_hash';

	/**
	 * Holds the transient key used to link PayPal to this site.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $signup_data_meta_key = 'tec_tc_paypal_signup_data';

	/**
	 * Stores the instance of the template engine that we will use for rendering the page.
	 *
	 * @since TBD
	 *
	 * @var \Tribe__Template
	 */
	protected $template;

	/**
	 * Gets the template instance used to setup the rendering of the page.
	 *
	 * @since TBD
	 *
	 * @return \Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new \Tribe__Template();
			$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/commerce/gateways/paypal' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}
	/**
	 * Gets the saved hash for a given user, empty when non-existent.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_transient_hash() {
		return get_transient( static::$signup_hash_meta_key );
	}

	/**
	 * Gets the saved hash for a given user, empty when non-existent.
	 *
	 * @since TBD
	 *
	 * @param string $value Hash for signup.
	 *
	 * @return bool
	 */
	public function update_transient_hash( $value ) {
		return set_transient( static::$signup_hash_meta_key, $value, DAY_IN_SECONDS );
	}

	/**
	 * Delete Hash transient from the DB.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_transient_hash() {
		return delete_transient( static::$signup_hash_meta_key );
	}

	/**
	 * Gets the saved hash for a given user, empty when non-existent.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_transient_data() {
		return get_transient( static::$signup_data_meta_key );
	}

	/**
	 * Saves the URL in a transient for later use.
	 *
	 * @since TBD
	 *
	 * @param string $value URL for signup.
	 *
	 * @return bool
	 */
	public function update_transient_data( $value ) {
		return set_transient( static::$signup_data_meta_key, $value, DAY_IN_SECONDS );
	}

	/**
	 * Delete url transient from the DB.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_transient_data() {
		return delete_transient( static::$signup_data_meta_key );
	}

	/**
	 * Generate a Unique Hash for signup. It will always be 20 characters long.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function generate_unique_signup_hash() {
		$nonce_key  = defined( 'NONCE_KEY' ) ? NONCE_KEY : uniqid( '', true );
		$nonce_salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : uniqid( '', true );

		$unique = uniqid( '', true );

		$keys = [ $nonce_key, $nonce_salt, $unique ];
		$keys = array_map( 'md5', $keys );

		return substr( str_shuffle( implode( '-', $keys ) ), 0, 45 );
	}

	/**
	 * Generates a Tracking it for this website.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function generate_unique_tracking_id() {
		$id = wp_generate_password( 6, false, false );;
		$url_frags = wp_parse_url( home_url() );
		$url       = Arr::get( $url_frags, 'host' ) . Arr::get( $url_frags, 'path' );
		$url       = add_query_arg( [
			'v' => Gateway::VERSION . '-' . $id,
		], $url );

		/**
		 * Tracking ID sent to PayPal.
		 *
		 * @since TBD
		 *
		 * @param string $url Which ID we are using normally a URL, cannot be longer than 127 chars.
		 */
		$url = apply_filters( 'tec_tickets_commerce_gateway_paypal_tracking_id', $url );

		// Always limit it to 127 chars.
		return substr( (string) $url, 0, 127 );
	}

	/**
	 * Request the signup link that redirects the seller to PayPal.
	 *
	 * @since TBD
	 *
	 * @return string|false
	 */
	public function generate_url() {
		// Fetch the cached value for this user.
		$signup = $this->get_transient_data();
		if ( $signup_url = Arr::get( $signup, [ 'links', 1, 'href' ] ) ) {
			return $signup_url;
		}

		$hash = $this->generate_unique_signup_hash();
		$this->update_transient_hash( $hash );

		$signup = tribe( WhoDat::class )->get_seller_signup_data( $hash );

		if ( ! $signup_url = Arr::get( $signup, [ 'links', 1, 'href' ] ) ) {
			return false;
		}

		$this->update_transient_data( $signup );

		return $signup_url;
	}

	/**
	 * From the Transient data store we get the referral data link.
	 *
	 * @since TBD
	 *
	 *
	 * @return false|string
	 */
	public function get_referral_data_link() {
		$links =  $this->get_transient_data();
		if ( empty( $links ) ) {
			return false;
		}

		return Arr::get( $links, [ 'links', 0, 'href' ], false );
	}

	public function get_link_html() {
		$template_vars = [
			'url' => $this->generate_url(),
		];

		return $this->get_template()->template( 'signup-link', $template_vars, false );
	}
}