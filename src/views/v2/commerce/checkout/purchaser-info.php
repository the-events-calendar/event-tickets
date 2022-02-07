<?php
/**
 * Tickets Commerce: Checkout Page Header
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/anonymous.php
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
 * @var bool $anonymous [Global] User state.
 */

if ( ! $anonymous ) {
	return;
}

$title = __( 'Purchaser info', 'event-tickets' );
$classes = [];
?>

<div class="tribe-tickets__commerce-checkout-anonymous-purchaser-field-wrapper">
	<p class="tribe-common tribe-field-text"><?php echo esc_html( $title ); ?></p>
	<?php $this->template( 'checkout/anonymous/name' ) ?>
	<?php $this->template( 'checkout/anonymous/email' ) ?>
</div>

