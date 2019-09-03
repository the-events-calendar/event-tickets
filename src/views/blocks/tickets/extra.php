<?php
/**
 * Block: Tickets
 * Extra column
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$ticket = $this->get( 'ticket' );

$context = array(
	'ticket' => $ticket,
	'key' => $this->get( 'key' ),
);
?>
<div
	class="tribe-block__tickets__item__extra"
>
	<?php $this->template( 'blocks/tickets/extra-price', $context ); ?>
	<?php $this->template( 'blocks/tickets/extra-available', $context ); ?>
	<?php if ( $ticket->show_description() && ! empty( $ticket->description ) ) { ?>
		<div class="tribe-block__tickets__item__details__summary">
			<div class="tribe-common-b3 tribe-block__tickets__item__details__summary--more" aria-controls="<?php echo esc_attr( 'tribe__details__content--' . $ticket->ID ); ?>" tabindex="0">More</div>
			<div class="tribe-common-b3 tribe-block__tickets__item__details__summary--less" aria-controls="<?php echo esc_attr( 'tribe__details__content--' . $ticket->ID ); ?>" tabindex="0">Less</div>
	</div>
	<?php } ?>
</div>
