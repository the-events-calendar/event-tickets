<?php
/**
 * Tickets Commerce: Checkout Cart
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart.php
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
 * @var int              $section               Which Section that we are going to render for this table.
 */

$post = get_post( $section );
?>

<div class="tribe-tickets__commerce-checkout-cart">
	<?php $this->template( 'checkout/cart/header', [ 'post' => $post ] ); ?>

	<div class="tribe-tickets__commerce-checkout-cart-items">
		<?php foreach ( $items as $item ) : ?>
			<?php
			if ( $item['event_id'] !== $section ) {
				continue;
			}
			?>
			<?php $this->template( 'checkout/cart/item', [ 'section' => $section, 'post' => $post, 'item' => $item ] ); ?>
		<?php endforeach; ?>
	</div>

	<?php $this->template( 'checkout/cart/footer' ); ?>

</div>
