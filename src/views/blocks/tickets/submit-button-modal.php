<?php
/**
 * Block: Tickets
 * Submit Button - Modal
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit-button-modal.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 * @version TBD
 *
 */

/* translators: %s is the event or post title the tickets are attached to. */
$title       = sprintf( __( '%s Tickets', 'event-tickets-plus' ), esc_html__( get_the_title() ) );
$button_text = esc_html__( 'Get Tickets!', 'event-tickets-plus');
$content     = apply_filters( 'tribe_events_tickets_edd_attendee_registration_modal_content', '<p>EDD Tickets modal needs content, badly.</p>' );
$content     = wp_kses_post( $content );
$args = [
	'button_type'  => 'submit',
	'button_name'  => 'edd-submit',
	'button_text'  => $button_text,
	'title'        => esc_html( $title ),
];

tribe( 'dialog.view' )->render_modal( $content, $args );
