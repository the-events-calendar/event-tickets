<?php
/**
 * Single order - Items metabox.
 *
 * @since 5.13.3
 * @since 5.21.0 Added the coupons and fees sections.
 * @since 5.24.0 Added the extras section.
 *
 * @version 5.24.0
 *
 * @var WP_Post             $order       The current post object.
 * @var Singular_Order_Page $single_page The orders table output.
 */

use TEC\Tickets\Commerce\Admin\Singular_Order_Page;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;

?>
<div class="tec-tickets-commerce-single-order--items">
	<table class="tec-tickets-commerce-single-order--items--table widefat fixed">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Item name', 'event-tickets' ); ?></th>
				<th class="tribe-desktop-only"><?php esc_html_e( 'Type', 'event-tickets' ); ?></th>
				<th class="tec-tickets-commerce-single-order--items--table--row--info-column"></th>
				<th style="padding-left:0;"><?php esc_html_e( 'Price', 'event-tickets' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $order->items as $item ) {
				/**
				 * Filters whether the item should be displayed in the single order items metabox.
				 *
				 * @since 5.18.0
				 *
				 * @param bool    $should_display Whether the item should be displayed.
				 * @param array   $item           The item data.
				 * @param WP_Post $order          The order object.
				 */
				if ( ! apply_filters( 'tec_tickets_commerce_single_orders_items_item_should_be_displayed', true, $item, $order ) ) {
					continue;
				}

				$ticket_id = $item['ticket_id'];

				$ticket = tribe( Module::class )->get_ticket( 0, $ticket_id );

				if ( ! $ticket ) {
					continue;
				}

				$attendees = $item['extra']['attendees'] ?? [];
				if ( empty( $attendees ) ) {
					// Order without ET+/specific attendee details per ticket.
					for ( $i = 0; $i < $item['quantity']; $i++ ) {
						$this->template(
							'order-items-metabox-item',
							[
								'order'    => $order,
								'ticket'   => $ticket,
								'item'     => $item,
								'attendee' => null,
							]
						);
					}
				}
				foreach ( $attendees as $attendee ) {
					$this->template(
						'order-items-metabox-item',
						[
							'order'    => $order,
							'ticket'   => $ticket,
							'item'     => $item,
							'attendee' => $attendee,
						]
					);
				}
			}

			$this->template(
				'order-items-coupons',
				[
					'coupons' => $order->coupons ?? [],
					'order'   => $order,
				]
			);

			$this->template(
				'order-items-fees',
				[
					'fees'  => $order->fees ?? [],
					'order' => $order,
				]
			);

			$this->template( 'order-items-extras' );
			?>
		</tbody>
		<tfoot>
			<tr class="tec-tickets-commerce-single-order--items--table--row tec-tickets-commerce-single-order--items--table--row--gray-bg">
				<td>
					<button type="button" class="button button-secodnary">
						<?php esc_html_e( 'Refund', 'event-tickets' ); ?>
					</button>
				</td>
				<td class="tribe-desktop-only"></td>
				<td class="tec-tickets-commerce-single-order--items--table--row--info-column">
					<strong><?php esc_html_e( 'Total', 'event-tickets' ); ?></strong>
				</td>
				<td style="padding-left:0;">
					<?php
					$original = tribe( Order::class )->get_value( $order->ID, true );
					$current  = tribe( Order::class )->get_value( $order->ID );

					if ( $original !== $current ) {
						printf(
							'<div class="tec-tickets-commerce-price-container"><ins><span class="tec-tickets-commerce-price">%s</span></ins><del><span class="tec-tickets-commerce-price">%s</span></del></div>',
							esc_html( $current ),
							esc_html( $original )
						);
					} else {
						printf(
							'<div class="tec-tickets-commerce-price-container"><ins><span class="tec-tickets-commerce-price">%s</span></ins></div>',
							esc_html( $current )
						);
					}
					?>
				</td>
				<td></td>
			</tr>
		</tfoot>
	</table>
</div>
<?php
