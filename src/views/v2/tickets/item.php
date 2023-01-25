<?php
/**
 * Block: Tickets
 * Single Ticket Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.0.3
 * @since 5.1.1 Display data attributes via `tribe_attributes` and make them filterable via `tribe_tickets_block_ticket_html_attributes`.
 * @since 5.1.6 Add the `data-available-count` attribute for each ticket to calculate the shared capacity availability correctly.
 * @since 5.1.9 Add the `data-ticket-price` attribute for each ticket to calculate the price precisely.
 * @since 5.1.10 Cast the `data-ticket-price` attribute value as string to avoid unwanted PHP errors.
 * @since 5.5.6 Add the `tribe-tickets__tickets-item--shared-capacity` wrapper class for tickets having shared capacity.
 *
 * @version 5.5.6
 *
 * If RSVP:
 * @var Tribe__Tickets__Editor__Template   $this                        [Global] Template object.
 * @var null|bool                          $is_modal                    [Global] Whether the modal is enabled.
 * @var int                                $post_id                     [Global] The current Post ID to which tickets are attached.
 * @var Tribe__Tickets__Tickets            $provider                    [Global] The tickets provider class.
 * @var string                             $provider_id                 [Global] The tickets provider class name.
 * @var string                             $cart_url                    [Global] Link to Cart (could be empty).
 * @var Tribe__Tickets__Ticket_Object[]    $tickets                     [Global] List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale             [Global] List of tickets on sale.
 * @var bool                               $has_tickets_on_sale         [Global] True if the event has any tickets on sale.
 * @var bool                               $is_sale_past                [Global] True if tickets' sale dates are all in the past.
 * @var bool                               $is_sale_future              [Global] True if no ticket sale dates have started yet.
 * @var Tribe__Tickets__Commerce__Currency $currency                    [Global] Tribe Currency object.
 * @var Tribe__Tickets__Ticket_Object      $ticket                      The ticket object.
 *
 * If Ticket:
 * @var Tribe__Tickets__Editor__Template   $this                        [Global] Template object.
 * @var int                                $post_id                     [Global] The current Post ID to which tickets are attached.
 * @var Tribe__Tickets__Tickets            $provider                    [Global] The tickets provider class.
 * @var string                             $provider_id                 [Global] The tickets provider class name.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets                     [Global] List of tickets.
 * @var array                              $cart_classes                [Global] CSS classes.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale             [Global] List of tickets on sale.
 * @var bool                               $has_tickets_on_sale         [Global] True if the event has any tickets on sale.
 * @var bool                               $is_sale_past                [Global] True if tickets' sale dates are all in the past.
 * @var bool                               $is_sale_future              [Global] True if no ticket sale dates have started yet.
 * @var Tribe__Tickets__Commerce__Currency $currency                    [Global] Tribe Currency object.
 * @var Tribe__Tickets__Tickets_Handler    $handler                     [Global] Tribe Tickets Handler object.
 * @var Tribe__Tickets__Privacy            $privacy                     [Global] Tribe Tickets Privacy object.
 * @var int                                $threshold                   [Global] The count at which "number of tickets left" message appears.
 * @var bool                               $show_original_price_on_sale [Global] Show original price on sale.
 * @var null|bool                          $is_mini                     [Global] If in "mini cart" context.
 * @var null|bool                          $is_modal                    [Global] Whether the modal is enabled.
 * @var string                             $submit_button_name          [Global] The button name for the tickets block.
 * @var string                             $cart_url                    [Global] Link to Cart (could be empty).
 * @var string                             $checkout_url                [Global] Link to Checkout (could be empty).
 * @var Tribe__Tickets__Ticket_Object      $ticket                      The ticket object.
 * @var int                                $key                         Ticket Item index.
 * @var string                             $data_available              Boolean string.
 * @var bool                               $has_shared_cap              True if ticket has shared capacity.
 * @var string                             $data_has_shared_cap         True text if ticket has shared capacity.
 * @var string                             $currency_symbol             The ticket's currency symbol, e.g. '$'.
 * @var bool                               $show_unlimited              Whether to allow showing of "unlimited".
 * @var int                                $available_count             The quantity of Available tickets based on the Attendees number.
 * @var bool                               $is_unlimited                Whether the ticket has unlimited quantity.
 * @var int                                $max_at_a_time               The maximum quantity able to be purchased in a single Add to Cart action.
 */

if (
	empty( $provider )
	|| $ticket->provider_class !== $provider->class_name
) {
	return false;
}

/* @var Tribe__Tickets__Privacy $privacy */
$privacy = tribe( 'tickets.privacy' );

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$handler = tribe( 'tickets.handler' );

$context = [
	'ticket'        => $ticket,
	'key'           => $this->get( 'key' ),
	'privacy'       => $privacy,
	'max_at_a_time' => $handler->get_ticket_max_purchase( $ticket->ID ),
];

$has_suffix = ! empty( $ticket->price_suffix );

$classes = [
	'tribe-tickets__tickets-item',
	'tribe-tickets__tickets-item--disabled'        => ! empty( $must_login ),
	'tribe-tickets__tickets-item--price-suffix'    => $has_suffix,
	'tribe-tickets__tickets-item--shared-capacity' => $this->get( 'data_has_shared_cap' ),
	get_post_class( '', $ticket->ID ),
];

$ticket_item_id  = 'tribe-';
$ticket_item_id .= ! empty( $is_modal ) ? 'modal' : 'block';
$ticket_item_id .= '-tickets-item-' . $ticket->ID;

// ET has this set from global context but ETP does not.
$has_shared_cap = isset( $has_shared_cap ) ? $has_shared_cap : $this->get( 'has_shared_cap' );

$attributes = [
	'data-ticket-id'      => (string) $ticket->ID,
	'data-available'      => $this->get( 'data_available' ),
	'data-has-shared-cap' => $this->get( 'data_has_shared_cap' ),
	'data-ticket-price'   => (string) $ticket->price,
];

if ( $has_shared_cap ) {
	$attributes['data-shared-cap']      = get_post_meta( $post_id, $handler->key_capacity, true );
	$attributes['data-available-count'] = (string) $available_count;
}

/**
 * Filter the ticket data attributes.
 *
 * @since 5.1.1
 *
 * @param array $attributes A list of data attributes with their values.
 * @param Tribe__Tickets__Ticket_Object $ticket The ticket object.
 */
$attributes = apply_filters( 'tribe_tickets_block_ticket_html_attributes', $attributes, $ticket );
?>
<div
	id="<?php echo esc_attr( $ticket_item_id ); ?>"
	<?php tribe_classes( $classes ); ?>
	<?php tribe_attributes( $attributes ); ?>
>

	<?php $this->template( 'v2/tickets/item/content', $context ); ?>

	<?php $this->template( 'v2/tickets/item/quantity', $context ); ?>

	<?php $this->template( 'v2/tickets/item/quantity-mini', $context ); ?>

	<?php $this->template( 'v2/tickets/item/opt-out', $context ); ?>

</div>
