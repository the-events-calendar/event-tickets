<?php
/**
 * Event Tickets Emails: Main template > Header > Head > Styles.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/header/head/styles.php
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
 * @var string $header_bg_color   Hex value for the header background color.
 * @var string $header_text_color Hex value for the header text color
 * @var string $ticket_bg_color   Hex value for the ticket background color.
 * @var string $ticket_text_color Hex value for the ticket text color.
 */

// @todo @codingmusician @juanfra @rafsuntaskin: Order properties alphabetically.
?>
<style type="text/css">
	body {
		margin: 0;
		padding: 0;
		text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
		color: #3C434A;
	}

	body, table, td, div, h1, p {font-family: Helvetica, Arial, sans-serif;}
	table { width:100%}
	table, td {border:0;border-spacing: 0;}

	.tec-tickets__email-body a,
	.tec-tickets__email-body a:focus,
	.tec-tickets__email-body a:visited {
		color: #141827;
		text-decoration: underline;
	}

	.tec-tickets__email-body,
	div.tec-tickets__email-body {
		width: 100%;
		border-collapse: collapse;
		border: 0;
		border-spacing: 0;
		background: #f0eeeb;
		color: #3C434A;
		padding-bottom: 30px;
	}

	.tec-tickets__email-preview-link,
	div.tec-tickets__email-preview-link {
		padding: 15px;
		text-align: center;
		font-size: 11px;
		max-width: 600px;
		margin: 0 auto;
	}

	.tec-tickets__email-table-main,
	table.tec-tickets__email-table-main {
		width: 100%;
		max-width: 600px;
		border-collapse: collapse;
		border: 0;
		border-spacing: 0;
		text-align: left;
		background: #ffffff;
		margin:0 auto;
	}

	.tec-tickets__email-table-main-header,
	td.tec-tickets__email-table-main-header {
		padding: 5px 5px 0px 5px;
		background: <?php echo esc_attr( $header_bg_color ); ?>;
		text-align: <?php echo esc_attr( $header_image_alignment ); ?>"
	}

	.tec-tickets__email-table-content,
	table.tec-tickets__email-table-content {
		width:100%;
		border:0;
		border-spacing:0;
		padding: 15px 30px;
	}

	.tec-tickets__email-table-content,
	.tec-tickets__email-table-content table {
		font-size: 14px;
	}

	.tec-tickets__email-table-content-title,
	h1.tec-tickets__email-table-content-title {
		font-size: 28px;
		font-weight: 700;
		letter-spacing: 0px;
		text-align: left;
		color: #141827;
		padding: 24px 0 !important;
		margin: 0;
	}

	.tec-tickets__email-table-content-event-title,
	h3.tec-tickets__email-table-content-event-title {
		color: #141827;
		font-style: normal;
		font-weight: 700;
		font-size: 18px;
		padding: 20px 0 !important;
		margin: 0;
	}

	.tec-tickets__email-table-content-tickets-total {
		padding:10px;
		text-align: center;
		font-weight: 700;
		background-color: <?php echo esc_attr( $header_bg_color ); ?>;
		color: <?php echo esc_attr( $header_text_color ); ?>;
	}

	.tec-tickets__email-table-content-tickets tbody > tr:last-child > td {
		border-bottom: 0;
	}

	.tec-tickets__email-table-content-ticket,
	td.tec-tickets__email-table-content-ticket {
		padding: 20px 25px;
		background:<?php echo esc_attr( $ticket_bg_color ); ?>;
		border-bottom: 1px solid rgba(0,0,0,.08);
	}

	.tec-tickets__email-table-content-ticket-holder-name,
	h2.tec-tickets__email-table-content-ticket-holder-name {
		font-size: 21px;
		font-weight: 700;
		line-height: 24px;
		margin:0;
		padding:0;
		background:transparent;
		color:<?php echo esc_attr( $ticket_text_color ); ?>;
	}

	.tec-tickets__email-table-content-ticket-type-name,
	div.tec-tickets__email-table-content-ticket-type-name {
		font-size: 16px;
		margin-top: 8px;
		padding:0;
		color:<?php echo esc_attr( $ticket_text_color ); ?>;
	}

	.tec-tickets__email-table-content-ticket-security-code,
	div.tec-tickets__email-table-content-ticket-security {
		font-size: 14px;
		font-weight: 400;
		display: block;
		margin:0 !important;
		padding:15px 0 0 0 !important;
		color:<?php echo esc_attr( $ticket_text_color ); ?>;
		opacity: .7;
	}

	.tec-tickets__email-table-content-ticket-number-from-total,
	div.tec-tickets__email-table-content-ticket-number-from-total {
		font-size: 14px;
		font-weight: 700;
		display: block;
		margin:0 !important;
		padding:15px 0 0 0 !important;
		color:<?php echo esc_attr( $ticket_text_color ); ?>;
	}

	.tec-tickets__email-table-main-footer,
	td.tec-tickets__email-table-main-footer {
		padding: 0px 20px 10px 20px;
		border-top: 1px solid #efefef;
		background-color: <?php echo esc_attr( $header_bg_color ); ?>;
	}

	.tec-tickets__email-table-main-footer table {
		font-size: 12px;
	}

</style>
