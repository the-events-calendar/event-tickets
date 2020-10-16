<?php
/**
 * Block: RSVP
 * Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/content.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since 4.9
 * @since 4.12.0 Add $post_id to filter for hiding opt-outs.
 *
 * @version 4.12.0
 *
 */
$going = ! empty( $_GET[ 'going' ] ) ? sanitize_text_field( $_GET[ 'going' ] ) : '';
?>
<div class="tribe-block__rsvp__content">

	<div class="tribe-block__rsvp__details__status">
		<?php $this->template( 'blocks/rsvp/details', array( 'ticket' => $ticket ) ); ?>
		<?php $this->template( 'blocks/rsvp/status', array( 'ticket' => $ticket, 'going' => $going ) ); ?>
	</div>

	<?php $this->template( 'blocks/rsvp/form', array( 'ticket' => $ticket, 'going' => $going, 'post_id' => $post_id ) ); ?>

</div>
