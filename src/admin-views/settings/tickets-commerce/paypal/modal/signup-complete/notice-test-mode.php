<?php
/**
 * The Template for displaying the Tickets Commerce PayPal modal notice when connected.
 *
 * @version 5.3.0
 *
 * @since 5.2.1
 * @since 5.3.0 Using generic CSS classes for gateway instead of PayPal.
 */

defined( 'ABSPATH' ) || exit;

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
		'content'        => __( 'You have connected your account for test mode. You will need to connect again once you are in live mode.', 'event-tickets' ),
	]
);
