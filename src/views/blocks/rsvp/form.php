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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   4.9
 * @since   4.12.0 Add $post_id to filter for hiding opt-outs.
 * @since   TBD Add vars to docblock.
 *
 * @version 4.12.0
 *
 * @var Tribe__Tickets__Editor__Template $template
 * @var Tribe__Tickets__RSVP             $rsvp
 * @var int                              $post_id
 */

$going      = $this->get( 'going' );
$must_login = ! is_user_logged_in() && $rsvp->login_required();
?>
<!-- This div is where the AJAX returns the form -->
<div class="tribe-block__rsvp__form">
	<?php if ( ! empty( $going ) && ! $must_login ) :
		$ticket = $this->get( 'ticket' );
		$args   = [
			'ticket_id' => $ticket->ID,
			'post_id'   => $post_id,
			'ticket'    => $rsvp->get_ticket( get_the_id(), $ticket->ID ),
			'going'     => esc_html( $going ),
		];

		// can't escape, contains html
		echo $template->template( 'blocks/rsvp/form/form', $args, false );
	endif; ?>
</div>
