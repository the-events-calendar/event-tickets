<?php
/**
 * Tickets Commerce: Billing Identification Fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/billing_identification.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 * @var bool $must_login [Global] Whether login is required to buy tickets or not.
 */

if ( $must_login ) {
	return;
}

$user      = wp_get_current_user();
$username  = '';
$useremail = '';

if ( ! empty( $user ) ) {
	$username  = $user->display_name;
	$useremail = $user->user_email;
}

?>

<div id="tec-tc-gateway-stripe-billing-identification">
	<h4><?php esc_html_e( 'Billing Information', 'event-tickets' ); ?></h4>
	<span id="tec-tc-gateway-stripe-billing-first-name">
		<input type="text" value="<?php echo esc_attr( $username ); ?>" placeholder="First Name"/>
	</span>
	<span id="tec-tc-gateway-stripe-billing-last-name">
		<input type="text" value="<?php echo esc_attr( $username ); ?>" placeholder="Last Name"/>
	</span>
	<span id="tec-tc-gateway-stripe-billing-email">
		<input type="email" value="<?php echo esc_attr( $useremail ); ?>" placeholder="Billing Email"/>
	</span>
</div>
