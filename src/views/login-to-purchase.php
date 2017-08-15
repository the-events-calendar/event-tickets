<?php
/**
 * Renders a link displayed to customers when they must first login
 * before being able to purchase tickets.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/login-to-purchase.php
 *
 * @version TBD
 */

$login_url = Tribe__Tickets__Tickets::get_login_url();
?>

<a href="<?php echo esc_attr( $login_url ); ?>"><?php esc_html_e( 'Login to purchase', 'event-tickets-plus' ); ?></a>