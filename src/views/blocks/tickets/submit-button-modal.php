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
$title       = sprintf( _x( '%s Tickets', 'Modal title. %s: event name', 'event-tickets' ), esc_html__( get_the_title() ) );
$button_text = _x( 'Get Tickets!', 'Get selected tickets.', 'event-tickets');
$content     = apply_filters( 'tribe_events_tickets_edd_attendee_registration_modal_content', '<p>Tickets modal needs content, badly.</p>' );
$content     = wp_kses_post( $content );
$args = [
	'button_type'    => 'submit',
	'button_classes' => [ 'tribe-common-c-btn--small tribe-block__tickets__submit' ],
	'button_name'    => 'edd-submit',
	'button_text'    => $button_text,
	'title'          => $title,
];

tribe( 'dialog.view' )->render_modal( $content, $args );
