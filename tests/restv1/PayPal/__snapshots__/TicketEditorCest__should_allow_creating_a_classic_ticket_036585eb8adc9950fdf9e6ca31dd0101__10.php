<?php return '<div
	id="tribe_panel_base"
	class="ticket_panel panel_base"
	aria-hidden="false"
	data-save-prompt="You have unsaved changes to your tickets. Discard those changes?"
>
	<div class="tribe_sectionheader ticket_list_container">
					<div class="ticket_table_intro">
				
	<a
		href="http://test.tribe.dev/wp-admin/edit.php?post_type=post&#038;page=tpp-orders&#038;event_id=1"
		class="button-secondary"
	>
		View Orders	</a>
				<a
					class="button-secondary"
					href="http://test.tribe.dev/wp-admin/edit.php?page=tickets-attendees&#038;event_id=1"
				>
					View Attendees				</a>
			</div>
			


	<table id="tribe_ticket_list_table" class="tribe-tickets-editor-table eventtable ticket_list eventForm widefat fixed">
		<thead>
			<tr class="table-header">
				<th class="ticket_name column-primary">Tickets</th>
				<th class="ticket_price">Price</th>
				<th class="ticket_capacity">Capacity</th>
				<th class="ticket_available">Available</th>
				<th class="ticket_edit"></th>
			</tr>
		</thead>
				<tbody class="tribe-tickets-editor-table-tickets-body">
			<tr class="Tribe__Tickets__Commerce__PayPal__Main is-expanded" data-ticket-order-id="order_2" data-ticket-type-id="2">
	<td class="column-primary ticket_name Tribe__Tickets__Commerce__PayPal__Main" data-label="Ticket Type:">
		<span class="dashicons dashicons-screenoptions tribe-handle"></span>
		<input
			type="hidden"
			class="tribe-ticket-field-order"
			name="tribe-tickets[list][2][order]"
			value="0"
					>

		
		Test ticket name
			</td>

	<td class="ticket_price" data-label="Price:">
	<span class="tribe-tickets-price-amount amount">&#x24;12.00</span></td>

	<td class="ticket_capacity">
		<span class=\'tribe-mobile-only\'>Capacity:</span>
		12	</td>

	<td class="ticket_available">
		<span class=\'tribe-mobile-only\'>Available:</span>
				12	</td>

	<td class="ticket_edit">
		<button data-provider=\'Tribe__Tickets__Commerce__PayPal__Main\' data-ticket-id=\'2\' title=\'Ticket ID: 2\' class=\'ticket_edit_button\'><span class=\'ticket_edit_text\'>Test ticket name</span></a>	</td>
</tr>
		</tbody>

		<tbody>
					</tbody>
	</table>

			</div>
	<div class="tribe-ticket-control-wrap">
		
		<button
			id="ticket_form_toggle"
			class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
			aria-label="Add a new ticket"
			""
		>
		New ticket		</button>

		<button
			id="rsvp_form_toggle"
			class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
			aria-label="Add a new RSVP"
		>
			New RSVP		</button>


		<button id="settings_form_toggle" class="button-secondary tribe-button-icon tribe-button-icon-settings">
			Settings		</button>

		
	</div>
	
</div>

------------


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


------------


