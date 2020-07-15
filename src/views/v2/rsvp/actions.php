<?php
/**
 * Block: RSVP
 * Actions
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var string|null $step The step the views are on.
 *
 * @since TBD
 * @version TBD
 */

?>
<div class="tribe-tickets__rsvp-actions-wrapper tribe-common-g-col">
	<div class="tribe-tickets__rsvp-actions">

		<?php if ( 'success' === $step ) : ?>

			<?php $this->template( 'v2/rsvp/actions/success', [ 'rsvp' => $rsvp ] ); ?>

		<?php elseif ( ! $rsvp->is_in_stock() ) : ?>

			<?php $this->template( 'v2/rsvp/actions/full', [ 'rsvp' => $rsvp ] ); ?>

		<?php else : ?>

			<?php $this->template( 'v2/rsvp/actions/rsvp', [ 'rsvp' => $rsvp ] ); ?>

		<?php endif; ?>
	</div>

</div>
