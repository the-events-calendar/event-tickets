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
 * @since TBD
 *
 * @version TBD
 */

$going = $this->get( 'going' );
?>

<form
	name="tribe-tickets-rsvp-form"
	data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
>
	<input type="hidden" name="product_id[]" value="<?php echo esc_attr( absint( $rsvp->ID ) ); ?>">
	<input type="hidden" name="attendee[order_status]" value="<?php echo esc_attr( $going ); ?>">

	<div class="tribe-tickets__rsvp-form-wrapper">

		<?php $this->template( 'v2/rsvp/form/title', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>

		<div class="tribe-tickets__rsvp-form-content tribe-tickets__form">

			<?php $this->template( 'v2/rsvp/form/error', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>

			<?php $this->template( 'v2/rsvp/form/fields', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>

			<?php $this->template( 'v2/rsvp/form/buttons', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>

		</div>

	</div>

</form>
