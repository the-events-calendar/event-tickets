<?php
/**
 * The Template for displaying the Tickets Commerce Stripe modal notice when connected.
 *
 * @version 5.3.0
 *
 * @since   5.3.0
 */

// Bail if not in sandbox mode.
if ( empty( tec_tickets_commerce_is_sandbox_mode() ) ) {
	return;
}

tribe( 'tickets.editor.template' )->template(
	'components/notice',
	[
		'id'             => 'tec-tickets__admin-settings-tickets-commerce-gateway-modal-notice-error',
		'notice_classes' => [
			'tribe-tickets__notice--error',
			'tec-tickets__admin-settings-tickets-commerce-gateway-modal-notice-error',
		],
		'content'        => __( 'Your connection is active, but Tickets Commerce is set to test mode. While in test mode no live transactions are processed.', 'event-tickets' ),
	]
);
