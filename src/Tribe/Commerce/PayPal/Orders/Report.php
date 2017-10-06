<?php

class Tribe__Tickets__Commerce__PayPal__Orders__Report {

	/**
	 * Slug of the admin page for orders
	 *
	 * @var string
	 */
	public static $orders_slug = 'tpp-orders';

	public function hook() {
		add_filter( 'post_row_actions', array( $this, 'add_orders_row_action' ), 10, 2 );
	}

	public function add_orders_row_action( array $actions, $post ) {
		$post_id = Tribe__Main::post_id_helper( $post );
		$post = get_post( $post_id );

		// only if tickets are active on this post type
		if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types(), true ) ) {
			return $actions;
		}

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		$has_tickets = count( $paypal->get_attendees_by_id( $post->ID ) );

		if ( ! $has_tickets ) {
			return $actions;
		}

		$url         = $this->get_tickets_report_link( $post );
		$post_labels = get_post_type_labels( get_post_type_object( $post->post_type ) );
		$type        = strtolower( $post_labels->singular_name );

		$actions['tickets_orders'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			sprintf( esc_html__( 'See PayPal purchases for this %s', 'event-tickets-plus' ), $type ),
			esc_url( $url ),
			esc_html__( 'PayPal Orders', 'event-tickets-plus' )
		);

		return $actions;
	}

	protected function get_tickets_report_link( $post ) {
		$url = add_query_arg( array(
			'post_type' => $post->post_type,
			'page'      => self::$orders_slug,
			'post_id'   => $post->ID,
		), admin_url( 'edit.php' ) );

		return $url;
	}
}