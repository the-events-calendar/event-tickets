<?php
/**
 * Tickets Commerce: Checkout Page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout.php
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
 * @var array[]          $sections              [Global] Which events we have tickets for.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways              [Global] An array with the gateways.
 * @var int              $gateways_active       [Global] The number of active gateways.
 */

?>
<section class="tribe-common event-tickets tribe-tickets__commerce-checkout">
	<?php $this->template( 'checkout/fields' ); ?>
	<?php $this->template( 'checkout/header' ); ?>
	<?php foreach ( $sections as $section ) : ?>
		<?php $this->template( 'checkout/cart', [ 'section' => $section ] ); ?>
	<?php endforeach; ?>
	<?php tribe( 'tickets.editor.template' )->template( 'v2/components/loader/loader' ); ?>
	<?php $this->template( 'checkout/cart/empty' ); ?>
	<?php $this->template( 'checkout/footer' ); ?>
	<?php $this->template( 'checkout/must-login' ); ?>
</section>
