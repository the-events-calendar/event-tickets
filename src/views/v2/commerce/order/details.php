<?php
/**
 * Tickets Commerce: Success Order Page Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/details.php
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
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

?>
<div class="tribe-common-b1 tribe-tickets__commerce-order-details">
	<?php $this->template( 'order/details/order-number' ); ?>
	<?php $this->template( 'order/details/date' ); ?>
	<?php $this->template( 'order/details/email' ); ?>
	<?php $this->template( 'order/details/total' ); ?>
	<?php $this->template( 'order/details/payment-method' ); ?>
</div>
