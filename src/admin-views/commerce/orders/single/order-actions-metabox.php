<?php
/**
 * Single order - Actions metabox.
 *
 * @since 5.13.3
 *
 * @version 5.13.3
 *
 * @var WP_Post                                         $order             The current post object.
 * @var \TEC\Tickets\Commerce\Admin\Singular_Order_Page $single_page       The orders table output.
 */

use TEC\Tickets\Commerce\Status\Status_Handler;

$possible_statuses = tribe( Status_Handler::class )->get_orders_possible_status( $order );

?>
<div class="submitbox" id="submitpost">

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

	<div id="minor-publishing">

		<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key. ?>
		<div style="display:none;">
			<?php submit_button( __( 'Save', 'event-tickets' ), '', 'save' ); ?>
		</div>
		<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">
		<?php
		/**
		 * Fires at the beginning of the publishing actions section of the Actions meta box.
		 *
		 * @since 5.13.3
		 *
		 * @param WP_Post $order WP_Post object for the current post on Edit Post screen..
		 */
		do_action( 'tribe_tickets_commerce_order_actions_box_start', $order );
		?>
		<div id="delete-action">
			<?php
			if ( current_user_can( 'delete_post', $order->ID ) ) {
				?>
				<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $order->ID ) ); ?>">
					<?php echo ! EMPTY_TRASH_DAYS ? esc_html__( 'Delete permanently', 'event-tickets' ) : esc_html__( 'Move to Trash', 'event-tickets' ); ?>
				</a>
				<?php
			}
			?>
		</div>

		<div id="publishing-action">
			<span class="spinner"></span>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update', 'event-tickets' ); ?>" />
			<?php submit_button( __( 'Update', 'event-tickets' ), 'primary large', 'save', false, [ 'id' => 'publish' ] ); ?>
		</div>
		<div class="clear"></div>
	</div>

</div>
<?php
