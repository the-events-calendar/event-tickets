<?php
/**
 * Block: Tickets
 * Extra column
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/extra.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * If RSVP:
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
 * @var int                                $key                 Ticket item index.
 *
 * If Ticket, some of the above but not all.
 */

$has_suffix = ! empty( $ticket->price_suffix );

$classes = [
	'tribe-tickets__tickets-item-extra',
	'tribe-tickets__tickets-item-extra--price-suffix' => $has_suffix,
];

$context = [
	'ticket'      => $ticket,
	'key'         => $key,
]

?>
<div <?php tribe_classes( $classes ); ?>>

	<?php $this->template( 'v2/tickets/item/extra/price', $context ); ?>

	<?php $this->template( 'v2/tickets/item/extra/available', $context ); ?>

	<?php $this->template( 'v2/tickets/item/extra/description-toggle', $context ); ?>

</div>
