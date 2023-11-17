<?php
/**
 * Tickets Commerce: Success Order Page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/success.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.1.10
 * @since   5.7.0 Added attendee information list for the purchase.
 *
 * @version 5.7.0
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 * @var array            $attendees             [Global] List of attendees for the given order.
 */

?>
<div class="tribe-common event-tickets">
	<section class="tribe-tickets__commerce-order">
		<?php $this->template( 'order/header' ); ?>
		<?php $this->template( 'order/description' ); ?>
		<?php $this->template( 'order/details' ); ?>
		<?php tribe( 'tickets.editor.template' )->template( 'components/attendees-list/attendees', $this->get_global_values(), true ); ?>
		<?php $this->template( 'order/footer' ); ?>
	</section>
</div>
