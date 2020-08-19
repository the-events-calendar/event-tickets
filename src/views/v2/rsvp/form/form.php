<?php
/**
 * Block: RSVP
 * Form base
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/form/form.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.12.3
 * @since TBD Updated the input name used for submitting.
 *
 * @version TBD
 */

$going = $this->get( 'going' );
?>

<form
	name="tribe-tickets-rsvp-form"
	data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
>
	<input type="hidden" name="tribe_tickets[<?php echo esc_attr( absint( $rsvp->ID ) ); ?>][ticket_id]" value="<?php echo esc_attr( absint( $rsvp->ID ) ); ?>">
	<input type="hidden" name="tribe_tickets[<?php echo esc_attr( absint( $rsvp->ID ) ); ?>][attendees][0][order_status]" value="<?php echo esc_attr( $going ); ?>">
	<input type="hidden" name="tribe_tickets[<?php echo esc_attr( absint( $rsvp->ID ) ); ?>][attendees][0][optout]" value="1">

	<div class="tribe-tickets__rsvp-form-wrapper">

		<?php $this->template( 'v2/rsvp/form/title', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>

		<div class="tribe-tickets__rsvp-form-content tribe-tickets__form">

			<?php $this->template( 'v2/rsvp/form/fields', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>

			<?php $this->template( 'v2/rsvp/form/buttons', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>

		</div>

	</div>

</form>
