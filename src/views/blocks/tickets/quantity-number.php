<?php
/**
 * Block: Tickets
 * Quantity Number
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/quantity-number.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$must_login = ! is_user_logged_in() && $ticket->get_provider()->login_required();
$ticket = $this->get( 'ticket' );
$max_quantity = tribe( 'tickets.handler' )->get_ticket_max_purchase( $ticket->ID );

$container_classes = [ 'tribe-block__tickets__item__quantity__number' ];
if ( $must_login ) {
	$container_classes[] = 'tribe-block__tickets__disabled';
}
$container_classes = implode( ' ', $container_classes);
?>
<div
	class="<?php echo esc_attr( $container_classes ); ?>"
>
	<input
		type="number"
		class="tribe-ticket-quantity tribe-common-h3 tribe-common-h4--min-medium"
		step="1"
		min="0"
		<?php if ( -1 !== $max_quantity && $ticket->managing_stock() ) : ?>
			max="<?php echo esc_attr( $max_quantity ); ?>"
		<?php endif; ?>
		name="quantity_<?php echo absint( $ticket->ID ); ?>"
		value="0"
		autocomplete="off"
		<?php disabled( $must_login ); ?>
	/>
</div>
