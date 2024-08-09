<?php
/**
 * Single order - Actions metabox.
 *
 * @var WP_Post                                         $order             The current post object.
 * @var \TEC\Tickets\Commerce\Admin\Singular_Order_Page $single_page       The orders table output.
 */

use TEC\Tickets\Commerce\Status\Status_Handler;

$possible_statuses = tribe( Status_Handler::class )->get_orders_possible_status( $order );

?>
<div class="tec-tickets-commerce-single-order--actions">
	<button class="button button-secondary"><?php esc_html_e( 'Email order details to purchaser', 'event-tickets' ); ?></button>
	<?php if ( ! empty( $possible_statuses ) ) : ?>
		<div class="tec-tickets-commerce-single-order--actions--status">
			<label for="tribe-tickets-commerce-status-selector">
				<?php esc_html_e( 'Status', 'event-tickets' ); ?>
			</label>
			<select id="tribe-tickets-commerce-status-selector" name="tribe-tickets-commerce-status">
				<option value=""><?php esc_html_e( 'Select status', 'event-tickets' ); ?></option>
				<?php foreach ( $possible_statuses as $possible_status ) : ?>
					<option <?php selected( $order->post_status, $possible_status->get_wp_slug() ); ?>value="<?php echo esc_attr( $possible_status->get_slug() ); ?>">
						<?php echo esc_html( $possible_status->get_name() ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	<?php endif; ?>
</div>
<?php
