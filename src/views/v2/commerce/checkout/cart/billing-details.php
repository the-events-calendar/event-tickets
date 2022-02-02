<?php
/**
 * Tickets Commerce: Billing Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/billing-details.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 * @var bool     $must_login [Global] Whether login is required to buy tickets or not.
 * @var \WP_User $user       The current user logged in, or a non-user w/ ID 0.
 */

if ( $must_login ) {
	return;
}

// @todo We're using $username for both first and last name. Is there a better way?
$username  = ! empty( $user->display_name ) ? $user->display_name : '';
$useremail = ! empty( $user->user_email ) ? $user->user_email : '';

?>

<div id="tec-tc-gateway-stripe-billing-identification">
	<h4><?php esc_html_e( 'Billing Information', 'event-tickets' ); ?></h4>
	<span id="tec-tc-gateway-stripe-billing-first-name">
		<input type="text" value="<?php echo esc_attr( $username ); ?>"
			   placeholder="<?php esc_html_e( 'First Name', 'event-tickets' ); ?>"/>
	</span>
	<span id="tec-tc-gateway-stripe-billing-last-name">
		<input type="text" value="<?php echo esc_attr( $username ); ?>"
			   placeholder="<?php esc_html_e( 'Last Name', 'event-tickets' ); ?>"/>
	</span>
	<span id="tec-tc-gateway-stripe-billing-email">
		<input type="email" value="<?php echo esc_attr( $useremail ); ?>"
			   placeholder="<?php esc_html_e( 'Billing Email', 'event-tickets' ); ?>"/>
	</span>
</div>
