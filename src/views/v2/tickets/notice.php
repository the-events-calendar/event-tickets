<?php
/**
 * Block: Tickets
 * Notice
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/notice.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 */

$this->template(
	'components/notice',
	[
		'id'              => 'tribe-tickets__notice__tickets-in-cart',
		'notice_classes'  => [
			'tribe-tickets__notice--barred',
			'tribe-tickets__notice--barred-left',
		],
		'content_classes' => [
			'tribe-common-b3',
		],
		'content'         => __( 'The numbers below include tickets for this event already in your cart. Clicking "Get Tickets" will allow you to edit any existing attendee information as well as change ticket quantities.', 'event-tickets' ),
	]
);
