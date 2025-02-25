<?php
/**
 * Single order - Fees section
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var array   $fees  The fees for the order.
 * @var WP_Post $order The current post object (with added properties).
 */

declare( strict_types=1 );

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
						<?php echo esc_html( $fee['sub_total']->get_currency() ); ?>
					</span>
				</ins>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
