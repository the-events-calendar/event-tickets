<?php
/**
 * Modal: Remove Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/modal/item/remove.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $is_modal True if it's in modal context.
 */

// Bail if it's not in modal context.
if ( empty( $is_modal ) ) {
	return;
}

?>
<div
	class="tribe-tickets__item__remove__wrap"
>
	<button
		type="button"
		class="tribe-tickets__item__remove"
	>
	</button>
</div>
