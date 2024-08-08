<?php
/**
 * Single order - Actions metabox.
 *
 * @var WP_Post                                         $order             The current post object.
 * @var \TEC\Tickets\Commerce\Admin\Singular_Order_Page $single_page       The orders table output.
 */

?>
<div>
	<button class="button button-secondary"><?php esc_html_e( 'Email order details to purchaser', 'event-tickets' ); ?></button>
	<div>
		<label for="tribe-tickets-commerce-status-selector">
			<?php esc_html_e( 'Status', 'event-tickets' ); ?>
		</label>
		<select id="tribe-tickets-commerce-status-selector" name="tribe-tickets-commerce-status">
			<option value=""><?php esc_html_e( 'Select status', 'event-tickets' ); ?></option>
			<option value="completed"><?php esc_html_e( 'Completed', 'event-tickets' ); ?></option>
			<option value="refunded"><?php esc_html_e( 'Refunded', 'event-tickets' ); ?></option>
			<option value="cancelled"><?php esc_html_e( 'Cancelled', 'event-tickets' ); ?></option>
		</select>
	</div>
</div>
<?php
