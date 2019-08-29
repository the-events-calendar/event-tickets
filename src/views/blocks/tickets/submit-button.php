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
 *
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
$title       = sprintf( __( '%s Tickets', 'event-tickets' ), esc_html__( get_the_title() ) );
$button_text = __( 'Get Tickets', 'event-tickets');
$content     = apply_filters( 'tribe_events_tickets_attendee_registration_modal_content', '<p>Modal Cart</p>', $this );
$args = [
	'button_name'  => $provider_id . 'tickets_process',
	'button_text'  => $button_text,
	'button_type'  => 'submit',
	'button_value' => '1',
	'title'        => $title,
];

tribe( 'dialog.view' )->render_modal( $content, $args );
