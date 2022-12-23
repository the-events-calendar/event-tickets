<?php
/**
 * Tickets Emails Email Template Main
 *
 * @since  TBD   Main email template that goes to recipients' email clients.
 * 
 * @var Tribe__Template  $this  Parent template object.
 * 
 */

?><!DOCTYPE html>
	<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width,initial-scale=1">
		<meta name="x-apple-disable-message-reformatting">
		<title></title>
		<!--[if mso]>
		<noscript>
			<xml>
				<o:OfficeDocumentSettings>
					<o:PixelsPerInch>96</o:PixelsPerInch>
				</o:OfficeDocumentSettings>
			</xml>
		</noscript>
		<![endif]-->
		<?php $this->template( 'email-template/style' ); ?>
	</head>
	<body style="margin:0;padding:0;">
		<?php $this->template( 'email-template/body' ); ?>
	</body>
</html>