<div id="tribe_panel_edit" class="ticket_panel panel_edit tribe-validation" aria-hidden="true" data-default-provider="Tribe__Tickets__Commerce__PayPal__Main">
	
	<div id="ticket_form" class="ticket_form tribe_sectionheader tribe-validation">
		<div id="ticket_form_table" class="eventtable ticket_form">
			<div
				class="tribe-dependent"
				data-depends="#Tribe__Tickets__RSVP_radio"
				data-condition-is-not-checked
			>
				<h4
					id="ticket_title_add"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-empty
				>
					Add new ticket				</h4>
				<h4
					id="ticket_title_edit"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-not-empty
				>
					Edit ticket				</h4>
			</div>
			<div
				class="tribe-dependent"
				data-depends="#Tribe__Tickets__RSVP_radio"
				data-condition-is-checked
			>
				<h4
					id="rsvp_title_add"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-empty
				>
					Add new RSVP				</h4>
				<h4
					id="rsvp_title_edit"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-not-empty
				>
					Edit RSVP				</h4>
			</div>
			<section id="ticket_form_main" class="main">
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left" for="ticket_name">Type:</label>
					<input
						type=\'text\'
						id=\'ticket_name\'
						name=\'ticket_name\'
						class="ticket_field ticket_form_right"
						size=\'25\'
						value=""
						data-validation-is-required
						data-validation-error="RSVP type is a required field"
					/>
					<span
						class="tribe_soft_note ticket_form_right"
						data-depends="#Tribe__Tickets__RSVP_radio"
						data-condition-not-checked
					>Ticket type name shows on the front end and emailed tickets					</span>
					<span
						class="tribe_soft_note ticket_form_right"
						data-depends="#Tribe__Tickets__RSVP_radio"
						data-condition-is-checked
					>RSVP type name shows on the front end and emailed rsvps					</span>
				</div>
				<fieldset id="tribe_ticket_provider_wrapper" class="input_block" aria-hidden="true" >
					<legend class="ticket_form_label">Sell using:</legend>
											<input
							type="radio"
							name="ticket_provider"
							id="Tribe__Tickets__RSVP_radio"
							value="Tribe__Tickets__RSVP"
							class="ticket_field ticket_provider"
							tabindex="-1"
													>
						<span>
							RSVPs						</span>
											<input
							type="radio"
							name="ticket_provider"
							id="Tribe__Tickets__Commerce__PayPal__Main_radio"
							value="Tribe__Tickets__Commerce__PayPal__Main"
							class="ticket_field ticket_provider"
							tabindex="-1"
													>
						<span>
							Tribe Commerce						</span>
									</fieldset>
				<div
	class="price tribe-dependent"
		data-depends="#Tribe__Tickets__RSVP_radio"
	data-condition-is-not-checked
	>
	<div class="input_block">
		<label for="ticket_price" class="ticket_form_label ticket_form_left">Price:</label>
		<input
			type="text"
			id="ticket_price"
			name="ticket_price"
			class="ticket_field ticket_form_right"
			size="7"
			value=""
						data-validation-error="Ticket price must be greater than zero."		/>
			</div>

		</div><div
	class="input_block ticket_advanced_Tribe__Tickets__RSVP tribe-dependent"
	data-depends="#Tribe__Tickets__RSVP_radio"
	data-condition-is-checked
>
	<label
		for="Tribe__Tickets__RSVP_capacity"
		class="ticket_form_label ticket_form_left"
	>
		Capacity:	</label>
	<input
		type=\'text\' id=\'Tribe__Tickets__RSVP_capacity\'
		name=\'tribe-ticket[capacity]\'
		class="ticket_field tribe-rsvp-field-capacity ticket_form_right"
		size=\'7\'
		value=\'\'
	/>
	<span class="tribe_soft_note ticket_form_right">Leave blank for unlimited</span>
</div>

<div
	class="input_block ticket_advanced_Tribe__Tickets__Commerce__PayPal__Main tribe-dependent"
	data-depends="#Tribe__Tickets__Commerce__PayPal__Main_radio"
	data-condition-is-checked
>
	<label
		for="Tribe__Tickets__Commerce__PayPal__Main_capacity"
		class="ticket_form_label ticket_form_left"
	>
		Capacity:	</label>
	<input
		type=\'text\' id=\'Tribe__Tickets__Commerce__PayPal__Main_capacity\'
		name=\'tribe-ticket[capacity]\'
		class="ticket_field tribe-tpp-field-capacity ticket_form_right"
		size=\'7\'
		value=\'\'
	/>
	<span class="tribe_soft_note ticket_form_right">Leave blank for unlimited</span>
</div>
			</section>

			<div class="accordion">
				<button class="accordion-header tribe_advanced_meta">
	Advanced</button>
