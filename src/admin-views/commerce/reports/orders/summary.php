<?php
/**
 * Template to render the Orders Report Summary.
 *
 * @version 5.6.7
 *
 * @var int $post_id The current post ID.
 * @var WP_Post $post The current post object.
 * @var string $post_singular_label The post type singular label.
 * @var Order_Summary $order_summary The data object.
 */
use TEC\Tickets\Commerce\Reports\Data\Order_Summary;

$sales_totals    = $order_summary->get_event_sales_data();
$tickets_by_type = $order_summary->get_tickets_by_type();
?>
<div id="tribe-order-summary" class="welcome-panel tribe-report-panel">
	<div class="welcome-panel-content">
		<div class="welcome-panel-column-container">
			<div class="welcome-panel-column welcome-panel-first">
				<h3>
				<?php
					echo esc_html(
						sprintf(
							_x( '%s Details', 'post type details', 'event-tickets' ),
							$post_singular_label
						)
					);
					?>
				</h3>
				<ul>
					<?php
					/**
					 * Provides an action that allows for the injections of fields at the top of the order report details meta ul
					 *
					 * @since 4.7
					 *
					 * @var $post_id
					 */
					do_action( 'tribe_tickets_report_event_details_list_top', $post_id );

					/**
					 * Provides an action that allows for the injections of fields at the bottom of the order report details ul
					 *
					 * @since 4.7
					 *
					 * @var $event_id
					 */
					do_action( 'tribe_tickets_report_event_details_list_bottom', $post_id );
					?>
				</ul>

				<?php
				/**
				 * Fires after the event details list (in the context of the  Orders Report admin view).
				 *
				 * @since 4.7
				 *
				 * @param WP_Post      $post
				 * @param bool|WP_User $author
				 */
				do_action( 'tribe_tickets_after_event_details_list', $post );
				?>

			</div>
			<div class="welcome-panel-column welcome-panel-middle">
				<h3 class="tec-tickets__admin-orders-report-overview-title">
					<?php
					echo esc_html(
						sprintf(
							__( 'Sales by %s', 'event-tickets' ),
							tribe_get_ticket_label_singular( 'sales_by_type' )
						)
					);
					?>
				</h3>
					<?php foreach ( $tickets_by_type as $type => $items ) : ?>
				<div class="tec-tickets__admin-orders-report-overview-ticket-type">
						<div class="tec-tickets__admin-orders-report-overview-ticket-type-icon tec-tickets__admin-orders-report-overview-ticket-type-icon--<?php echo esc_html( $type ); ?>"></div>
						<div class="tec-tickets__admin-orders-report-overview-ticket-type-label">
							<?php echo esc_html( $order_summary->get_label_for_type( $type ) ); ?>
						</div>
						<div class="tec-tickets__admin-orders-report-overview--border"></div>
				</div>
				<ul class="tec-tickets__admin-orders-report-overview-ticket-type-list">
					<?php foreach ( $items as $item ) : ?>
					<li class="tec-tickets__admin-orders-report-overview-ticket-type-list-item">
						<div class="tec-tickets__admin-orders-report-overview-ticket-type-list-item-ticket-name">
							<?php echo esc_html( $item['label'] ); ?>
						</div>
						<div class="tec-tickets__admin-orders-report-overview-ticket-type-list-item-stat">
							<?php echo esc_html( $item['qty_by_status'] ); ?>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
					<?php endforeach; ?>
			</div>
			<div class="welcome-panel-column welcome-panel-last alternate">
				<div class="tec-tickets__admin-orders-report__sales-overview__title">
					<h3><?php echo esc_html__( 'Sales Totals', 'event-tickets' ); ?></h3>
				</div>
				<div class="tec-tickets__admin-orders-report__sales-overview__data">
					<div class="tec-tickets__admin-orders-report__sales-overview__by-status">
						<?php foreach ( $sales_totals['by_status'] as $status ) : ?>
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item">
								<div class="tec-tickets__admin-orders-report__sales-overview__list__item-label"><?php echo esc_html( $status['label'] ); ?></div>
								<div class="tec-tickets__admin-orders-report__sales-overview__list__item-amount"><?php echo esc_html( sprintf( '%1$s (%2$s)', $status['total_sales_price'], $status['qty_sold'] ) ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="tec-tickets__admin-orders-report-overview--border"></div>
					<div class="tec-tickets__admin-orders-report__sales-overview__total">
						<div class="tec-tickets__admin-orders-report__sales-overview__list__item">
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-label">
								<div class="tec-tickets__admin-orders-report__sales-overview__total-label">
									<?php echo esc_html__( 'Total Tickets Ordered', 'event-tickets' ); ?>
								</div>
							</div>
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-amount">
								<?php
								printf(
									'%1$s (%2$s)',
									esc_html( $sales_totals['total_ordered']['price'] ),
									esc_html( $sales_totals['total_ordered']['qty'] )
								);
								?>
							</div>
						</div>
						<div class="tec-tickets__admin-orders-report__sales-overview__list__item">
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-label">
								<div class="tec-tickets__admin-orders-report__sales-overview__total-label">
									<?php echo esc_html__( 'Total Fees Collected', 'event-tickets' ); ?>
								</div>
							</div>
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-amount">
								<?php
								printf(
									'%1$s (%2$s)',
									esc_html( $sales_totals['total_sales']['total_fees'] ),
									esc_html( $sales_totals['total_sales']['fees_qty'] )
								);
								?>
							</div>
						</div>
						<div class="tec-tickets__admin-orders-report__sales-overview__list__item">
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-label">
								<div class="tec-tickets__admin-orders-report__sales-overview__total-label">
									<?php echo esc_html__( 'Total Discounts', 'event-tickets' ); ?>
								</div>
							</div>
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-amount">
								<?php
								printf(
									'%1$s (%2$s)',
									esc_html( $sales_totals['total_sales']['total_discounts'] ),
									esc_html( $sales_totals['total_sales']['discounts_qty'] )
								);
								?>
							</div>
						</div>
						<div class="tec-tickets__admin-orders-report__sales-overview__list__item">
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-label">
								<div class="tec-tickets__admin-orders-report__sales-overview__total-label">
									<?php echo esc_html__( 'Total Ticket Sales', 'event-tickets' ); ?>
								</div>
							</div>
							<div class="tec-tickets__admin-orders-report__sales-overview__list__item-amount">
								<?php echo esc_html( sprintf( '%1$s (%2$s)', $sales_totals['total_sales']['price'], $sales_totals['total_sales']['qty'] ) ); ?>
							</div>
						</div>
					</div>
					<?php
					/**
					 * Fires after sales breakdown in the Orders Report admin view.
					 *
					 * @since 5.6.7
					 *
					 * @param WP_Post $post_id The current post ID.
					 */
					do_action( 'tec_tickets_commerce_order_report_after_sales_breakdown', $post_id );
					?>
				</div>
			</div>
		</div>
	</div>
</div>
