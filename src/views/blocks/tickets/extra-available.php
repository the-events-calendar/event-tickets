<?php
/**
 * Block: Tickets
 * Extra column, available
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version 4.11
 *
 */

$ticket    = $this->get( 'ticket' );
$available = $ticket->available();
if ( -1 === $available ) {
	return;
}
?>
<div
	class="tribe-common-b3 tribe-tickets__item__extra__available"
>
	<?php if ( -1 !== $ticket->available() ) : ?>
		<?php $this->template( 'blocks/tickets/extra-available-quantity', [ 'ticket' => $ticket ] ); ?>
	<?php endif; ?>
</div>
