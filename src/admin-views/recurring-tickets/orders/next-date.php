<?php
/**
 * The sales of a recurring event's next date, above the sales totals of every date on its Orders page.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/admin-views/recurring-tickets/orders/next-date.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $title The title, naming the date.
 * @var array{by_status: array<string,array{label: string, qty_sold: int, total_sales_price: string}>, total_sales: array{qty: int, price: string}} $sales The date's sales.
 */

?>
<div class="tec-tickets__admin-orders-report__sales-overview__title">
	<h3><?php echo esc_html( $title ); ?></h3>
</div>
<div class="tec-tickets__admin-orders-report__sales-overview__data">
	<div class="tec-tickets__admin-orders-report__sales-overview__by-status">
		<?php foreach ( $sales['by_status'] as $status_sales ) : ?>
			<div class="tec-tickets__admin-orders-report__sales-overview__list__item">
				<div class="tec-tickets__admin-orders-report__sales-overview__list__item-label"><?php echo esc_html( $status_sales['label'] ); ?></div>
				<div class="tec-tickets__admin-orders-report__sales-overview__list__item-amount"><?php echo esc_html( sprintf( '%1$s (%2$s)', $status_sales['total_sales_price'], $status_sales['qty_sold'] ) ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="tec-tickets__admin-orders-report__sales-overview__total">
		<div class="tec-tickets__admin-orders-report__sales-overview__list__item">
			<div class="tec-tickets__admin-orders-report__sales-overview__list__item-label">
				<div class="tec-tickets__admin-orders-report__sales-overview__total-label">
					<?php echo esc_html__( 'Total Ticket Sales', 'event-tickets' ); ?>
				</div>
			</div>
			<div class="tec-tickets__admin-orders-report__sales-overview__list__item-amount">
				<?php echo esc_html( sprintf( '%1$s (%2$s)', $sales['total_sales']['price'], $sales['total_sales']['qty'] ) ); ?>
			</div>
		</div>
	</div>
	<div class="tec-tickets__admin-orders-report-overview--border"></div>
</div>
