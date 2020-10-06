<?php
/**
 * Modal: Attendee Registration > Notice > Error.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/modal/attendee-registration/notice/error.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 */

$notice_classes = [
	'tribe-tickets__notice--error',
	'tribe-tickets__validation-notice',
];

$this->template(
	'components/notice',
	[
		'id'             => 'tribe-tickets__notice__attendee-modal',
		'notice_classes' => $notice_classes,
		'content'        => sprintf(
			// Translators: %s: The HTML wrapped number of tickets.
			esc_html_x(
				'You have %s ticket(s) with a field that requires information.',
				'Note about missing required fields, %s is the html-wrapped number of tickets.',
				'event-tickets'
			),
			'<span class="tribe-tickets__notice--error__count">1</span>'
		),
	]
);
