<?php return '
<div id="tribe_panel_settings" class="ticket_panel panel_settings" aria-hidden="true" >
	<h4>Ticket Settings	</h4>

	<section class="settings_main">
				
	<fieldset class="screen-reader-text">
									<input
					type="radio"
					class="tribe-ticket-editor-field-default_provider settings_field"
					name="tribe-tickets[settings][default_provider]"
					id="provider_Tribe__Tickets__Commerce__PayPal__Main_radio"
					value="Tribe__Tickets__Commerce__PayPal__Main"
					checked
				>
						</fieldset>

			</section>
	<section id="tribe-tickets-image">
		<div class="tribe-tickets-image-upload">
			<div class="input_block">
				<span class="ticket_form_label tribe-strong-label">Ticket header image:				</span>
				<p class="description">
					Select an image from your Media Library to display on emailed ticket. For best results, use a .jpg, .png, or .gif at least 1160px wide.				</p>
			</div>
			<input
				type="button"
				class="button"
				name="tribe-tickets[settings][header_image]"
				id="tribe_ticket_header_image"
				value="Select an Image"
			/>

			<span id="tribe_tickets_image_preview_filename" class="">
				<span class="dashicons dashicons-format-image"></span>
				<span class="filename"></span>
			</span>
		</div>
		<div class="tribe-tickets-image-preview">
			<a class="tribe_preview" id="tribe_ticket_header_preview">
							</a>
			<p class="description">
				<a href="#" id="tribe_ticket_header_remove">Remove</a>
			</p>

			<input
				type="hidden"
				id="tribe_ticket_header_image_id"
				class="settings_field"
				name="tribe-tickets[settings][header_image_id]"
				value=""
			/>
		</div>
	</section>

	<input type="button" id="tribe_settings_form_save" name="tribe_settings_form_save" value="Save settings" class="button-primary" />
	<input type="button" id="tribe_settings_form_cancel" name="tribe_settings_form_cancel" value="Cancel" class="button-secondary" />
</div>
';
