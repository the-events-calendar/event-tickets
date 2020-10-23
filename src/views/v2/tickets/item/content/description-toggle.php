<?php
/**
 * Block: Tickets
 * Content Description Toggle.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/content/description-toggle.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket The Ticket Object
 * @var bool $is_modal                        True if it's in modal context.
 */

if (
	empty( $is_modal )
	|| ! $ticket->show_description()
	|| empty( $ticket->description )
) {
	return;
}

$ticket_details_id  = 'tribe__details__content' . ( true === $is_modal ) ?: '__modal';
$ticket_details_id .= '--' . $ticket->ID;
?>
<div class="tribe-tickets__tickets-item-details-summary">
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--more"
		aria-controls="<?php echo esc_attr( $ticket_details_id ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php esc_html_e( 'Open the ticket description.', 'event-tickets' ); ?></span>
		<?php echo esc_html_x( 'More', 'Opens the ticket description', 'event-tickets' ); ?>
	</button>
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--less"
		aria-controls="<?php echo esc_attr( $ticket_details_id ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php esc_html_e( 'Close the ticket description.', 'event-tickets' ); ?></span>
		<?php echo esc_html_x( 'Less', 'Closes the ticket description', 'event-tickets' ); ?>
	</button>
</div>
