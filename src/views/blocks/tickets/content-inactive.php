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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.11.0
 *
 */

$message = $this->get( 'is_sale_past' ) ? __( 'Tickets are no longer available', 'event-tickets' ) : __( 'Tickets are not yet available', 'event-tickets' );
?>
<div
	class="tribe-tickets__item__content tribe-tickets__item__content--inactive"
>
	<?php echo esc_html( $message ) ?>
</div>
