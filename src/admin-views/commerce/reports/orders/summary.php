<?php
/**
 * Template to render the Orders Report Summary.
 *
 * @version TBD
 *
 * @var int $post_id The current post ID.
 * @var WP_Post $post The current post object.
 * @var string $post_singular_label The post type singular label.
 * @var array $tickets A list of PayPal tickets that have at least one sale.
 * @var array $tickets_data A list of PayPal tickets that have at least one sale.
 * @var array $event_data A list of PayPal tickets that have at least one sale.
 * @var Order_Summary $order_summary The data object.
 */

use TEC\Tickets\Commerce\Reports\Data\Order_Summary;
use \TEC\Tickets\Commerce\Status\Completed;
use \TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Utils\Value;

?>
<div id="tribe-order-summary" class="welcome-panel tribe-report-panel">
	<div class="welcome-panel-content">
		<div class="welcome-panel-column-container">
			<div class="welcome-panel-column welcome-panel-first">
				<h3><?php
					echo esc_html(
						sprintf(
							_x( '%s Details', 'post type details', 'event-tickets' ),
							$post_singular_label
						)
					); ?>
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
				<div class="tec-tickets__admin-orders-report-overview-ticket-type">
					<?php
					$tickets_by_type = $order_summary->get_tickets_by_type();
					foreach ( $tickets_by_type as $type => $items ): ?>
						<div class="tec-tickets__admin-orders-report-overview-ticket-type-icon tec-tickets__admin-orders-report-overview-ticket-type-icon--default"></div>
						<div class="tec-tickets__admin-orders-report-overview-ticket-type-label">
							<?php echo esc_html( $order_summary->get_label_for_type( $type ) ); ?>
						</div>
						<div class="tec-tickets__admin-orders-report-overview-ticket-type-border"></div>
				</div>
				<ul class="tec-tickets__admin-orders-report-overview-ticket-type-list">
					<?php foreach ( $items as $item ): ?>
						<li class="tec-tickets__admin-orders-report-overview-ticket-type-list-item">
							<div>
								<span class="tec-tickets__admin-orders-report-overview-ticket-type-list-item-ticket-name">
									<?php echo esc_html( $item['label'] ) ?>
								</span>
							</div>
							<div class="tec-tickets__admin-orders-report-overview-ticket-type-list-item-stat">
								<?php echo esc_html( $item['qty_string'] ) ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
					<?php endforeach; ?>
				<ul>
					<?php
					/**
					 * @todo @juanfra We need to determine what counts as "sale" we have all the statuses here, I am currently only using
					 *       pending and completed, but we need to make sure user stories here.
					 * @todo @juanfra Raw HTML here, we need to modify the styling and add some classes.
					 */
					foreach ( $tickets as $ticket ) :
						$data = $tickets_data[ $ticket->ID ];
						$total = Value::create();
						$total->total( [
							$data['total_by_status'][ Completed::SLUG ],
							$data['total_by_status'][ Pending::SLUG ]
						] );
						$ticket_sales = sprintf(
							'%1$s: %2$s (%3$s)',
							$ticket->name,
							$total->get_currency(),
							$data['qty_by_status'][ Completed::SLUG ] + $data['qty_by_status'][ Pending::SLUG ]
						);
						?>
						<li>
							<?php echo esc_html( $ticket_sales ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="welcome-panel-column welcome-panel-last alternate">
				<div class="totals-header">
					<h3>
						<?php
						$text_total_sales = sprintf(
							esc_html__( 'Total %s Sales', 'event-tickets' ),
							tribe_get_ticket_label_singular( 'total_sales' )
						);
						$total_sales_value = Value::create( $event_data['total_by_status'][ Completed::SLUG ] );

						$totals_header = sprintf(
							'%1$s: %2$s (%3$s)',
							$text_total_sales,
							$total_sales_value->get_currency(),
							$event_data['qty_by_status'][ Completed::SLUG ]
						);
						echo esc_html( $totals_header );
						?>
					</h3>

					<div class="order-total">
						<?php
						$text_total_ordered = sprintf(
							esc_html__( 'Total %s Ordered', 'event-tickets' ),
							tribe_get_ticket_label_plural( 'total_ordered' )
						);

						$total = Value::create();
						$total->total( [
								$data['total_by_status'][ Completed::SLUG ],
								$data['total_by_status'][ Pending::SLUG ]
						] );

						$totals_header = sprintf(
							'%1$s: %2$s (%3$s)',
							$text_total_ordered,
							$total->get_currency(),
							$event_data['qty_by_status'][ Completed::SLUG ] + $event_data['qty_by_status'][ Pending::SLUG ]
						);
						echo esc_html( $totals_header );
						?>
					</div>
				</div>

				<ul id="sales_breakdown_wrapper" class="tribe-event-meta-note">
					<?php
					// Loop on all status to get items
					foreach ( $event_data['qty_by_status'] as $status_slug => $quantity ) :
						$status = tribe( \TEC\Tickets\Commerce\Status\Status_Handler::class )->get_by_slug( $status_slug );
						$total = Value::create( $event_data['total_by_status'][ $status_slug ] );
						// do not show status if no tickets
						if ( 0 >= (int) $quantity ) {
							continue;
						}
						?>
						<li>
							<strong><?php echo esc_html( $status->get_name() ) ?>:</strong>
							<?php echo esc_html( $total->get_currency() ); ?>
							<span id="total_issued">(<?php echo esc_html( $quantity ); ?>)</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</div>