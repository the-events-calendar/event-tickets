<?php
/**
 * This template renders the RSVP ticket form login link
 *
 * @version TBD
 *
 */
?>
<a href="<?php echo esc_url( tribe( 'tickets' )->get_login_url() ); ?>">
	<?php esc_html_e( 'Login to RSVP', 'events-gutenberg' ); ?>
</a>