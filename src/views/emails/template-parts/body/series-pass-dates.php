<?php
/**
 * Event Tickets Emails: Main template > Body > Series Pass Dates.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/series-pass-dates.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.8.4
 *
 * @since 5.8.4
 *
 * @var string[] $dates The set of dates to render the template for.
 */

if ( empty( $dates ) ) {
	return;
}
?>

<tr>
	<td class="tec-tickets__email-table-content__series-date">
		<?php echo esc_html( implode( ' - ', $dates ) ); ?>
	</td>
</tr>
