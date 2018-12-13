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
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$availability_past = $this->get( 'availability_past' );

$classes = array(
	'tribe-block__tickets__item',
	'tribe-block__tickets__item--inactive',
);

$context = array(
	'availability_past' => $availability_past,
);
?>
<div class="<?php echo implode( ' ', get_post_class( $classes ) ); ?>">
	<?php $this->template( 'blocks/tickets/icon', $context ); ?>
	<?php $this->template( 'blocks/tickets/content-inactive', $context ); ?>
</div>
