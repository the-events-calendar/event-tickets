<?php
/**
 * Tickets Commerce: Checkout Page Gateways.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/gateways.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.3.0
 *
 * @version 5.3.0
 *
 * @var \Tribe__Template   $this               [Global] Template object.
 * @var Module             $provider           [Global] The tickets provider instance.
 * @var string             $provider_id        [Global] The tickets provider class name.
 * @var array[]            $items              [Global] List of Items on the cart to be checked out.
 * @var bool               $must_login         [Global] Whether login is required to buy tickets or not.
 * @var string             $login_url          [Global] The site's login URL.
 * @var string             $registration_url   [Global] The site's registration URL.
 * @var bool               $is_tec_active      [Global] Whether `The Events Calendar` is active or not.
 * @var Abstract_Gateway[] $gateways           [Global] An array with the gateways.
 * @var int                $gateways_active    [Global] The number of active gateways.
 * @var int                $gateways_connected [Global] The number of connected gateways.
 */

// Bail if the cart is empty or if there are no active gateways.
if ( empty( $items ) || ! tribe_is_truthy( $gateways_active ) ) {
	return;
}

// Bail if user needs to login, but is not logged in.
if ( $must_login && ! is_user_logged_in() ) {
	return;
}

?>
<div class="tribe-tickets__commerce-checkout-gateways">
	<h4 class="tribe-common-h5 tribe-tickets__commerce-checkout-section-header tribe-common-a11y-hidden">
		<?php esc_html_e( 'Payment info', 'event-tickets' ); ?>
	</h4>
	<?php
	foreach ( $gateways as $gateway ) {
		if ( ! $gateway::is_enabled() || ! $gateway::is_active() ) {
			continue;
		}
		$gateway->render_checkout_template( $this );
	}
	?>
</div>
