<?php
/**
 * Tickets Emails List Item
 *
 * @since  5.5.6  List item of email templates for Emails settings tab.
 *
 * @var Email_Abstract[]  $emails  Array of email info.
 * @var Email_Abstract    $email   Email info.
 */

// If no email, bail.
if ( empty( $email ) ) {
	return;
}

$item_classes   = [ 'tec-tickets__admin-settings-emails-template-list-item' ];
$item_classes[] = $email->is_enabled() ?
    'tec-tickets__admin-settings-emails-template-list-item--enabled' :
    'tec-tickets__admin-settings-emails-template-list-item--disabled';

?>
<div <?php tribe_classes( $item_classes ); ?> >
	<?php $this->template( 'message-templates/list-icon' ); ?>
	<?php $this->template( 'message-templates/list-title' ); ?>
	<?php $this->template( 'message-templates/list-recipient' ); ?>
	<?php $this->template( 'message-templates/list-action' ); ?>
</div>
