<?php
/**
 * Event Tickets Emails: Main template > Header > Head > Scripts.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/header/head/scripts.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template  $this  Current template object.
 */

// Bail if we're on preview.
if ( ! empty( $preview ) ) {
	return;
}

?>
<!--[if mso]>
<noscript>
	<xml>
		<o:OfficeDocumentSettings>
			<o:PixelsPerInch>96</o:PixelsPerInch>
		</o:OfficeDocumentSettings>
	</xml>
</noscript>
<![endif]-->
