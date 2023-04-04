<?php
/**
 * Event Tickets Emails: Main template > Header.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/header/header.php
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

// If we're in preview mode, bail.
if ( ! empty( $preview ) ) {
	return;
}

$language = explode( '-', get_bloginfo( 'language' ) );
?><!DOCTYPE html>
	<html lang="<?php esc_attr( $language[0] ); ?>" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
	<head>
		<?php $this->template( 'template-parts/header/head/meta' ); ?>
		<?php $this->template( 'template-parts/header/head/title' ); ?>
		<?php $this->template( 'template-parts/header/head/scripts' ); ?>
		<?php $this->template( 'template-parts/header/head/json-ld' ); ?>
		<?php $this->template( 'template-parts/header/head/styles' ); ?>
	</head>
	<body class="tec-tickets__email-body">
		<?php $this->template( 'template-parts/header/top-link' ); ?>

		<table role="presentation" class="tec-tickets__email-table-main">
			<?php $this->template( 'template-parts/body/header' ); ?>
			<tr>
				<td>
					<table role="presentation" class="tec-tickets__email-table-content">
