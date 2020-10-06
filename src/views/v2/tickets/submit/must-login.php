<?php
/**
 * Block: Tickets
 * Submit Login
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/submit/login.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $must_login If the login is required to purchase tickets.
 */

if ( empty( $must_login ) ) {
	return;
}

?>
<a class="tribe-common-c-btn tribe-common-c-btn--small" href="<?php echo esc_url( Tribe__Tickets__Tickets::get_login_url() ); ?>">
	<?php echo esc_html_x( 'Log in to purchase', 'login required before purchase', 'event-tickets' ); ?>
</a>
