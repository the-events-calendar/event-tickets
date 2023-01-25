<?php
/**
 * Tickets Emails Message Template List Action
 *
 * @since  5.5.6   Action links for list item of email message templates for Emails settings tab.
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

?>
<div class="tec-tickets__admin-settings-emails-template-list-item-action">
	<a href="#" class="tec-tickets__admin-settings-emails-template-list-item-action-link dashicons dashicons-edit"></a>
</div>
