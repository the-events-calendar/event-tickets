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
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this [Global] Template object.
 * @var bool $is_logged_out [Global] If the user is logged out.
 * @var bool $must_login Global] Whether login is required to buy tickets or not.
 */

if ( ! $is_logged_out || $must_login ) {
	return;
}

$title = __( 'Purchaser info', 'event-tickets' );
?>

<div class="tribe-tickets__form tribe-tickets__commerce-checkout-purchaser-info-field-wrapper">
	<p class="tribe-common tribe-field-text"><?php echo esc_html( $title ); ?></p>
	<?php $this->template( 'checkout/purchaser-info/name' ) ?>
	<?php $this->template( 'checkout/purchaser-info/email' ) ?>
</div>
