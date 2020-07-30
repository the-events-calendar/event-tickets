<?php
/**
 * Block: RSVP ARi
 * Form Error
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/error.php
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
		<p><?php esc_html_e( 'Please fill in required information before proceeding', 'event-tickets' ); ?></p>
	</span>
</div>
