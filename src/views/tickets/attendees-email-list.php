<?php
/**
 * Attendees Email List Template
 * The template for the email with the attendee list when using ticketing plugins (Like WooTickets), shown in List format
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/attendees-email-list.php
 *
 */
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body text="#222222" bgcolor="#ffffff" style="width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0 auto; padding:0; background:#ffffff; min-height:1000px;">
<table align="center" width="100%" style="border-width: 1px; padding:0; border-spacing: 0px; border-style: none; border-color: #cccccc; border-collapse: collapse; background-color: #f7f7f7;">
	<tr>
		<td align="left" style="padding:20px; background-color: #dddddd;">
			<h1 style="color:#0a0a0e; margin:0 0 20px 0 !important; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-style:normal; font-weight:700; font-size:32px; letter-spacing:normal; text-align:left; line-height: 100%;"><?php echo esc_html( $event->post_title ); ?></h1>

			<h2 style="color:#0a0a0e; margin:0 !important; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-style:normal; font-weight:700; font-size:18px; letter-spacing:normal; text-align:left; line-height: 100%;"><?php _e( 'Attendee List', 'event-tickets' ); ?></h2>
		</td>
	</tr>
</table>
<table align="center" cellpadding="5" width="100%" style="border-collapse: collapse; padding:0; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-style:normal; font-weight:400; font-size:13px; letter-spacing:normal; text-align:left; line-height: 100%;">
	<?php

	$count       = 0;
	$head_style  = 'background:#444444; color:#ffffff; padding:15px;';
	$odd_style   = 'background:#eeeeee; color:#222222; padding:15px; border-bottom:1px solid #ccc;';
	$even_style  = 'background:#ffffff; color:#222222; padding:15px; border-bottom:1px solid #ccc;';
	$field_names = array();

	foreach ( $items as $item ) {
		$count ++;

		echo '<tbody>';

		if ( $count === 2 ) {
			$cell_type = 'td';

		}

		if ( $count > 1 ) {
			if ( $count % 2 == 0 ) {
				$style = $odd_style;
			} else {
				$style = $even_style;
			}
		}

		echo '<tr>';
		echo '<td style="'. esc_attr( $style ) . '">';
		foreach ( $item as $key => $field ) {
			if ( $count === 1 ) {
				$field_names[] = esc_html( $field );
			} else {
				echo sprintf( '<div><span style="font-weight: bold;">%1$s:</span> %2$s</div>', array_values( $field_names )[ $key ], esc_html( $field ) );
			}
		}
		echo '</td>';
		echo '</tr>';

		if ( $count === count( $items ) ) {
			echo '</tbody>';
		}
	}
	?>
</table>
</body>
</html>
