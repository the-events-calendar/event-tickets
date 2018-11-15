<?php
/**
 * This template renders the RSVP ticket description
 *
 * @version TBD
 *
 */
if ( ! $ticket->show_description() ) {
	return;
}
?>
<div class="tribe-block__rsvp__description">
	<?php echo wpautop( esc_html( $ticket->description ) ); ?>
</div>