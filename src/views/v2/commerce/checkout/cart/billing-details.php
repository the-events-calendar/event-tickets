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
$user       = wp_get_current_user();
$username   = ! empty( $user->display_name ) ? $user->display_name : '';
$name_parts = explode( ' ', $username );
$first_name = array_shift( $name_parts );
$last_name  = implode( ' ', $name_parts);
$useremail  = ! empty( $user->user_email ) ? $user->user_email : '';

?>

<div class="tribe-tickets__commerce-checkout-purchaser">
	<h4 class="tribe-tickets__commerce-checkout-section-header">
		<?php esc_html_e( 'Purchaser Info', 'event-tickets' ); ?>
	</h4>
	<div class="tribe-tickets__commerce-checkout-field-group">
		<label 
			class="tribe-tickets__commerce-checkout-field-group-label" 
			for="tec-tc-gateway-stripe-billing-name-input">
			<?php esc_html_e( 'Person purchasing tickets:', 'event-tickets' ); ?>
		</label>
		<input 
			class="tribe-tickets__commerce-checkout-field-group-input"
			id="tec-tc-gateway-stripe-billing-name-input"
			type="text" value="<?php echo esc_attr( $username ); ?>" />
	</div>
	<div class="tribe-tickets__commerce-checkout-field-group">
		<label 
			class="tribe-tickets__commerce-checkout-field-group-label" 
			for="tec-tc-gateway-stripe-billing-email-input">
			<?php esc_html_e( 'Email address:', 'event-tickets' ); ?>
		</label>
		<input 
			class="tribe-tickets__commerce-checkout-field-group-input"
			id="tec-tc-gateway-stripe-billing-email-input"
			type="email" 
			value="<?php echo esc_attr( $useremail ); ?>" />
		<div class="tribe-tickets__commerce-checkout-field-group-help">
			<?php esc_html_e( 'Your tickets will be sent to this address.', 'event-tickets' ); ?>
		</div>
	</div>
</div>
