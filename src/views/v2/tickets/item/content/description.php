<?php
/**
 * Block: Tickets
 * Content Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/content/description.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket   Ticket Object
 * @var bool                          $is_mini  True if it's in mini cart context.
 * @var bool                          $is_modal True if it's in modal context.
 */

if ( ! $ticket->show_description() || empty( $ticket->description ) ) {
	return false;
}

// Bail if it's mini.
if ( ! empty( $is_mini ) ) {
	return;
}

$ticket_details_id  = 'tribe__details__content' . ( true === $is_modal ) ?: '__modal';
$ticket_details_id .= '--' . $ticket->ID;
?>

<?php $this->template( 'v2/tickets/item/content/description-toggle', [ 'ticket' => $ticket ] ); ?>

<div
	id="<?php echo esc_attr( $ticket_details_id ); ?>"
	class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__item__details__content"
>
	<?php echo esc_html( $ticket->description ); ?>
</div>
