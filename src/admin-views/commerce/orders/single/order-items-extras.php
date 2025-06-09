<?php
/**
 * Single order - Extras section
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 *
 * @var WP_Post $order The current post object (with added properties).
 */

declare( strict_types=1 );

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Values\Currency_Value;

$tip_amount    = (float) get_post_meta( $order->ID, Order::META_ORDER_TOTAL_TIP, true );
$tax_amount    = (float) get_post_meta( $order->ID, Order::META_ORDER_TOTAL_TAX, true );
$missed_amount = (float) get_post_meta( $order->ID, Order::META_ORDER_TOTAL_AMOUNT_UNACCOUNTED, true );

?>
<?php if ( $tip_amount ) : ?>
	<tr class="tec-tickets-commerce-single-order--items--table--row">
		<td><?php esc_html_e( 'Tip amount', 'event-tickets' ); ?></td>
		<td></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--info-column"></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--price-column" colspan="2">
			<div class="tec-tickets-commerce-price-container">
				<ins>
					<span class="tec-tickets-commerce-price">
						<?php echo esc_html( Currency_Value::create_from_float( $tip_amount )->get() ); ?>
					</span>
				</ins>
			</div>
		</td>
	</tr>
<?php endif; ?>

<?php if ( $tax_amount ) : ?>
	<tr class="tec-tickets-commerce__fees-section">
		<td colspan="5"><h2><?php esc_html_e( 'Tax amount', 'event-tickets' ); ?></h2></td>
	</tr>
	<tr class="tec-tickets-commerce-single-order--items--table--row">
		<td><?php esc_html_e( 'Tax amount', 'event-tickets' ); ?></td>
		<td></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--info-column"></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--price-column" colspan="2">
			<div class="tec-tickets-commerce-price-container">
				<ins>
					<span class="tec-tickets-commerce-price">
						<?php echo esc_html( Currency_Value::create_from_float( $tax_amount )->get() ); ?>
					</span>
				</ins>
			</div>
		</td>
	</tr>
<?php endif; ?>

<?php if ( $missed_amount ) : ?>
	<tr class="tec-tickets-commerce-single-order--items--table--row">
		<td><?php esc_html_e( 'Unaccounted amount', 'event-tickets' ); ?></td>
		<td></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--info-column"></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--price-column" colspan="2">
			<div class="tec-tickets-commerce-price-container">
				<ins>
					<span class="tec-tickets-commerce-price">
						<?php echo esc_html( Currency_Value::create_from_float( $missed_amount )->get() ); ?>
					</span>
				</ins>
			</div>
		</td>
	</tr>
<?php endif; ?>
