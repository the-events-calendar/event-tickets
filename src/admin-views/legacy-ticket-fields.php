<?php
/**
 * @var string        $legacy_identifier
 * @var string|float  $price
 * @var string|float  $regular_price
 *
 * @see Tribe__Tickets__Legacy_Provider_Support
 */

$field_id = esc_attr( $legacy_identifier );
?>

<tr class="ticket_advanced ticket_advanced_<?php echo $field_id; ?>">
	<td>
		<label for="ticket_price"><?php esc_html_e( 'Price:', 'event-tickets' ); ?></label>
	</td>
	<td>
		<input type='text' id='ticket_price' name='ticket_price' class="ticket_field" size='7' value='<?php echo esc_attr( $regular_price ); ?>' />
		<p class="description"><?php esc_html_e( '(0 or empty for free tickets)', 'event-tickets' ) ?></p>
	</td>
</tr>
<tr class="ticket_advanced ticket_advanced_<?php echo $field_id; ?>">
	<td>
		<label for="ticket_sale_price"><?php esc_html_e( 'Sale Price:', 'event-tickets' ) ?></label>
	</td>
	<td>
		<input type='text' id='ticket_sale_price' name='ticket_sale_price' class="ticket_field" size='7' value='<?php echo esc_attr( $price ); ?>' readonly />
		<p class="description"><?php esc_html_e( '(Current sale price - this can be managed via the product editor)', 'event-tickets' ) ?></p>
	</td>
</tr>
