<?php
/**
 * Block: Tickets
 * Inactive Ticket Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/item-inactive.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$sale_past = $this->get( 'sale_past' );
$classes   = array(
	'tribe-block__tickets__item',
	'tribe-block__tickets__item--inactive',
);

$context = array(
	'sale_past' => $sale_past,
);
?>
<div <?php tribe_classes( get_post_class( $classes ) ); ?>>
	<?php $this->template( 'blocks/tickets/content-inactive', $context ); ?>
</div>
