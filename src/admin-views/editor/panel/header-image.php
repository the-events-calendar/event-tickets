<?php
/**
 * The template for the ticket header image upload.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 *
 * @var int $post_id The Post ID.
 */

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );
$header_id       = get_post_meta( $post_id, $tickets_handler->key_image_header, true );
$header_id       = ! empty( $header_id ) ? $header_id : '';
$header_img      = '';
$header_filename = '';

if ( ! empty( $header_id ) ) {
	$header_img      = wp_get_attachment_image( $header_id, 'full' );
	$header_filename = basename( get_attached_file( $header_id ) );
}
?>
<section id="tribe-tickets-image">
	<div class="tribe-tickets-image-upload">
		<div class="input_block">
				<span class="ticket_form_label tribe-strong-label"><?php
					echo esc_html(
						sprintf(
							_x( '%s header image:', 'ticket image upload label', 'event-tickets' ),
							tribe_get_ticket_label_singular( 'ticket_image_upload_label' )
						)
					); ?>
				</span>
			<p class="description">
				<?php
				echo esc_html(
					sprintf(
						_x(
							'Select an image from your Media Library to display on emailed %s. For best results, use a .jpg, .png, or .gif at least 1160px wide.',
							'ticket image upload label description',
							'event-tickets'
						),
						tribe_get_ticket_label_singular_lowercase( 'ticket_image_upload_label_description' )
					)
				); ?>
			</p>
		</div>
		<input
			type="button"
			class="button"
			name="tribe-tickets[settings][header_image]"
			id="tribe_ticket_header_image"
			value="<?php esc_html_e( 'Select an Image', 'event-tickets' ); ?>"
		/>

		<span id="tribe_tickets_image_preview_filename" class="<?php echo ! empty( $header_filename )? esc_attr( '-active' ): ''; ?>">
				<span class="dashicons dashicons-format-image"></span>
				<span class="filename"><?php echo esc_html( $header_filename ); ?></span>
			</span>
	</div>
	<div class="tribe-tickets-image-preview">
		<a class="tribe_preview" id="tribe_ticket_header_preview">
			<?php
			// Can't escape - mixed html
			echo $header_img;
			?>
		</a>
		<p class="description">
			<a href="#" id="tribe_ticket_header_remove"><?php esc_html_e( 'Remove', 'event-tickets' ); ?></a>
		</p>

		<input
			type="hidden"
			id="tribe_ticket_header_image_id"
			class="settings_field"
			name="tribe-tickets[settings][header_image_id]"
			value="<?php echo esc_attr( $header_id ); ?>"
		/>
	</div>
</section>
