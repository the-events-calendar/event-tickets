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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since TBD
 * @version TBD
 */

$toggle_id = 'toggle-rsvp-' . $rsvp->ID;
?>
<div class="tribe-tickets__rsvp-actions-success">
	<div class="tribe-tickets__rsvp-actions-success-going">
		<em class="tribe-tickets__rsvp-actions-success-going-check-icon"></em>
		<span class="tribe-tickets__rsvp-actions-success-going-text tribe-common-h6">
			<?php esc_html_e( 'You are going', 'event-tickets' ); ?>
		</span>
	</div>

	<div class="tribe-tickets__rsvp-actions-success-going-toggle tribe-common-form-control-toggle">
		<input
			class="tribe-common-form-control-toggle__input tribe-tickets__rsvp-actions-success-going-toggle-input"
			id="<?php echo esc_attr( $toggle_id ); ?>"
			name="toggleGroup"
			type="checkbox"
			value="toggleOne"
			data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
		/>
		<label
			class="tribe-common-form-control-toggle__label tribe-tickets__rsvp-actions-success-going-toggle-label"
			for="<?php echo esc_attr( $toggle_id ); ?>"
		>
			<?php esc_html_e( 'Show me on public attendee list', 'event-tickets' ); ?>
		</label>
		<?php // @todo: Implement tooltip here. ?>
	</div>
</div>

