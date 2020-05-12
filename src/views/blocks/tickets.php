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
 * @since   4.10.8 Updated loading logic for including a renamed template.
 * @since   4.10.10 Removed initial check for tickets.
 *
 * @version 4.11.3
 *
 * @var Tribe__Tickets__Editor__Template $this
 */

/** @var Tribe__Tickets__Commerce__Currency $currency */
$currency            = tribe( 'tickets.commerce.currency' );
$cart_classes        = [ 'tribe-block', 'tribe-tickets', 'tribe-common' ];
$has_tickets_on_sale = $this->get( 'has_tickets_on_sale' );
$is_sale_future      = $this->get( 'is_sale_future' );
$is_sale_past        = $this->get( 'is_sale_past' );
$post_id             = $this->get( 'post_id' );
$provider            = $this->get( 'provider' );
$provider_id         = $this->get( 'provider_id' );
$tickets             = $this->get( 'tickets', [] );
$tickets_on_sale     = $this->get( 'tickets_on_sale' );

$event_tickets       = $provider->get_tickets( $post_id );

// We don't display anything if there is no provider or tickets.
if ( ! $is_sale_future && ( ! $provider || ! $event_tickets ) ) {
	return false;
}

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
	<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__title"><?php echo esc_html( tribe_get_ticket_label_plural( 'event-tickets' ) ); ?></h2>
	<input type="hidden" name="tribe_tickets_saving_attendees" value="1"/>
	<input type="hidden" name="tribe_tickets_ar" value="1"/>
	<input type="hidden" name="tribe_tickets_ar_data" value="" id="tribe_tickets_block_ar_data"/>
	<?php $this->template(
		'components/notice',
		[
			'id' => 'tribe-tickets__notice__tickets-in-cart',
			'notice_classes' => [
				'tribe-tickets__notice--barred',
				'tribe-tickets__notice--barred-left',
			],

			'content_classes' => [
				'tribe-common-b3',
			],
			'content' => __( 'The numbers below include tickets for this event already in your cart. Clicking "Get Tickets" will allow you to edit any existing attendee information as well as change ticket quantities.', 'event-tickets' )
		]
	); ?>

	<?php $this->template( 'blocks/tickets/commerce/fields', [ 'provider' => $provider, 'provider_id' => $provider_id ] ); ?>
	<?php if ( $has_tickets_on_sale ) : ?>
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
			<?php $ticket_symbol = $currency->get_currency_symbol( $ticket->ID, true ); ?>
			<?php $this->template( 'blocks/tickets/item', [ 'ticket' => $ticket, 'key' => $key, 'currency_symbol' => $ticket_symbol ] ); ?>
		<?php endforeach; ?>
		<?php
		// We're assuming that all the currency is the same here.
		$currency_symbol     = $currency->get_currency_symbol( $ticket->ID, true );
		$this->template( 'blocks/tickets/footer', [ 'tickets' => $tickets, 'currency_symbol' => $currency_symbol ] );
		?>
	<?php else : ?>
		<?php $this->template( 'blocks/tickets/item-inactive', [ 'is_sale_past' => $is_sale_past ] ); ?>
	<?php endif; ?>
	<?php
		ob_start();
		/**
		 * Allows filtering of extra classes used on the tickets-block loader
		 *
		 * @since  4.11.0
		 *
		 * @param  array $classes The array of classes that will be filtered.
		 */
		$loader_classes = apply_filters( 'tribe_tickets_block_loader_classes', [ 'tribe-tickets-loader__tickets-block' ] );
		include Tribe__Tickets__Templates::get_template_hierarchy( 'components/loader.php' );
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
	?>
</form>
<div class="tribe-common">
	<span id="tribe-tickets__modal_target"></span>
</div>
