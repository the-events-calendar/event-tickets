<?php
/**
 * Block: Tickets
 * Submit Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit-button.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version 4.9.4
 *
 */
?>
<button
	class="tribe-block__tickets__buy"
	type="submit"
>
	<?php esc_html_e( 'Add to cart', 'event-tickets' ); ?>
</button>
<?php
/* translators: %s is the event or post title the tickets are attached to. */
$title       = sprintf( __( '%s Tickets', 'event-tickets-plus' ), esc_html__( get_the_title() ) );
$button_text = __( 'Get Tickets!', 'event-tickets-plus');
$content     = apply_filters( 'tribe_events_tickets_woo_attendee_registration_modal_content', $this );
//$content     = wp_kses_post( $content );
$args = [
	'button_name'  => 'wootickets_process',
	'button_text'  => $button_text,
	'button_type'  => 'submit',
	'button_value' => '1',
	'title'        => esc_html( $title ),
];

tribe( 'dialog.view' )->render_modal( $content, $args );