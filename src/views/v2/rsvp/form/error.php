<?php
/**
 * Block: RSVP
 * Form Error
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/form/error.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<div class="tribe-tickets__form-message tribe-tickets__form-message--error tribe-common-b3 tribe-common-a11y-hidden">
	<?php $this->template( 'v2/components/icons/error', [ 'classes' => [ 'tribe-tickets__form-message--error-icon' ] ] ); ?>
	<span class="tribe-tickets__form-message-text">
		<strong>
			<?php esc_html_e( 'Whoops', 'event-tickets' ); ?>
		</strong>
		<p><?php esc_html_e( 'There is a field that requires information.', 'event-tickets' ); ?></p>
	</span>
</div>
