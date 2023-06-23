<?php
/**
 * Tickets Emails List Icon
 *
 * @since  5.5.6   Icon (checkmark) for list item of email templates for Emails settings tab.
 *
 * @var array<Email_Abstract> $emails Array of email info.
 * @var Email_Abstract        $email  Email info.
 */

use TEC\Tickets\Emails\Email_Abstract;

// If no email, bail.
if ( empty( $email ) ) {
	return;
}

$icon_classes   = [ 'dashicons' ];
$icon_classes[] = $email->is_enabled() ? 'dashicons-yes' : '';

?>
<div class="tec-tickets__admin-settings-emails-template-list-item-icon">
	<span <?php tribe_classes( $icon_classes ); ?> ></span>
</div>
