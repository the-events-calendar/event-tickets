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
$title       = sprintf( __( '%s Tickets', 'event-tickets' ), esc_html__( get_the_title() ) );
$button_text = esc_html__( 'Get Tickets!', 'event-tickets');

/**
 * Filter Modal Content
 *
 * @since TBD
 *
 * @param string $content a string of default content
 * @param Tribe__Tickets__Editor__Template $template_obj the Template objec
 *
 */
$content     = apply_filters( 'tribe_events_tickets_attendee_registration_modal_content', '<p>Ticket Modal</p>', $this );
$args = [
	'button_name' => $provider_id . '_get_tickets',
	'button_text' => $button_text,
	'button_type' => 'submit',
	'show_event'  => 'tribe_dialog_show_ar_modal',
	'title'       => esc_html( $title ),
];

tribe( 'dialog.view' )->render_modal( $content, $args );
