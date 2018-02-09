<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Links
 *
 * A PayPal link repository information.
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Links {

	/**
	 * Returns the link to the IPN notification history page on PayPal.
	 *
	 * @since TBD
	 *
	 * @param string $what Either `link` to return the URL or `tag` to return an `a` tag.
	 *
	 * @return string
	 */
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

	/**
	 * Returns the link to the IPN notification settings page on PayPal.
	 *
	 * @since TBD
	 *
	 * @param string $what Either `link` to return the URL or `tag` to return an `a` tag.
	 *
	 * @return string
	 */
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

	/**
	 * Returns the link to an Order page on PayPal, based on the Order ID.
	 *
	 * @since TBD
	 *
	 * @param string $what Either `link` to return the URL or `tag` to return an `a` tag.
	 * @param string $order_id The Order PayPal ID (hash).
	 * @param string $text An optional message that will be used as the `tag` text; defaults to
	 *                  the Order PayPal ID (hash).
	 *
	 * @return string
	 */
	public function order_link( $what, $order_id, $text = null ) {
		$text = null !== $text ? $text : $order_id;

		$link = Tribe__Tickets__Commerce__PayPal__Order::get_order_link( $order_id );
		$tag  = '<a href="'
		        . esc_attr( $link )
		        . '" target="_blank">' . esc_html__( $text )
		        . '</a>';

		$map = array(
			'link' => $link,
			'tag'  => $tag,
		);

		return Tribe__Utils__Array::get( $map, $what, '' );
	}
}