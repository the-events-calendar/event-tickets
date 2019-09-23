<?php
/**
 * Block: RSVP
 * Messages Sucess
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/messages/success.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @since TBD Use function for text.
 *
 * @version TBD
 */

?>
<div class="tribe-block__rsvp__message__success">

	<?php echo esc_html( sprintf( _x( 'Your %1$s has been received! Check your email for your %1$s confirmation.', 'blocks rsvp messages success', 'event-tickets' ), tribe_get_rsvp_label_singular( 'blocks_rsvp_messages_success' ) ) ); ?>

</div>
