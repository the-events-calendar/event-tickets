<?php
/**
 * Renders a link displayed to customers when they can first login
 * before purchasing tickets.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/login-before-purchase.php
 *
 * @version TBD
 */

$login_url = Tribe__Tickets__Tickets::get_login_url();
?>

<a href="<?php echo esc_attr( $login_url ); ?>"><?php esc_html_e( 'Login before purchasing', 'event-tickets-plus' ); ?></a>