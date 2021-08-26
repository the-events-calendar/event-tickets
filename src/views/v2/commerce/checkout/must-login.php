<?php
/**
 * Tickets Commerce: Checkout Page Must Login
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/must-login.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var string           $paypal_attribution_id [Global] What is our PayPal Attribution ID.
 * @var bool             $must_login            [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url             [Global] The site's login URL.
 * @var string           $registration_url      [Global] The site's registration URL.
 */

// Bail if there are no items or if they don't have to log in to complete the purchase.
if ( empty( $items ) || empty( $must_login ) ) {
	return;
}

?>
<div class="tribe-tickets__commerce-checkout-must-login">

	<?php $this->template( 'checkout/must-login/login' ); ?>

	<?php $this->template( 'checkout/must-login/registration' ); ?>

</div>
