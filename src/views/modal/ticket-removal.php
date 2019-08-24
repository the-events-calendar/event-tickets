<?php
/**
 * Modal: Ticket Total
 * Total column, price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/ticketptotal.php
 *
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 * @version TBD
 *
 */

$ticket = $this->get( 'ticket' );
?>
<div
	class="tribe-block__tickets__item__modal_removal"
>
	<button
		class="tribe-block__tickets__item__remove_btn"
	>
		<?php esc_html_e( 'x', 'event-tickets' ); ?>
	</button>
</div>
