<?php
/**
 * Event Tickets Emails: Main template > Body > Tickets.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/tickets.php
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

if ( empty( $tickets ) ) {
	return;
}
$i = 0;

$this->template( 'template-parts/body/tickets-total' );

?>
<tr>
	<td style="padding:0;">
		<table class="tec-tickets__email-table-content-tickets" role="presentation">
		<?php foreach ( $tickets as $ticket ) : ?>
			<?php $i++; ?>
			<tr>
				<td class="tec-tickets__email-table-content-ticket">
				<?php $this->template( 'template-parts/body/ticket/holder-name', [ 'ticket' => $ticket ] ); ?>

				<?php $this->template( 'template-parts/body/ticket/ticket-name', [ 'ticket' => $ticket ] ); ?>

				<?php $this->template( 'template-parts/body/ticket/security-code', [ 'ticket' => $ticket ] ); ?>

				<?php $this->template( 'template-parts/body/ticket/number-from-total', [ 'i' => $i ] ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</table>
	</td>
</tr>
