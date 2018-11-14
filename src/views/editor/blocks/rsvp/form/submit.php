<?php
/**
 * This template renders the RSVP ticket form submit input or the login link
 * Depending if the user is logged in or not
 *
 * @version TBD
 *
 */
$must_login = ! is_user_logged_in() && tribe( 'tickets.rsvp' )->login_required();
?>
<?php if ( $must_login ) : ?>
	<?php $this->template( 'editor/blocks/rsvp/form/submit-login' ); ?>
<?php else : ?>
	<?php $this->template( 'editor/blocks/rsvp/form/submit-button' ); ?>
<?php endif; ?>