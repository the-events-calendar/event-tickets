<?php
/**
 * Tickets Commerce: Checkout Cart Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.1.9
 *
 * @version 5.1.9
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var string           $paypal_attribution_id [Global] What is our PayPal Attribution ID.
 * @var bool             $must_login            [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url             [Global] The site's login URL.
 * @var string           $registration_url      [Global] The site's registration URL.
 * @var bool             $tec_active            [Global] Whether `The Events Calendar` is active or not.
 */

$classes = [
	'tribe-tickets__commerce-checkout-cart-footer',
	'tribe-common-b1',
];
?>
<footer <?php tribe_classes( $classes ); ?>>

	<?php $this->template( 'checkout/cart/footer/quantity' ); ?>

	<?php $this->template( 'checkout/cart/footer/total' ); ?>

</footer>
