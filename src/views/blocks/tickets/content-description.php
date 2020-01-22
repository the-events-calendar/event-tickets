<?php
/**
 * Block: Tickets
 * Content Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/content-description.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 * 
 * @since 4.9
 * @since TBD Updated the button to include a type - helps avoid submitting forms unintentionally.
 *
 * @version TBD
 */

$ticket = $this->get( 'ticket' );

if ( ! $ticket->show_description() || empty( $ticket->description ) ) {
	return false;
}

$modal  = $this->get( 'is_modal' );
$id = 'tribe__details__content' . ( true === $modal ) ?: '__modal';
$id .= '--' . $ticket->ID;
?>
<?php if ( true === $modal && $ticket->show_description() && ! empty( $ticket->description ) ) : ?>
		<div class="tribe-tickets__item__details__summary">
			<button
				type="button"
				class="tribe-common-b3 tribe-tickets__item__details__summary--more"
				aria-controls="<?php echo esc_attr( $id ); ?>"
				tabindex="0"
			>
				<span class="screen-reader-text"><?php esc_html_e( 'Open the ticket description.', 'event-tickets' ); ?></span>
				<?php echo esc_html_x( 'More', 'Opens the ticket description', 'event-tickets' ); ?>
			</button>
			<button
				type="button"
				class="tribe-common-b3 tribe-tickets__item__details__summary--less"
				aria-controls="<?php echo esc_attr( $id ); ?>"
				tabindex="0"
			>
				<span class="screen-reader-text"><?php esc_html_e( 'Close the ticket description.', 'event-tickets' ); ?></span>
				<?php echo esc_html_x( 'Less', 'Closes the ticket description', 'event-tickets' ); ?>
			</button>
	</div>
<?php endif; ?>
<div id="<?php echo esc_attr( $id ); ?>" class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__item__details__content">
	<?php echo $ticket->description; ?>
</div>
