<?php
/**
 * This template renders the ticket form submit input or the login link
 * Depending if the user is logged in or not
 *
 * @version 0.3.4-alpha
 *
 */
$must_login = ! is_user_logged_in() && $ticket->get_provider()->login_required();
?>
<?php if ( $must_login ) : ?>
	<?php $this->template( 'editor/blocks/tickets/submit-login' ); ?>
<?php else : ?>
	<?php $this->template( 'editor/blocks/tickets/submit-button' ); ?>
<?php endif; ?>