<?php
/**
 * Tickets Emails Message Template List Icon
 *
 * @since  5.5.6   Icon (checkmark) for list item of email message templates for Emails settings tab.
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

$icon_classes   = [ 'dashicons' ];
$icon_classes[] = tribe_is_truthy( $template['enabled'] ) ? 'dashicons-yes' : '';

?>
<div class="tec-tickets__admin-settings-emails-template-list-item-icon">
	<span <?php tribe_classes( $icon_classes ); ?> ></span>
</div>
