<?php
/**
 * Block: RSVP
 * Form Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/submit.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$must_login = ! is_user_logged_in() && tribe( 'tickets.rsvp' )->login_required();
?>
<?php if ( $must_login ) : ?>
	<?php $this->template( 'blocks/rsvp/form/submit-login', array( 'ticket' => $ticket ) ); ?>
<?php else : ?>
	<?php $this->template( 'blocks/rsvp/form/name', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/form/email', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/form/opt-out', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/form/submit-button' ); ?>
<?php endif; ?>
