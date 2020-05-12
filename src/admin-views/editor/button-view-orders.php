<?php
$post_id = get_the_ID();

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );
$total_tickets   = tribe_get_event_capacity( $post_id );

// only show if there are tickets
if ( empty( $total_tickets ) ) {
	return;
}
$post = get_post( $post_id );

$args = [
	'post_type' => $post->post_type,
	// by default try to show PayPal tickets orders
	'page' => 'tpp-orders',
	'event_id' => $post->ID,
];
$url = add_query_arg( $args, admin_url( 'edit.php' ) );

/**
 * Filter the Attendee Report Url
 *
 * @since TDB
 *
 * @param string $url  a url to attendee report
 * @param int    $post ->ID post id
 */
$url = apply_filters( 'tribe_filter_attendee_order_link', $url, $post->ID );
?>

<?php if ( ! empty( $url ) ) : ?>
	<a
		href="<?php echo esc_url( $url ); ?>"
		class="button-secondary"
	>
		<?php esc_html_e( 'View Orders', 'event-tickets' ); ?>
	</a>
<?php endif;