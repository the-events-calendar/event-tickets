<?php
/**
 * Purchaser edit modal.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var WP_Post $order The Order post.
 */



?>
<form class="tec-tickets-commerce-vertical-form" id="tec-tickets-commerce-edit-purchaser-form">
<?php $this->template( 'src/admin-views/components/loader' ); ?>
	<?php wp_nonce_field( 'tec_commerce_purchaser_edit', '_wpnonce' ); ?>
	<input type="hidden" value="<?php echo esc_attr( $order->ID );?>" name="ID" />
	<div class="tec-tickets-commerce-row">
		<label for="tec-tickets-commerce-edit-purchaser-name">
			<?php echo esc_html_x( 'Purchaser name*', 'Field label for the purchaser name.', 'event-tickets' ); ?>
		</label>
		<input type="text" required="required" name="name" id="tec-tickets-commerce-edit-purchaser-name" />
	</div>
	<div class="tec-tickets-commerce-row">
		<label for="tec-tickets-commerce-edit-purchaser-email">
			<?php echo esc_html_x( 'Purchaser email*', 'Field label for the purchaser email.', 'event-tickets' ); ?>
		</label>
		<input type="email" required="required" name="email" id="tec-tickets-commerce-edit-purchaser-email" />
	</div>
	<div class="tec-tickets-commerce-row tec-tickets-commerce-edit-purchaser-actions">
		<button type="button" id="tec-tickets-commerce-edit-purchaser-cancel" class="button button-secondary">Cancel</button>
		<button type="submit" id="tec-tickets-commerce-edit-purchaser-save" disabled class="button button-primary">Save</button>
	</div>
</form>
