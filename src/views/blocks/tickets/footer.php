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

$ticket = $this->get( 'ticket' );

$context = array(
	'ticket' => $ticket,
	'key' => $this->get( 'key' ),
);
?>
<div
	class="tribe-block__tickets__footer"
>
<?php $this->template( 'blocks/tickets/footer-quantity', [ 'provider' => $provider, 'provider_id' => $provider_id, 'ticket' => $ticket ] ); ?>
<?php $this->template( 'blocks/tickets/footer-total', [ 'provider' => $provider, 'provider_id' => $provider_id, 'ticket' => $ticket ] ); ?>
<?php $this->template( 'blocks/tickets/submit', [ 'provider' => $provider, 'provider_id' => $provider_id, 'ticket' => $ticket ] ); ?>
</div>
