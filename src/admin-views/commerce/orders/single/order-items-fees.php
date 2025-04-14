<?php
/**
 * Single order - Fees section
 *
 * @since 5.21.0
 *
 * @version 5.21.0
 *
 * @var array   $fees  The fees for the order.
 * @var WP_Post $order The current post object (with added properties).
 */

declare( strict_types=1 );

use TEC\Tickets\Commerce\Values\Currency_Value;

// If we don't have any fees, we don't need to display anything.
if ( empty( $fees ) ) {
	return;
}

?>
<tr class="tec-tickets-commerce__fees-section">
	<td colspan="5"><h2><?php esc_html_e( 'Fees', 'event-tickets' ); ?></h2></td>
</tr>
<?php foreach ( $fees as $fee ) : ?>
	<tr class="tec-tickets-commerce-single-order--items--table--row">
		<td><?php echo esc_html( $fee['display_name'] ); ?></td>
		<td></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--info-column"></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--price-column" colspan="2">
			<div class="tec-tickets-commerce-price-container">
				<ins>
					<span class="tec-tickets-commerce-price">
						<?php echo esc_html( Currency_Value::create_from_float( $fee['sub_total'] )->get() ); ?>
					</span>
				</ins>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
