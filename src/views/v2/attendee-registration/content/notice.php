<?php
/**
 * Attendee registration
 * Notice
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content/notice.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 */

// @todo @juanfra: componetize v2 for the notice.
$this->template(
	'components/notice',
	[
		'id'             => 'tribe-tickets__notice__attendee-registration',
		'notice_classes' => [
			'tribe-tickets__notice--error',
			'tribe-tickets__validation-notice',
		],
		'content'        => sprintf(
			// Translators: %s HTML wrapped number of tickets.
			esc_html_x(
				'You have %s ticket(s) with a field that requires information.',
				'Note about missing required fields, %s is the html-wrapped number of tickets.',
				'event-tickets'
			),
			'<span class="tribe-tickets__notice--error__count">1</span>'
		),
	]
);
