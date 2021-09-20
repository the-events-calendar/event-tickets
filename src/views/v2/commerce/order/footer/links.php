<?php
/**
 * Tickets Commerce: Success Order Page Footer Links
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/footer/links.php
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
<div class="tribe-common-b2 tribe-tickets__commerce-order-footer-links">
	<?php $this->template( 'order/footer/links/browse-events' ); ?>
	<?php $this->template( 'order/footer/links/back-home' ); ?>
</div>
