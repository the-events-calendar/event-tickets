<?php return '<style type="text/css">
	body {
		color: #3C434A;
		margin: 0;
		padding: 0;
		text-align: left;
	}

	body, table, td, div, h1, p { font-family: Helvetica, Arial, sans-serif; }
	table { width: 100% }
	table, td { border: 0; border-spacing: 0; }

	.tec-tickets__email-body a,
	.tec-tickets__email-body a:focus,
	.tec-tickets__email-body a:visited {
		color: #141827 !important;
		text-decoration: underline;
	}

	h3.tec-tickets__email-table-content-post-title a {
		text-decoration: none;
	}

	.tec-tickets__email-table-content-post-title-container,
	td.tec-tickets__email-table-content-post-title-container {
		padding: 0;
	}

	.tec-tickets__email-body,
	div.tec-tickets__email-body {
		background: #f0eeeb;
		border: 0;
		border-collapse: collapse;
		border-spacing: 0;
		color: #3C434A;
		padding-bottom: 30px;
		padding-top: 30px;
		width: 100%;
	}

	.tec-tickets__email-preview-link,
	div.tec-tickets__email-preview-link {
		font-size: 11px;
		padding: 15px;
		margin: 0 auto;
		max-width: 600px;
		text-align: center;
	}

	.tec-tickets__email-table-main,
	table.tec-tickets__email-table-main {
		background: #ffffff;
		border: 0;
		border-collapse: collapse;
		border-spacing: 0;
		margin:0 auto;
		max-width: 600px;
		text-align: left;
		width: 100%;
	}

	.tec-tickets__email-table-main-header,
	td.tec-tickets__email-table-main-header {
		background: #ffffff;
		padding: 5px 5px 0px 5px;
		text-align: left;
	}

	.tec-tickets__email-table-content,
	table.tec-tickets__email-table-content {
		border: 0;
		border-spacing: 0;
		padding: 15px 30px;
		width: 100%;
	}

	.tec-tickets__email-table-content,
	.tec-tickets__email-table-content table {
		font-size: 14px;
	}

	.tec-tickets__email-table-content-title,
	h1.tec-tickets__email-table-content-title {
		color: #141827;
		font-size: 28px;
		font-weight: 700;
		letter-spacing: 0px;
		line-height: 1.25;
		margin: 0;
		padding: 24px 0 !important;
		text-align: left;
	}

	.tec-tickets__email-table-content-post-title,
	h3.tec-tickets__email-table-content-post-title {
		color: #141827;
		font-size: 18px;
		font-style: normal;
		font-weight: 700;
		margin: 0;
		padding: 20px 0 !important;
	}

	.tec-tickets__email-table-content-tickets-total {
		background-color: #ffffff;
		color: #000000;
		font-weight: 700;
		padding:10px;
		text-align: center;
	}

	.tec-tickets__email-table-content-tickets tbody > tr:last-child > td {
		border-bottom: 0;
	}

	.tec-tickets__email-table-content-ticket,
	td.tec-tickets__email-table-content-ticket {
		background:#007363;
		border-bottom: 1px solid rgba(0,0,0,.08);
		padding: 20px 25px;
	}

	.tec-tickets__email-table-content-ticket-holder-name,
	h2.tec-tickets__email-table-content-ticket-holder-name {
		background:transparent;
		color: #ffffff;
		font-size: 21px;
		font-weight: 700;
		line-height: 24px;
		margin: 0;
		padding: 0;
	}

	.tec-tickets__email-table-content-ticket-type-name,
	div.tec-tickets__email-table-content-ticket-type-name {
		color: #ffffff;
		font-size: 16px;
		margin-top: 8px;
		padding: 0;
	}

	.tec-tickets__email-table-content-ticket-security-code,
	div.tec-tickets__email-table-content-ticket-security {
		color: #ffffff;
		display: block;
		font-size: 14px;
		font-weight: 400;
		margin: 0 !important;
		opacity: .7;
		padding: 15px 0 0 0 !important;
	}

	.tec-tickets__email-table-content-ticket-number-from-total,
	div.tec-tickets__email-table-content-ticket-number-from-total {
		color: #ffffff;
		font-size: 14px;
		font-weight: 700;
		display: block;
		margin: 0 !important;
		padding: 15px 0 0 0 !important;
	}

	.tec-tickets__email-table-main-footer,
	td.tec-tickets__email-table-main-footer {
		background-color: #ffffff;
		border-top: 1px solid #efefef;
		padding: 0px 20px 10px 20px;
	}

	.tec-tickets__email-table-main-footer table {
		font-size: 12px;
	}

	.tec-tickets__email-table-content-order-attendees-table-container,
	td.tec-tickets__email-table-content-order-attendees-table-container {
		padding-bottom: 50px;
	}

	.tec-tickets__email-table-content-order-attendees-table,
	table.tec-tickets__email-table-content-order-attendees-table {
		border-collapse: collapse;
		margin-top: 10px;
	}

	.tec-tickets__email-table-content-order-attendees-table-header-row,
	tr.tec-tickets__email-table-content-order-attendees-table-header-row {
		border: 1px solid #d5d5d5;
		color: #727272;
		font-size: 12px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-attendees-table-header-cell,
	td.tec-tickets__email-table-content-order-attendees-table-header-cell {
		padding:0 6px;
	}

	.tec-tickets__email-table-content-order-attendee-info-row,
	tr.tec-tickets__email-table-content-order-attendee-info-row {
		border: 1px solid #d5d5d5;
		font-size: 12px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-attendee-info,
	td.tec-tickets__email-table-content-order-attendee-info {
		line-height: 18px;
		padding: 0 6px;
		vertical-align: top;
	}

	.tec-tickets__email-table-content-align-left {
		text-align: left;
	}

	.tec-tickets__email-table-content-align-center {
		text-align: center;
	}

	.tec-tickets__email-table-content-align-right {
		text-align: right;
	}

	.tec-tickets__email-table-content-order-error-top-text {
		font-size: 14px;
		font-weight: 400;
		padding-top: 10px;
	}

	.tec-tickets__email-table-content-order-error-bottom-text,
	td.tec-tickets__email-table-content-order-error-bottom-text {
		color: #da394d;
		font-size: 14px;
		font-weight: 700;
		padding: 24px 0 40px;
	}

	.tec-tickets__email-table-content-order-post-title,
	td.tec-tickets__email-table-content-order-post-title {
		font-size: 16px;
		font-weight: 700;
		padding-top:43px;
	}

	.tec-tickets__email-table-content-order-total-container,
	td.tec-tickets__email-table-content-order-total-container {
		padding-top: 20px;
		text-align: right;
	}

	.tec-tickets__email-table-content-order-total-table {
		display: inline-block;
		width: auto;
	}

	.tec-tickets__email-table-content-order-total-left-cell,
	td.tec-tickets__email-table-content-order-total-left-cell {
		font-size: 14px;
		font-weight: 400;
		line-height: 24px;
		padding-right: 10px;
	}

	.tec-tickets__email-table-content-order-total-right-cell {
		font-size: 16px;
		font-weight: 700;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-payment-info-container,
	td.tec-tickets__email-table-content-order-payment-info-container {
		font-size: 14px;
		font-weight: 400;
		padding: 20px 0 50px;
		text-align: right;
	}

	.tec-tickets__email-table-content-order-purchaser-details-top {
		font-size: 16px;
		font-weight: 700;
		line-height: 23px;
	}

	.tec-tickets__email-table-content-order-purchaser-details-bottom {
		font-size: 14px;
		font-weight: 400;
		line-height: 23px;
	}

	.tec-tickets__email-table-content-order-ticket-totals-header-row,
	tr.tec-tickets__email-table-content-order-ticket-totals-header-row {
		border: 1px solid #d5d5d5;
		color: #727272;
		font-size: 12px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-ticket-totals-ticket-row,
	tr.tec-tickets__email-table-content-order-ticket-totals-ticket-row {
		border: 1px solid #d5d5d5;
		font-size: 14px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-ticket-totals-cell,
	th.tec-tickets__email-table-content-order-ticket-totals-cell,
	td.tec-tickets__email-table-content-order-ticket-totals-cell {
		padding: 0 6px;
	}

	.tec-tickets__email-table-content-greeting-container,
	td.tec-tickets__email-table-content-greeting-container {
		line-height: 21px;
		padding-bottom: 40px;
	}

	.tec-tickets__email-table-content-additional-content-container,
	td.tec-tickets__email-table-content-additional-content-container {
		padding-bottom: 40px;
	}

	.tec-tickets__email-table-content-additional-content-container p,
	td.tec-tickets__email-table-content-additional-content-container p {
		color: #141827 !important;
		font-size: 16px !important;
		line-height: 1.5;
	}

	.tec-tickets__email-table-content-not-going-confirmation-container,
	td.tec-tickets__email-table-content-not-going-confirmation-container {
		padding-bottom: 30px;
	}

	.tec-tickets__email-table-content-ticket-holder-name-container,
	td.tec-tickets__email-table-content-ticket-holder-name-container {
		vertical-align: top;
	}

	.tec-tickets__email-table-content-ticket-security-code-container,
	td.tec-tickets__email-table-content-ticket-security-code-container {
		vertical-align: bottom;
	}

	@media screen and ( max-width: 500px ) {
		.tec-tickets__email-table-content-title,
		h1.tec-tickets__email-table-content-title {
			font-size: 21px;
			line-height: 28px;
		}

		.tec-tickets__email-table-content,
		table.tec-tickets__email-table-content {
			padding: 15px 25px;
		}

		.tec-tickets__email-table-content-ticket-holder-name-container,
		td.tec-tickets__email-table-content-ticket-holder-name-container {
			display: block;
			padding-bottom: 30px;
			text-align: left;
		}

		.tec-tickets__email-table-content-ticket-security-code {
			padding: 15px 0 30px 0 !important;
		}

		.tec-tickets__email-table-content-ticket-security-code-container,
		td.tec-tickets__email-table-content-ticket-security-code-container {
			display: block;
			text-align: center;
		}
	}
</style>
<div class="tec-tickets__email-body">

		
		<table role="presentation" class="tec-tickets__email-table-main">

			<tr>
	<td
		class="tec-tickets__email-table-main-header"
		align="left"
	>
			</td>
</tr>

			<tr>
				<td>
					<table role="presentation" class="tec-tickets__email-table-content">
<tr>
	<td>
		<h1 class="tec-tickets__email-table-content-title">
			You confirmed you will not be attending		</h1>
	</td>
</tr>

<tr>
	<td class="tec-tickets__email-table-content-not-going-confirmation-container">
		Thank you for confirming that you will not be attending.	</td>
</tr>

					</table>
				</td>
			</tr>
			<tr>
	<td class="tec-tickets__email-table-main-footer">
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
						<tr>
	<td style="padding:10px 0px 0px 0px;text-align:right;color:#000000;" align="right">
		Powered by <a href="https://evnt.is/et-in-app-email-credit" style="color:#000000">Event Tickets</a>	</td>
</tr>
		</table>
	</td>
</tr>
		</table>
</div>
<div  class="tribe-tickets-loader__dots tribe-common-c-loader tribe-common-a11y-hidden tribe-common-c-loader__dot tribe-common-c-loader__dot--third" >
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--first"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--second"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--third"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
</div>
';
