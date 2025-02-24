<?php
/**
 * Event Tickets Emails: Main template > Header > Head > Series Pass Styles.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/header/head/series-pass-styles.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.8.4
 *
 * @since   5.8.4
 */
?>

<style type="text/css">
	td.tec-tickets__email-table-content-upcoming-events-list__title-container {
		padding-top: 30px;
	}

	tr.tec-tickets__email-table-content-upcoming-events-list__cards-container {
		display: block;
		border: 1px solid #D5D5D5;
		padding: 27px;
	}

	.tec-tickets__email-table-content-upcoming-event-card {
		display: block;
		width: 100%;
	}

	.tec-tickets__email-table-content-upcoming-event-card:not(:last-of-type) {
		margin-bottom: 18px;
	}

	td.tec-tickets__email-table-content-upcoming-event-card__month {
		font-size: 11px;
		color: #727272;
		text-align: center;
	}

	td.tec-tickets__email-table-content-upcoming-event-card__time {
		font-size: 12px;
		color: #141827;
		padding: 0 0 0 14px;
	}

	td.tec-tickets__email-table-content-upcoming-event-card__day {
		font-size: 24px;
		color: #141827;
		font-weight: bold;
		text-align: center;
	}

	td.tec-tickets__email-table-content-upcoming-event-card__title {
		font-size: 18px;
		color: #141827;
		font-weight: bold;
		padding: 0 0 0 14px;
	}

	.tec-tickets__email-table-content-upcoming-event-card__link {
		display: block;
		padding: 18px 0 0 0 !important;
	}

	.tec-tickets__email-table-content-upcoming-event-card__link a {
		color: #141827;
		text-decoration: underline !important;
	}
</style>