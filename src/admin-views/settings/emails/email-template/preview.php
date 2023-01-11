<?php
/**
 * Tickets Emails Email Template Preview.
 *
 * @since  TBD   Preview email template that can be viewed on a web page.
 *
 * @var Tribe__Template  $this    Parent template object.
 * @var boolean          $preview Whether or not we are viewing the template as a preview.
 */

// If not viewing preview, bail.
if ( ! tribe_is_truthy( $preview ) ) {
	return;
}

?><div style="max-width: 100%;">
	<?php $this->template( 'email-template/style' ); ?>
	<?php $this->template( 'email-template/body' ); ?>
</div>
