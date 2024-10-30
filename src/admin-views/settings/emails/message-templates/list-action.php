<?php
/**
 * Tickets Emails List Action
 *
 * @since  5.5.6   Action links for list item of email templates for Emails settings tab.
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
<div class="tec-tickets__admin-settings-emails-template-list-item-action">
	<a href="<?php echo esc_url( $email->get_edit_url() ); ?>" class="tec-tickets__admin-settings-emails-template-list-item-action-link dashicons dashicons-edit"></a>
</div>
