<?php
/**
 * Block: Tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since   TBD
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
 * @var bool                               $is_mini             True if it's in mini cart context.
 * @var bool                               $is_modal            True if it's in modal context.
 */

// We don't display anything if there is no provider or tickets.
if ( ! $is_sale_future && ( ! $provider || ! $tickets ) ) {
	return false;
}

$classes = [
	'tribe-common',
	'event-tickets',
];

?>
<div <?php tribe_classes( $classes ); ?>>
	<form
		id="tribe-tickets"
		action="<?php echo esc_url( $provider->get_cart_url() ); ?>"
		class="tribe-tickets"
		class="tribe-tickets"
		method="post"
		enctype='multipart/form-data'
		data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
		autocomplete="off"
		data-provider-id="<?php echo esc_attr( $provider->orm_provider ); ?>"
		novalidate
	>

		<input type="hidden" name="tribe_tickets_saving_attendees" value="1"/>
		<input type="hidden" name="tribe_tickets_ar" value="1"/>
		<input type="hidden" name="tribe_tickets_ar_data" value="" id="tribe_tickets_block_ar_data"/>

		<?php $this->template( 'v2/tickets/commerce/fields' ); ?>

		<?php $this->template( 'v2/tickets/title' ); ?>

		<?php $this->template( 'v2/tickets/notice' ); ?>

		<?php $this->template( 'v2/tickets/items' ); ?>

		<?php $this->template( 'v2/tickets/footer' ); ?>

		<?php $this->template( 'v2/tickets/item/inactive' ); ?>

		<?php $this->template( 'v2/components/loader/loader' ); ?>

	</form>

	// @todo Convert this into an action.
	<?php // $this->template( 'v2/modal/target' ); ?>

</div>
