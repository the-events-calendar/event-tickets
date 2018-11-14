<?php
/**
 * This template renders the RSVP ticket description
 *
 * @version 0.3.0-alpha
 *
 */
if ( ! $ticket->show_description() ) {
	return;
}
?>
<div class="tribe-block__rsvp__description">
	<?php echo wpautop( esc_html( $ticket->description ) ); ?>
</div>