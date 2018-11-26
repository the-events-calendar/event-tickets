<?php
/**
 * Block: RSVP
 * Form Submit Login
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/submit-login.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */

?>
<a href="<?php echo esc_url( tribe( 'tickets' )->get_login_url() ); ?>">
	<?php esc_html_e( 'Login to RSVP', 'events-gutenberg' ); ?>
</a>