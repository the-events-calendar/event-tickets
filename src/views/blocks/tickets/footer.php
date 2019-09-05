<?php
/**
 * Block: Tickets
 * Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/footer.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 * @version TBD
 *
 */

// /$ticket = $this->get( 'ticket' );
$modal  = $this->get( 'is_modal' );
?>
<div
	class="tribe-block__tickets__footer"
>
<?php $this->template( 'blocks/tickets/footer-quantity' ); ?>
<?php $this->template( 'blocks/tickets/footer-total' ); ?>
<?php if ( true !== $modal) { ?>
	<?php $this->template( 'blocks/tickets/submit' ); ?>
<?php } ?>
</div>
