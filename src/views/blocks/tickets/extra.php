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

$modal = $this->get( 'is_modal' );
$id = 'tribe__details__content--' . $ticket->ID;
?>
<div class="tribe-block__tickets__item__extra">
	<?php $this->template( 'blocks/tickets/extra-price', $context ); ?>
	<?php $this->template( 'blocks/tickets/extra-available', $context ); ?>
	<?php if ( true !== $modal && $ticket->show_description() && ! empty( $ticket->description ) ) : ?>
		<div class="tribe-block__tickets__item__details__summary">
			<button
				class="tribe-common-b3 tribe-block__tickets__item__details__summary--more"
				aria-controls="<?php echo esc_attr( $id ); ?>"
				tabindex="0"
			>
				<span class="screen-reader-text"><?php esc_html_e( 'Open the ticket description.', 'event-tickets' ); ?></span>
				<?php echo esc_html_x( 'More', 'Opens the ticket description', 'event-tickets' ); ?>
			</button>
			<button
				class="tribe-common-b3 tribe-block__tickets__item__details__summary--less"
				aria-controls="<?php echo esc_attr( $id ); ?>"
				tabindex="0"
			>
				<span class="screen-reader-text"><?php esc_html_e( 'Close the ticket description.', 'event-tickets' ); ?></span>
				<?php echo esc_html_x( 'Less', 'Closes the ticket description', 'event-tickets' ); ?>
			</button>
	</div>
	<?php endif; ?>
</div>
