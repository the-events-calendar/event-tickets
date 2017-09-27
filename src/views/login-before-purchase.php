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

$login_url          = Tribe__Tickets__Tickets::get_login_url();
$users_can_register = (bool) get_option( 'users_can_register' );
if ( ! $users_can_register ) {
	$login_message = _x( 'Login before purchasing', 'Login link on Tribe Commerce checkout page', 'event-tickets-plus' );
} else {
	$login_message = _x( 'Login', 'Login link on Tribe Commerce checkout page, shown as an alternative to the registration link', 'event-tickets-plus' );
}
$register_message = _x( 'register', 'Registration link on Tribe Commerce checkout page, shown as an alternative the login link', 'event-tickets-plus' );
?>

<a href="<?php echo esc_attr( $login_url ); ?>"><?php echo esc_html( $login_message ); ?></a>

<?php if ( $users_can_register ) : ?>
	or <a href="<?php echo esc_attr( $login_url ); ?>"><?php echo esc_html( $register_message ); ?></a> before purchasing
<?php endif ?>
