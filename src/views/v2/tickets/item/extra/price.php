<?php
/**
 * Block: Tickets
 * Extra column, price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/extra/price.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template   $this                Template object.
 * @var null|bool                          $is_modal            [Global] Whether the modal is enabled.
 * @var int                                $post_id             [Global] The current Post ID to which tickets are attached.
 * @var array                              $attributes          [Global] Ticket attributes (could be empty).
 * @var Tribe__Tickets__Tickets            $provider            [Global] The tickets provider class.
 * @var string                             $provider_id         [Global] The tickets provider class name.
 * @var string                             $cart_url            [Global] Link to Cart (could be empty).
 * @var Tribe__Tickets__Ticket_Object[]    $tickets             [Global] List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale     [Global] List of tickets on sale.
 * @var bool                               $has_tickets_on_sale [Global] True if the event has any tickets on sale.
 * @var bool                               $is_sale_past        [Global] True if tickets' sale dates are all in the past.
 * @var bool                               $is_sale_future      [Global] True if no ticket sale dates have started yet.
 * @var Tribe__Tickets__Commerce__Currency $currency            [Global] Tribe Currency object.
 * @var Tribe__Tickets__Ticket_Object      $ticket              The ticket object.
 */

$classes = [
	'tribe-common-b2',
	'tribe-common-b1--min-medium',
	'tribe-tickets__tickets-item-extra-price',
];

$has_suffix = ! empty( $ticket->price_suffix );
?>
<div <?php tribe_classes( $classes ); ?>>
	<?php if ( ! empty( $ticket->on_sale ) ) : ?>
		<span class="tribe-common-b2 tribe-tickets__tickets-original-price">
			<?php echo $currency->get_formatted_currency_with_symbol( $ticket->regular_price, $post_id, $provider->class_name ); ?>
		</span>
	<?php endif; ?>
	<span class="tribe-tickets__tickets-sale-price">
		<?php echo $currency->get_formatted_currency_with_symbol( $ticket->price, $post_id, $provider->class_name ); ?>
		<?php if ( $has_suffix ) : ?>
			<span class="tribe-tickets__tickets-sale-price-suffix tribe-common-b2">
				<?php
				// This suffix contains HTML to be output.
				// phpcs:ignore
				echo $ticket->price_suffix;
				?>
			</span>
		<?php endif; ?>
	</span>
</div>
