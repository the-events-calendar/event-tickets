<?php

class Tribe__Tickets__Commerce__PayPal__Notices {

	public function show_missing_identity_token_notice() {
		tribe_notice(
			$this->slug( 'pdt-missing-identity-token' ),
			array( $this, 'render_missing_identity_token_notice' ),
			array(),
			array( $this, 'should_render_missing_identity_token_notice' )
		);
	}

	public function render_missing_identity_token_notice() {
		Tribe__Admin__Notices::instance()->render(
			$this->slug( 'pdt-missing-identity-token' ),
			sprintf( '%s, <a href="%s" target="_blank">%s</a>.',
				esc_html__( 'PayPal is using PDT data but you have not set the PDT identity token,', 'event-tickets' ),
				esc_attr( admin_url() . 'page=tribe-common&tab=event-tickets#tribe-field-ticket-paypal-identity-token' ),
				esc_html__( 'set it here', 'event-tickets' )
			) );
	}

	public function should_render_missing_identity_token_notice() {
		return tribe_get_option( 'ticket-paypal-identity-token' );
	}

	protected function slug( $string ) {
		return 'tickets-commerce-' . $string;
	}
}