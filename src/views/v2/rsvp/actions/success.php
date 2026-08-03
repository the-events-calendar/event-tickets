<?php
/**
 * Block: RSVP
 * Actions - Success
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions/success.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var bool $show_attendees_list Whether the opt-in toggle should be shown. Filterable via `tec_tickets_rsvp_show_attendees_list`.
 *
 * @since 4.12.3
 * @version 4.12.3
 * @since TBD Only render the toggle when `tec_tickets_rsvp_show_attendees_list` allows it.
 */

?>
<div class="tribe-tickets__rsvp-actions-success">

	<?php $this->template( 'v2/rsvp/actions/success/title' ); ?>

	<?php if ( ! empty( $show_attendees_list ) ) : ?>
		<?php $this->template( 'v2/rsvp/actions/success/toggle' ); ?>
	<?php endif; ?>

</div>
