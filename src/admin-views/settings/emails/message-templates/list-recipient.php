<?php
/**
 * Tickets Emails List Recipient
 *
 * @since  5.5.6   Recipient for list item of email templates for Emails settings tab.
 *
 * @var array<Email_Abstract> $emails Array of email info.
 * @var Email_Abstract        $email  Email info.
 */

use TEC\Tickets\Emails\Email_Abstract;

// If no email, bail.
if ( empty( $email ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-emails-template-list-item-recipient">
	<?php
	echo sprintf(
	// Translators: %s: The email "to".
		esc_html__( 'To: %s', 'event-tickets' ),
		esc_html( $email->get_to() )
	);
	?>
</div>
