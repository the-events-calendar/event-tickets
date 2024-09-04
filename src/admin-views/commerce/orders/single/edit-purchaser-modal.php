<?php
/**
 * Purchaser edit modal.
 *
 * @since TBD
 *
 * @version TBD
 *
 *
 */



?>
<form id="tec-tickets-commerce-edit-purchaser-form">
		<?php wp_nonce_field('my_plugin_save_meta_box_data', 'my_plugin_meta_box_nonce'); ?>
	<div>
		<label for="tec-tickets-commerce-edit-purchaser-name">
			<?php esc_html_x( 'Purchaser name*', 'Field label for the purchaser name.', 'event-tickets' ); ?>
		</label>
		<input type="text" required="required" name="" id="tec-tickets-commerce-edit-purchaser-name" class="" />
		<label for="tec-tickets-commerce-edit-purchaser-email">
			<?php esc_html_x( 'Purchaser email*', 'Field label for the purchaser email.', 'event-tickets' ); ?>
		</label>
		<input type="email" required="required" name="" id="tec-tickets-commerce-edit-purchaser-email" class="" />
	</div>
</form>