<section id="ticket_form_advanced" class="advanced accordion-content" data-datepicker_format="1">
	<h4 class="accordion-label screen_reader_text">Advanced Settings</h4>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_description">Description:</label>
		<textarea
			rows="5"
			cols="40"
			name="ticket_description"
			class="ticket_field ticket_form_right"
			id="ticket_description"
		></textarea>
		<div class="input_block">
			<label class="tribe_soft_note">
				<input
					type="checkbox"
					id="tribe_tickets_show_description"
					name="ticket_show_description"
					value="1"
					class="ticket_field ticket_form_left"
					 checked=\'checked\'				>
				Show description on front end ticket form.			</label>
		</div>
	</div>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_start_date">Start sale:</label>
		<div class="ticket_form_right">
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-start_date ticket_field"
				name="ticket_start_date"
				id="ticket_start_date"
				value=""
				data-validation-type="datepicker"
				data-validation-is-less-or-equal-to="#ticket_end_date"
				data-validation-error="{&quot;is-required&quot;:&quot;Start sale date cannot be empty.&quot;,&quot;is-less-or-equal-to&quot;:&quot;Start sale date cannot be greater than End Sale date&quot;}"
			/>
			<span class="helper-text hide-if-js">YYYY-MM-DD</span>
			<span class="datetime_seperator"> at </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-start_time ticket_field"
				name="ticket_start_time"
				id="ticket_start_time"
								data-step="30"
				data-round="00:00:00"
				value=""
				aria-label="Ticket start date"
			/>
			<span class="helper-text hide-if-js">HH:MM</span>
			<span class="dashicons dashicons-editor-help" title="If you do not set a start sale date, tickets will be available immediately.">
			</span>
		</div>
	</div>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_end_date">End sale:</label>
		<div class="ticket_form_right">
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-end_date ticket_field"
				name="ticket_end_date"
				id="ticket_end_date"
				value=""
			/>
			<span class="helper-text hide-if-js">YYYY-MM-DD</span>
			<span class="datetime_seperator"> at </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-end_time ticket_field"
				name="ticket_end_time"
				id="ticket_end_time"
								data-step="30"
				data-round="00:00:00"
				value=""
				aria-label="Ticket end date"
			/>
			<span class="helper-text hide-if-js">HH:MM</span>
			<span
				class="dashicons dashicons-editor-help"
									title="If you do not set an end sale date, tickets will be available forever."
							></span>
		</div>
	</div>
	<div id="advanced_fields">
		<div id="Tribe__Tickets__Commerce__PayPal__Main_advanced" class="tribe-dependent" data-depends="#Tribe__Tickets__Commerce__PayPal__Main_radio" data-condition-is-checked>
<div class="ticket_advanced_Tribe__Tickets__Commerce__PayPal__Main input_block tribe-dependent"
     data-depends="#Tribe__Tickets__Commerce__PayPal__Main_radio"
     data-condition-is-checked
>
	<label for="ticket_tpp_sku" class="ticket_form_label ticket_form_left">SKU:</label>
	<input
		type="text"
		id="ticket_sku"
		name="ticket_sku"
		class="ticket_field sku_input ticket_form_right"
		size="14"
		value=""
	>
	<p class="description ticket_form_right">
		A unique identifying code for each ticket type you&#039;re selling	</p>
</div>
</div>	</div>
</section><!-- #ticket_form_advanced -->

				
							</div>

						<div class="ticket_bottom">
				<input
					type="hidden"
					name="ticket_id"
					id="ticket_id"
					class="ticket_field"
					value=""
				/>
				<input
					type="button"
					id="ticket_form_save"
					class="button-primary tribe-dependent tribe-validation-submit"
					name="ticket_form_save"
					value="Save ticket"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-not-checked
				/>
				<input
					type="button"
					id="rsvp_form_save"
					class="button-primary tribe-dependent tribe-validation-submit"
					name="ticket_form_save"
					value="Save RSVP"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-checked
				/>
				<input
					type="button"
					id="ticket_form_cancel"
					class="button-secondary"
					name="ticket_form_cancel"
					value="Cancel"
				/>

				
				<div id="ticket_bottom_right">
									</div>
			</div>
		</div>
	</div>
</div>


------------

<div class="wrap"><div class="updated"><p>ticket-add</p></div></div>';
