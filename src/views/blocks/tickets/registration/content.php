<?php
/**
 * Block: Tickets
 * Registration Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/content.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version TBD
 *
 */

 /**
 * Before the output, whether or not $events is empty.
 *
 * @since TBD
 *
 * @param string $passed_provider       The 'provider' $_REQUEST var.
 * @param string $passed_provider_class The class string or empty string if ticket provider is not found.
 * @param array  $events                The array of events, which might be empty.
 */
do_action( 'tribe_tickets_registration_content_before_all_events', $passed_provider, $passed_provider_class, $events );

?>

<div class="tribe-common tribe-tickets__registration">

	<?php $this->template( 'blocks/tickets/registration/summary/content' ); ?>
	<?php $this->template( 'blocks/tickets/registration/attendee/content' ); ?>

</div>
