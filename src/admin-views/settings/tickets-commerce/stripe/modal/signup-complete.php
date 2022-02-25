<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Modal after a successful connection.
 *
 * @version 5.3.0
 *
 * @since   5.3.0
 */

$request_vars = tribe_get_request_vars();

// Bail if we're not in the correct context, when Stripe was connected.
if ( empty( $request_vars['tc-status'] ) || 'stripe-signup-complete' !== $request_vars['tc-status'] ) {
	return;
}

$dialog_view = tribe( 'dialog.view' );
$content     = $this->template( 'settings/tickets-commerce/stripe/modal/signup-complete/content', [], false );

$args = [
	'append_target'           => '#stripe-connected-modal-target',
	'button_id'               => 'stripe-connected-modal-button',
	'content_wrapper_classes' => 'tribe-dialog__wrapper tribe-tickets__admin-container event-tickets tribe-common tribe-modal__wrapper--stripe-connected',
	'title'                   => esc_html__( "You are now connected to Stripe! What's next?", 'event-tickets' ),
	'title_classes'           => [
		'tribe-dialog__title',
		'tribe-modal__title',
		'tribe-common-h5',
		'tribe-modal__title--gateway-connected',
	],
];

ob_start();
$dialog_view->render_modal( $content, $args, 'stripe-connected-modal-id' );
$modal_content = ob_get_clean();

$modal  = '<div class="tribe-common event-tickets">';
$modal .= '<span id="stripe-connected-modal-target"></span>';
$modal .= $modal_content;
$modal .= '</div>';

echo $modal; // phpcs:ignore
