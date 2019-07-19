<?php
/**
 * Block: RSVP
 * Form Error
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/error.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version 4.9.4
 *
 */
?>
<div class="tribe-block__rsvp__message__error">

	<?php printf( esc_html__( 'Please fill in the %s confirmation name and email fields.', 'event-tickets' ), tribe_get_rsvp_label_singular( basename( __FILE__ ) ) ); ?>

</div>
