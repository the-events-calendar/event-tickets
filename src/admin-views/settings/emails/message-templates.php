<?php
/**
 * Tickets Emails Message Template List
 *
 * @since  5.5.6  List of email message templates for Emails settings tab.
 *
 * @var array<Email_Abstract>  $emails  Array of template info.
 */

use TEC\Tickets\Emails\Email_Abstract;

// @todo $templates variable will be an array of Message_Template objects in the future.

// If no templates, bail.
if ( empty( $emails ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-emails-template-list">
	<?php foreach ( $emails as $email ) : ?>
		<?php $this->template( 'message-templates/list-item', [ 'email' => $email ] ); ?>
	<?php endforeach; ?>
</div>
