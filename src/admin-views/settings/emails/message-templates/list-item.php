<?php
/**
 * Tickets Emails Message Template List Item
 *
 * @since  5.5.6  List item of email message templates for Emails settings tab.
 *
 * @var Array[]  $templates  Array of template info.
 * @var Array    $template   Template info.
 */

// @todo $templates variable will be an array of Message_Template objects in the future.
// @todo $template variable will be a Message_Template object in the future.

// If no template, bail.
if ( empty( $template ) ) {
	return;
}

$item_classes   = [ 'tec-tickets__admin-settings-emails-template-list-item' ];
$item_classes[] = tribe_is_truthy( $template['enabled'] ) ?
    'tec-tickets__admin-settings-emails-template-list-item--enabled' :
    'tec-tickets__admin-settings-emails-template-list-item--disabled';

?>
<div <?php tribe_classes( $item_classes ); ?> >
	<?php $this->template( 'message-templates/list-icon' ); ?>
	<?php $this->template( 'message-templates/list-title' ); ?>
	<?php $this->template( 'message-templates/list-recipient' ); ?>
	<?php $this->template( 'message-templates/list-action' ); ?>
</div>
