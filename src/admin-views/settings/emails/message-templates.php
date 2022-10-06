<?php
/**
 * Tickets Emails Message Template List
 *
 * @since  TBD  List of email message templates for Emails settings tab.
 * 
 * @var Array[]  $templates  Array of template info.
 */

// @todo $templates variable will be an array of Message_Template objects in the future.

// If no templates, bail.
if ( empty( $templates ) ) {
	return;
}
// @todo Update template HTML.
?>
<div class="tec_tickets-emails-template-list">
	<?
	foreach ( $templates as $email_template ) {
		$this->template( 'message-templates/list-item', [ 'template' => $email_template ] );
	}
	?>
</div>
