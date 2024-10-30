<?php
/**
 * List of Tickets Commerce tickets orders.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/orders-tc-tickets.php
 *
 * @since 5.2.0
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 */

$view      = Tribe__Tickets__Tickets_View::instance();
$post_id   = get_the_ID();
$post      = get_post( $post_id );
$post_type = get_post_type_object( $post->post_type );
$user_id   = get_current_user_id();

if ( ! $view->has_ticket_attendees( $post_id, $user_id ) ) {
	return;
}

$post_type_singular = $post_type ? $post_type->labels->singular_name : _x( 'Post', 'fallback post type singular name', 'event-tickets' );
$orders             = $view->get_event_attendees_by_order( $post_id, $user_id );
$order              = array_values( $orders );
$title              = sprintf(
	// Translators: 1: post type singular name, 2: ticket label plural.
	__( '%1$s %2$s', 'event-tickets' ),
	$post_type_singular,
	tribe_get_ticket_label_plural( 'orders_tickets_heading' )
);

$this->template(
	'tickets/my-tickets',
	[
		'title'   => $title,
		'post_id' => $post_id,
		'orders'  => $orders,
		'post'    => $post,
	]
);
