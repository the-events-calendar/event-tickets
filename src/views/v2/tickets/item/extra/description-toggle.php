<?php
/**
 * Block: Tickets
 * Extra column, description toggle.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/extra/price.php
 *
 * See more documentation about our views templating system.
 *
 * @link    http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket   The ticket object.
 * @var bool                          $is_mini  True if it's in mini cart context.
 * @var bool                          $is_modal True if it's in modal context.
 */

if (
	! empty( $is_mini )
	|| ! empty( $is_modal )
	|| ! $ticket->show_description()
	|| empty( $ticket->description )
) {
	return;
}

$toggle_id = 'tribe__details__content--' . $ticket->ID;
?>
<div class="tribe-tickets__item__details__summary">
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__item__details__summary--more"
		aria-controls="<?php echo esc_attr( $toggle_id ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide">
			<?php esc_html_e( 'Open the ticket description.', 'event-tickets' ); ?>
		</span>
		<?php echo esc_html_x( 'More', 'Opens the ticket description', 'event-tickets' ); ?>
	</button>
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__item__details__summary--less"
		aria-controls="<?php echo esc_attr( $toggle_id ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php esc_html_e( 'Close the ticket description.', 'event-tickets' ); ?></span>
		<?php echo esc_html_x( 'Less', 'Closes the ticket description', 'event-tickets' ); ?>
	</button>
</div>
