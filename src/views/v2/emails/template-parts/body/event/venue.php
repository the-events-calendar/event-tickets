<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Location.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/event/venue.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var array $event_venue Array of venue information.
 */

if ( empty( $tickets[0]['event']['venue'] ) || ! is_array( $tickets[0]['event']['venue'] ) ) {
	return;
}

$event_venue = $tickets[0]['event']['venue'];

// @todo - Update `Get Directions` link.

?>
<tr>
	<td style="padding:54px 0 12px 0">
		<h3 style="font-size:16px;font-weight:700;background:transparent;padding:0;margin:0;color:#141827">
			<?php esc_html_e( 'Event Location', 'event-tickets' ); ?>
		</h3>
	</td>
</tr>
<tr>
	<td style="border:1px solid #d5d5d5;padding:25px;">
		<h2 style="font-size: 18px;font-weight: 700;margin:0;padding:0;background:transparent;">
			<?php echo esc_html( $event_venue['name'] ); ?>
		</h2>
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
			<tr>
				<td style="padding:12px 0 0 0; width: 50%;">
					<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
						<tr>
							<td style="text-align:center;vertical-align:top;display:inline-block;" valign="top" align="center">
								<img width="20" height="28" style="width:20px;height:28px;display:block;" src="<?php echo plugins_url( '/event-tickets/src/resources/icons/map-pin.svg' ) ?>" />
							</td>
							<td style="padding:0;text-align:left">
								<?php echo esc_html( $event_venue['address1'] ); ?><br>
								<?php echo esc_html( $event_venue['address2'] ); ?><br>
								<a href="#"><?php esc_html_e( 'Get Directions', 'event-tickets' ); ?></a>
							</td>
						</tr>
					</table>
				</td>
				<td style="padding:0; width: 50%;">
					<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;margin-bottom:18px">
						<tr>
							<td style="display:inline-block;text-align:center;vertical-align:top;" valign="top" align="center">
								<img width="25" height="24" style="width:25px;height:24px;display:block;" src="<?php echo plugins_url( '/event-tickets/src/resources/icons/phone.svg' ) ?>" />
							</td>
							<td style="padding:0;">
								<?php echo esc_html( $event_venue['phone'] ); ?>
							</td>
						</tr>
					</table>
					<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
						<tr>
							<td style="display:inline-block;text-align:center;vertical-align:top;" valign="top" align="center">
								<img width="24" height="23" style="width:24px;height:23px;display:block;" src="<?php echo plugins_url( '/event-tickets/src/resources/icons/link.svg' ) ?>" />
							</td>
							<td style="padding:0;">
								<a
									href="<?php echo esc_url( $event_venue['website'] ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									style="overflow-wrap: anywhere;"
								>
									<?php echo esc_url( $event_venue['website'] ); ?>
								</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
</tr>
