<?php
/**
 * Block: RSVP
 * Form from rsvp/form/form.php via AJAX
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since TBD Add $post_id to filter for hiding opt-outs.
 *
 * @version TBD
 *
 */

$going      = $this->get( 'going' );
$must_login = ! is_user_logged_in() && tribe( 'tickets.rsvp' )->login_required();
?>
<!-- This div is where the AJAX returns the form -->
<div class="tribe-block__rsvp__form">
	<?php if ( ! empty( $going ) && ! $must_login ) :
		$ticket = $this->get( 'ticket' );
		$args = [
			'ticket_id' => $ticket->ID,
			'post_id'   => $post_id,
			'ticket'    => tribe( 'tickets.rsvp' )->get_ticket( get_the_id(), $ticket->ID ),
			'going'     => esc_html( $going ),
		];

		// can't escape, contains html
		echo tribe( 'tickets.editor.template' )->template( 'blocks/rsvp/form/form', $args, false );
	endif; ?>
</div>
