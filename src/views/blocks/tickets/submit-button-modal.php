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
 * @since 4.11
 *
 * @version 4.11
 *
 */

/* translators: %s is the event or post title the tickets are attached to. */
$title       = sprintf( _x( '%s Tickets', 'Modal title. %s: event name', 'event-tickets' ), get_the_title() );
$button_text = _x( 'Get Tickets', 'Get selected tickets.', 'event-tickets' );

/**
 * Filter Modal Content
 *
 * @since 4.11
 *
 * @param string $content a string of default content
 * @param Tribe__Tickets__Editor__Template $template_obj the Template objec
 */
$content     = apply_filters( 'tribe_events_tickets_attendee_registration_modal_content', '<p>Ticket Modal</p>', $this );

$args = [
	'append_target'           => '#tribe-tickets__modal_target',
	'button_classes'          => [ 'tribe-common-c-btn', 'tribe-common-c-btn--small', 'tribe-tickets__submit' ],
	'button_disabled'         => true,
	'button_name'             => $provider_id . '_get_tickets',
	'button_text'             => $button_text,
	'button_type'             => 'submit',
	'close_event'             => 'tribe_dialog_close_ar_modal',
	'content_wrapper_classes' => 'tribe-dialog__wrapper tribe-modal__wrapper--ar',
	'show_event'              => 'tribe_dialog_show_ar_modal',
	'title'                   => $title,
	'title_classes'           => [
		'tribe-dialog__title',
		'tribe-modal__title',
		'tribe-common-h5',
		'tribe-common-h--alt',
		'tribe-modal--ar__title',
	],
];

tribe( 'dialog.view' )->render_modal( $content, $args );

$event_id = get_the_ID();
/** @var Tribe__Tickets__Editor__Template $template */
$template = tribe( 'tickets.editor.template' );
$tickets  = $this->get( 'tickets' );
$template->template( 'registration-js/attendees/content', array( 'event_id' => $event_id, 'tickets' => $tickets ) );


