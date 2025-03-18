<?php
/**
 * Single order - Coupons section.
 *
 * @since 5.21.0
 *
 * @version 5.21.0
 *
 * @var array   $coupons The coupons for the order.
 * @var WP_Post $order   The current post object (with added properties).
 */

declare( strict_types=1 );

// If we don't have any coupons, we don't need to display anything.
if ( empty( $coupons ) ) {
	return;
}

?>
<tr class="tec-tickets-commerce__coupons-section">
	<td colspan="5"><h2><?php esc_html_e( 'Coupons', 'event-tickets' ); ?></h2></td>
</tr>
<?php foreach ( $coupons as $coupon ) : ?>
	<tr class="tec-tickets-commerce-single-order--items--table--row">
		<td><?php echo esc_html( $coupon['display_name'] ); ?></td>
		<td><code><?php echo esc_html( $coupon['slug'] ); ?></code></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--info-column"></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--price-column" colspan="2">
			<div class="tec-tickets-commerce-price-container">
				<ins>
					<span class="tec-tickets-commerce-price">
						<?php echo esc_html( $coupon['sub_total']->get_currency() ); ?>
					</span>
				</ins>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
