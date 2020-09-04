<?php
/**
 * Block: Tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9
 * @since   4.10.8  Updated loading logic for including a renamed template.
 * @since   4.10.10 Removed initial check for tickets.
 * @since   4.12.1  Use tribe_get_ticket_label_plural() for "Tickets" string.
 * @since   TBD Use wrapper with `tribe-common` and `event-tickets`. Receive variables from the backend.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template   $this
 * @var bool                               $has_tickets_on_sale True if the event has any tickets on sale.
 * @var bool                               $is_sale_future      True if tickets are future sale.
 * @var bool                               $is_sale_past        True if tickets are past sale.
 * @var WP_Post|int                        $post_id             The post object or ID.
 * @var Tribe__Tickets__Tickets            $provider            The tickets provider class.
 * @var string                             $provider_id         The tickets provider class name.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets             List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale     List of tickets on sale.
 * @var Tribe__Tickets__Commerce__Currency $currency
 */

// We don't display anything if there is no provider or tickets.
if ( ! $is_sale_future && ( ! $provider || ! $tickets ) ) {
	return false;
}

$cart_classes = [
	'tribe-block',
	'tribe-tickets',
	'tribe-common',
];

/**
 * A flag we can set via filter, e.g. at the end of this method, to ensure this template only shows once.
 *
 * @since 4.5.6
 *
 * @param boolean $already_rendered Whether the order link template has already been rendered.
 *
 * @see Tribe__Tickets__Tickets_View::inject_link_template()
 */
$already_rendered = apply_filters( 'tribe_tickets_order_link_template_already_rendered', false );

// Output order links / view link if we haven't already (for RSVPs).
if ( ! $already_rendered ) {
	$html = $this->template( 'blocks/attendees/order-links', [], false );

	if ( empty( $html ) ) {
		$html = $this->template( 'blocks/attendees/view-link', [], false );
	}

	echo $html;

	add_filter( 'tribe_tickets_order_link_template_already_rendered', '__return_true' );
}
?>
<form
	id="tribe-tickets"
	action="<?php echo esc_url( $provider->get_cart_url() ); ?>"
	<?php tribe_classes( $cart_classes ); ?>
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	autocomplete="off"
	data-provider-id="<?php echo esc_attr( $provider->orm_provider ); ?>"
	novalidate
>
	<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__title">
		<?php echo esc_html( tribe_get_ticket_label_plural( 'event-tickets' ) ); ?>
	</h2>
	<input type="hidden" name="tribe_tickets_saving_attendees" value="1"/>
	<input type="hidden" name="tribe_tickets_ar" value="1"/>
	<input type="hidden" name="tribe_tickets_ar_data" value="" id="tribe_tickets_block_ar_data"/>
	<?php
	$this->template(
		'components/notice',
		[
			'id'              => 'tribe-tickets__notice__tickets-in-cart',
			'notice_classes'  => [
				'tribe-tickets__notice--barred',
				'tribe-tickets__notice--barred-left',
			],

			'content_classes' => [
				'tribe-common-b3',
			],
			'content'         => __( 'The numbers below include tickets for this event already in your cart. Clicking "Get Tickets" will allow you to edit any existing attendee information as well as change ticket quantities.', 'event-tickets' ),
		]
	);

	$this->template(
		'blocks/tickets/commerce/fields',
		[
			'provider'    => $provider,
			'provider_id' => $provider_id,
		]
	);

	if ( $has_tickets_on_sale ) :
		foreach ( $tickets_on_sale as $key => $ticket ) :
			$ticket_symbol = $currency->get_currency_symbol( $ticket->ID, true );

				$this->template(
					'blocks/tickets/item',
					[
						'ticket'          => $ticket,
						'key'             => $key,
						'currency_symbol' => $ticket_symbol,
					]
				);
		endforeach;

		// We're assuming that all the currency is the same here.
		$currency_symbol = $currency->get_currency_symbol( $ticket->ID, true );
		$this->template(
			'blocks/tickets/footer',
			[
				'tickets'         => $tickets,
				'currency_symbol' => $currency_symbol,
			]
		);
	else :
		$this->template( 'blocks/tickets/item-inactive', [ 'is_sale_past' => $is_sale_past ] );
	endif;

	$this->template( 'v2/components/loader/loader' );
	?>

</form>

<div class="tribe-common">
	<span id="tribe-tickets__modal_target"></span>
</div>
