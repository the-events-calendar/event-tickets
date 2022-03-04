<?php
/**
 * Tickets Commerce: Checkout Purchaser Info.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.3.0
 *
 * @version 5.3.0
 *
 * @var \Tribe__Template $this [Global] Template object.
 * @var array[] $items [Global] List of Items on the cart to be checked out.
 * @var bool $must_login Global] Whether login is required to buy tickets or not.
 */

if ( is_user_logged_in() || $must_login || empty( $items ) ) {
	return;
}
?>

<div class="tribe-tickets__form tribe-tickets__commerce-checkout-purchaser-info-wrapper tribe-common-b2">
	<h4 class="tribe-common-h5 tribe-tickets__commerce-checkout-purchaser-info-title"><?php esc_html_e( 'Purchaser info', 'event-tickets' ); ?></h4>
	<?php $this->template( 'checkout/purchaser-info/name' ); ?>
	<?php $this->template( 'checkout/purchaser-info/email' ); ?>
</div>
