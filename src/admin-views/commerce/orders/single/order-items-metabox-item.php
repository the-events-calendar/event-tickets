<?php
/**
 * Single order - Items metabox - SingleItem.
 *
 * @since 5.13.3
 *
 * @version 5.13.3
 *
 * @var WP_Post                       $order    The current post object.
 * @var array                         $item     The current order item.
 * @var Tribe__Tickets__Ticket_Object $ticket   The ticket object.
 * @var array                         $attendee The attendee object.
 */

use TEC\Tickets\Commerce\Order;
?>
<tr class="tec-tickets-commerce-single-order--items--table--row">
	<td><?php echo esc_html( $ticket->name ); ?></td>
	<td class="tribe-desktop-only"><?php echo 'series_pass' === $ticket->type ? esc_html__( 'Series Pass', 'event-tickets' ) : esc_html__( 'Standard Ticket', 'event-tickets' ); ?></td>
	<td class="tec-tickets-commerce-single-order--items--table--row--info-column"><!-- @todo dpan: this is were Refunded would go --></td>
	<td style="padding-left:0;">
		<?php
		$current  = tribe( Order::class )->get_item_value( $item );
		$original = tribe( Order::class )->get_item_value( $item, true );
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
	<td>
		<div class="tec-tickets-commerce-single-order--items--table--row--actions">
			<a href="javascript:void(0)" class="tribe-dashicons">
				<span class="dashicons tribe-icon-trash"></span>
				<span class="tribe-mobile-hidden"><?php esc_html_e( 'Delete', 'event-tickets' ); ?></span>
			</a>
			<?php if ( ! empty( $attendee ) ) : ?>
			<button type="button" class="tribe-tickets-commerce-extend-order-row" aria-disabled="false">
				<span class="screen-reader-text"><?php esc_html_e( 'Expand row', 'event-tickets' ); ?></span>
				<span class="tribe-tickets-commerce-order-extend-indicator" aria-hidden="true"></span>
			</button>
			<?php endif; ?>
		</div>
	</td>
</tr>
<?php
if ( ! empty( $attendee ) ) :
	?>
	<tr class="tec-tickets-commerce-single-order--items--table--attendee-row tec-tickets-commerce-single-order--items--table--row--gray-bg">
		<td colspan="4">
			<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column">
				<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row">
					<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row--label">
						<?php esc_html_e( 'Attendee', 'event-tickets' ); ?>
					</div>
					<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row--value">
						<?php
						$edit = <<<HTML
						<a class="tribe-dashicons" href="javascript:void(0)"><span class="dashicons dashicons-edit"></span>
							%s
						</a>
						HTML;

						$edit = sprintf( $edit, esc_html__( 'Edit', 'event-tickets' ) );
						$attendee['meta'][ array_keys( $attendee['meta'] )[0] ] .= '%s';
						echo is_array( $attendee['meta'] ) ?
						sprintf( implode( '</br>', array_map( 'esc_html', $attendee['meta'] ) ), $edit ) : // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped
						'';
						?>
					</div>
				</div>
				<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row">
					<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row--label">
						<?php esc_html_e( 'Event', 'event-tickets' ); ?>
					</div>
					<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row--value">
						<?php
						$events = tribe( Order::class )->get_events( $order->ID );
						foreach ( $events as $event ) {
							if ( ! current_user_can( 'edit_post', $event->ID ) ) {
								printf(
									'<div>%s</div>',
									esc_html( get_the_title( $event->ID ) )
								);
								continue;
							}

							if ( 'trash' === $event->post_status ) {
								// translators: 1) is the event's title and 2) is an indication as a text that it is now trashed.
								printf(
									'<div>%1$s %2$s</div>',
									esc_html( get_the_title( $event->ID ) ),
									esc_html_x( '(trashed)', 'This is about an "event" related to a Tickets Commerce order that now has been trashed.', 'event-tickets' )
								);
								continue;
							}

							if ( ( ! in_array( $event->post_type, get_post_types( [ 'show_ui' => true ] ), true ) ) ) {
								printf(
									'<div>%s</div>',
									esc_html( get_the_title( $event->ID ) )
								);
								continue;
							}

							printf(
								'<div><a href="%s">%s</a></div>',
								esc_url( get_edit_post_link( $event->ID ) ),
								esc_html( get_the_title( $event->ID ) )
							);
						}
						?>
					</div>
				</div>
				<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row">
					<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row--label">
						<?php esc_html_e( 'Seat', 'event-tickets' ); ?>
					</div>
					<div class="tec-tickets-commerce-single-order--items--table--attendee-row--column--row--value">
						<!-- @TODO dpan Needs dynamic -->
						<a href="javascript:void(0)">D12 - General Theater</a>
					</div>
				</div>
			</div>
		</td>
		<td class="tribe-desktop-only"></td>
	</tr>
	<?php
endif;
