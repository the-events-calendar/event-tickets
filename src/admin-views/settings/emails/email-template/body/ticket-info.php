<?php




?>
<tr>
	<td style="padding:20px 25px;background:<?php echo $ticket_bg_color; ?>">
		<?php $this->template( 'email-template/body/qr-image' ); ?>
		<?php $this->template( 'email-template/body/recipient-name' ); ?>
		<p style="font-size: 16px;margin:0;padding:0;color:<?php echo $ticket_text_color; ?>;">
			<?php echo $ticket_name; ?>
		</p>
		<p style="font-size: 14px;font-weight: 400;margin:0;padding:15px 0 0 0;color:<?php echo $ticket_text_color; ?>;">
			<?php echo $ticket_id; ?>
		</p>
	</td>
</tr>