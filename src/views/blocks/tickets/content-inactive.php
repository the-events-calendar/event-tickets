<?php
/**
 * Block: Tickets
 * Inactive Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/content-inactive.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$message = $this->get( 'sale_past' ) ? esc_html__( 'Tickets are no longer available', 'event-tickets' ) : esc_html__( 'Tickets are not yet available', 'event-tickets' );
?>
<div
	class="tribe-block__tickets__item__content tribe-block__tickets__item__content--inactive"
>
	<?php echo $message ?>
</div>
