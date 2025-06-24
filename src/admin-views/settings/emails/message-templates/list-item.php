<?php
/**
 * Tickets Emails List Item
 *
 * @since 5.5.6  List item of email templates for Emails settings tab.
 *
 * @var array<Email_Abstract>  $emails  Array of email info.
 * @var array<Email_Abstract>    $email   Email info.
 */

use TEC\Tickets\Emails\Email_Abstract;

// If no email, bail.
if ( empty( $email ) ) {
	return;
}

$item_classes   = [
	'tec-tickets__admin-settings-emails-template-list-item',
	'tec-tickets__admin-settings-emails-template-list-item--enabled'  => $email->is_enabled(),
	'tec-tickets__admin-settings-emails-template-list-item--disabled' => ! $email->is_enabled(),
];

?>
<div <?php tribe_classes( $item_classes ); ?> >
	<?php $this->template( 'message-templates/list-icon' ); ?>
	<?php $this->template( 'message-templates/list-title' ); ?>
	<?php $this->template( 'message-templates/list-recipient' ); ?>
	<?php $this->template( 'message-templates/list-action' ); ?>
</div>
