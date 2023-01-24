<?php
/**
 * Tickets Emails Message Template List
 *
 * @since  5.5.6  List of email message templates for Emails settings tab.
 *
 * @var Array[]  $templates  Array of template info.
 */

// @todo $templates variable will be an array of Message_Template objects in the future.

// If no templates, bail.
if ( empty( $templates ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-emails-template-list">
	<?php foreach ( $templates as $email_template ) : ?>
		<?php $this->template( 'message-templates/list-item', [ 'template' => $email_template ] ); ?>
	<?php endforeach; ?>
</div>
