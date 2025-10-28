<?php
/**
 * RSVP Block Frontend Rendering
 *
 * @since TBD
 *
 * @param array    $attributes The array of attributes for this block.
 * @param string   $content    Rendered block output. ie. <InnerBlocks.Content />.
 * @param WP_Block $block      The instance of the WP_Block class that represents the block being rendered.
 */

use TEC\Tickets\Commerce\RSVP\Constants;
use TEC\Tickets\Commerce\Module;

// Exit early if no RSVP ID is set.
if ( empty( $attributes['rsvpId'] ) ) {
	return '';
}

// Get the RSVP ticket ID.
$rsvp_id = $attributes['rsvpId'];

// Get the current post.
global $post;
if ( ! $post ) {
	return '';
}

// Get the Commerce module instance to retrieve the ticket.
$provider = Module::get_instance();
if ( ! $provider ) {
	return '';
}

// Get the RSVP ticket object.
$rsvp = $provider->get_ticket( $post->ID, $rsvp_id );
if ( ! $rsvp || ! $rsvp instanceof \Tribe__Tickets__Ticket_Object ) {
	return '';
}

// Check if login is required for RSVP.
$requirements = (array) tribe_get_option( 'ticket-authentication-requirements', [] );
$must_login   = ! is_user_logged_in() && in_array( 'event-tickets_rsvp', $requirements, true );

// Get the login URL with redirect back to the current page.
$login_url = wp_login_url( get_permalink( $post->ID ) );

// Create the RSVP template args (matching the Classic Editor approach).
$rsvp_template_args = [
	'rsvp'          => $rsvp,
	'post_id'       => $post->ID,
	'block_html_id' => Constants::TC_RSVP_TYPE . uniqid(),
	'step'          => '',
	'active_rsvps'  => $rsvp && $rsvp->date_in_range() ? [ $rsvp ] : [],
	'must_login'    => $must_login,
	'login_url'     => $login_url,
];

// Get the template instance.
$template = tribe( 'tickets.editor.template' );
if ( ! $template ) {
	return '';
}

// Render the RSVP template using the same template as Classic Editor.
$template->template(
	'v2/commerce/rsvp',
	$rsvp_template_args,
);
