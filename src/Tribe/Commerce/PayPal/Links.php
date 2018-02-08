<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Links
 *
 * A PayPal link repository information.
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Links {

	public function ipn_notification_history( $what = 'tag' ) {
		$link = add_query_arg(
			array( 'cmd' => '_display-ipns-history' ),
			tribe( 'tickets.commerce.paypal.gateway' )->get_settings_url()
		);
		$tag  = '<a href="'
		        . esc_attr( $link )
		        . '" target="_blank">'
		        . esc_html__( 'Profile and Settings > My selling tools > Instant Payment Notification > IPN History Page', 'event-tickets' )
		        . '</a>';
		$map  = array(
			'link' => $link,
			'tag'  => $tag,
		);

		return Tribe__Utils__Array::get( $map, $what, '' );
	}

	public function ipn_notification_settings( $what = 'tag' ) {
		$link = add_query_arg(
			array( 'cmd' => '_profile-ipn-notify' ),
			tribe( 'tickets.commerce.paypal.gateway' )->get_settings_url()
		);

		$tag = '<a href="'
		       . esc_attr( $link )
		       . '" target="_blank">' . esc_html__( 'Profile and Settings > My selling tools > Instant Payment Notification > Update', 'event-tickets' )
		       . '</a>';

		$map = array(
			'link' => $link,
			'tag'  => $tag,
		);

		return Tribe__Utils__Array::get( $map, $what, '' );
	}
}