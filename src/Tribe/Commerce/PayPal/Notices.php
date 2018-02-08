<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Notices
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Notices {

	/**
	 * Triggers the display of the missing PDT identity token notice.
	 *
	 * @since TBD
	 */
	public function show_missing_identity_token_notice() {
		set_transient( $this->slug( 'show-missing-identity-token' ), '1', DAY_IN_SECONDS );
	}

	/**
	 * Hooks the class method to relevant filters and actions.
	 *
	 * @since TBD
	 *
	 */
	public function hook() {
		tribe_notice(
			$this->slug( 'pdt-missing-identity-token' ),
			array( $this, 'render_missing_identity_token_notice' ),
			array(),
			array( $this, 'should_render_missing_identity_token_notice' )
		);

		$this->show_transient_notices();
	}

	/**
	 * Renders (echoes) the missing PDT identity token admin notice.
	 *
	 * @since TBD
	 */
	public function render_missing_identity_token_notice() {
		Tribe__Admin__Notices::instance()->render_paragraph(
			$this->slug( 'pdt-missing-identity-token' ),
			sprintf( '%s, <a href="%s" target="_blank">%s</a>.',
				esc_html__( 'PayPal is using PDT data but you have not set the PDT identity token', 'event-tickets' ),
				esc_attr( admin_url() . '?page=tribe-common&tab=event-tickets#tribe-field-ticket-paypal-identity-token' ),
				esc_html__( 'set it here', 'event-tickets' )
			)
		);
	}

	/**
	 * Whether the missing PDT identity token notice should be rendered or not.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_render_missing_identity_token_notice() {
		$transient      = get_transient( $this->slug( 'show-missing-identity-token' ) );
		$identity_token = tribe_get_option( 'ticket-paypal-identity-token' );

		return ! empty( $transient ) && empty( $identity_token );
	}

	/**
	 * Builds a slug used by the class.
	 *
	 * @since TBD
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function slug( $string ) {
		return 'tickets-commerce-paypal-' . $string;
	}

	/**
	 * Registers a notice to be displayed even if the registering class is not loaded.
	 *
	 * @since TBD
	 *
	 * @param string $slug
	 * @param string $html
	 * @param array $args
	 */
	public function register_transient_notice( $slug, $html, $args = array() ) {
		$transient = $this->slug( 'notices' );
		$notices   = get_transient( $transient );
		$notices   = is_array( $notices ) ? $notices : array();
		$notices   = array_merge( $notices, array( $slug => array( time(), $html, $args ) ) );
		set_transient( $transient, $notices, WEEK_IN_SECONDS );
	}

	/**
	 * Shows notices based on a transient array.
	 *
	 * @since TBD
	 */
	protected function show_transient_notices() {
		$notices = get_transient( $this->slug( 'notices' ) );
		$notices = is_array( $notices ) ? $notices : array();

		foreach ( $notices as $key => $data ) {
			list ( $timestamp, $output, $args ) = $data;

			if ( ( $timestamp + WEEK_IN_SECONDS ) > time() ) {
				tribe_notice(
					$this->slug( $key ),
					$output,
					$args
				);
			} else {
				unset( $notices[ $key ] );
			}
		}

		set_transient( $this->slug( 'notices' ), $notices );
	}

	/**
	 * Removes a transient notice.
	 *
	 * The notice will not be displayed to any other user.
	 *
	 * @since TBD
	 *
	 * @param string $slug
	 */
	public function remove_transient_notice( $slug ) {
		$notices = get_transient( $this->slug( 'notices' ) );
		$notices = is_array( $notices ) ? $notices : array();
		unset( $notices[ $slug ] );
		set_transient( $this->slug( 'notices' ), $notices );
	}
}