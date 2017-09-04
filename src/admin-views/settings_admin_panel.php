<div id="tribe_panel_settings" class="ticket_panel panel_settings" aria-hidden="true" >
	<h4><?php esc_html_e( 'Ticket Settings', 'event-tickets' ); ?></h4>

	<section class="settings_main">
		<?php
		/**
		 * Allows for the insertion of additional elements into the ticket settings admin panel below the ticket table
		 *
		 * @param Post ID
		 * @since TBD
		 */
		do_action( 'tribe_events_tickets_settings_content', $post_id );
		?>
	</section>
	<section id="tribe-tickets-image">
		<div class="tribe-tickets-image-upload">
			<div class="input_block">
				<span class="ticket_form_label"><?php esc_html_e( 'Ticket header image:', 'event-tickets' ); ?></span>
				<p class="description"><?php esc_html_e( 'Select an image from your media library to display on emailed tickets. For best results, use a .jpg, .png, or .gif at least 1160px wide.', 'event-tickets' ); ?></p>
			</div>
			<input type="button" class="button" name="tribe_ticket_header_image" id="tribe_ticket_header_image" value="<?php esc_html_e( 'Select an Image', 'event-tickets' ); ?>" />
			<span id="tribe_tickets_image_preview_filename"><span class="dashicons dashicons-format-image"></span><span class="filename"><?php echo $header_filename; ?></span></span>
		</div>
		<div class="tribe-tickets-image-preview">
				<a class="tribe_preview" id="tribe_ticket_header_preview">
					<?php
					// Can't escape - mixed html
					echo $header_img;
					?>
				</a>
				<p class="description"><a href="#" id="tribe_ticket_header_remove"><?php esc_html_e( 'Remove', 'event-tickets' ); ?></a></p>

				<input type="hidden" id="tribe_ticket_header_image_id" class="settings_field" name="tribe_ticket_header_image_id" value="<?php echo esc_attr( $header_id ); ?>" />
		</div>
	</section>

	<input type="button" id="tribe_settings_form_save" name="tribe_settings_form_save" value="<?php esc_attr_e( 'Save settings', 'event-tickets' ); ?>" class="button-primary" />
	<input type="button" id="tribe_settings_form_cancel" name="tribe_settings_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" class="button-secondary" />
</div><!-- #tribe_panel_settings -->
