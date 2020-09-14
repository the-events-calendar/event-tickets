<?php
/**
 * Block: Tickets
 * Submit Button - Modal
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/submit/button-modal.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 */

/** @var \Tribe__Tickets__Attendee_Registration__Main $attendee_registration */
$attendee_registration = tribe( 'tickets.attendee_registration' );

if ( ! $attendee_registration->is_modal_enabled() ) {
	return;
}

/* translators: %1$s: Event name, %2$s: Tickets label */
$title = sprintf( _x( '%1$s %2$s', 'Tickets modal title.', 'event-tickets' ), get_the_title(), tribe_get_ticket_label_plural( 'event-tickets' ) );

/* translators: %s: Tickets label */
$button_text = sprintf( _x( 'Get %s', 'Get Tickets button.', 'event-tickets' ), tribe_get_ticket_label_plural( 'event-tickets' ) );

/**
 * Allow filtering of the button classes for the tickets block.
 *
 * @since 4.11.3
 *
 * @param array $button_name The button classes.
 */
$button_classes = apply_filters(
	'tribe_tickets_ticket_block_submit_classes',
	[
		'tribe-common-c-btn',
		'tribe-common-c-btn--small',
		'tribe-tickets__buy',
	]
);

/**
 * Filter Modal Content.
 *
 * @since 4.11.0
 *
 * @param string $content a string of default content.
 * @param Tribe__Tickets__Editor__Template $template_obj the Template object.
 */
$content = apply_filters( 'tribe_events_tickets_attendee_registration_modal_content', '<p>Ticket Modal</p>', $this );

$args = [
	'append_target'           => '#tribe-tickets__modal_target',
	'button_classes'          => $button_classes,
	'button_disabled'         => true,
	'button_id'               => 'tribe-tickets__submit',
	'button_name'             => $provider_id . '_get_tickets',
	'button_id'               => 'tribe-tickets__submit',
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

/** @var Tribe__Tickets__Editor__Template $template */
$template = tribe( 'tickets.editor.template' );
$template->template( 'v2/attendee-registration/attendees/content', [ 'post_id' => get_the_ID(), 'tickets' => $tickets ] );
