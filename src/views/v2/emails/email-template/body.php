<?php
/**
 * Tickets Emails Email Template body
 *
 * @since  TBD   Email template body.
 *
 * @var Tribe__Template  $this  Parent template object.
 */

// @todo Update link URL.
?>
<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;background:#f0eeeb;color:#3C434A;">
	<tr>
		<?php $this->template( 'email-template/body/top-link' ); ?>
	</tr>
	<tr>
		<td align="center" style="padding:0;">
			<table role="presentation" style="width:100%;max-width:600px;border-collapse:collapse;border:0;border-spacing:0;text-align:left;background:#ffffff;">
				<?php $this->template( 'email-template/body/header' ); ?>
				<tr>
					<td style="padding:15px 30px;">
						<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
							<?php $this->template( 'email-template/body/greeting' ); ?>
							<?php $this->template( 'email-template/body/date' ); ?>
							<?php $this->template( 'email-template/body/event-title' ); ?>
							<?php $this->template( 'email-template/body/event-image' ); ?>
							<?php $this->template( 'email-template/body/ticket-info' ); ?>
							<?php $this->template( 'email-template/body/event-location' ); ?>
							<?php $this->template( 'email-template/body/add-links' ); ?>
						</table>
					</td>
				</tr>
				<?php $this->template( 'email-template/body/footer' ); ?>
			</table>
		</td>
	</tr>
	<tr>
		<td style="padding:10px">&nbsp;</td>
	</tr>
</table>
