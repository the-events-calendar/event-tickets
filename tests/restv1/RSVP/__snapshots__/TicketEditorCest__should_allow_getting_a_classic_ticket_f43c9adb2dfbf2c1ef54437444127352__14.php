<?php return '
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
						value="Test RSVP name"
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
							 checked=\'checked\'						>
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
			value="0"
						data-validation-error="Ticket price must be greater than zero."		/>
					<p class="description ticket_form_right">
				Leave blank for free Ticket			</p>
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
		value=\'13\'
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
		>Test description text</textarea>
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
				value="1/2/2020"
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
				value="08:00:00"
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
				value="3/1/2050"
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
				value="20:00:00"
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
		<div id="Tribe__Tickets__Commerce__PayPal__Main_advanced" class="tribe-dependent" data-depends="#Tribe__Tickets__Commerce__PayPal__Main_radio" data-condition-is-checked></div>	</div>
</section><!-- #ticket_form_advanced -->

				
							</div>

						<div class="ticket_bottom">
				<input
					type="hidden"
					name="ticket_id"
					id="ticket_id"
					class="ticket_field"
					value="2"
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
					<a href="http://test.tribe.dev/wp-admin/post.php?post=1&action=edit&dialog=move_ticket_types&ticket_type_id=2&check=nonceABC&TB_iframe=true" class="thickbox tribe-ticket-move-link">Move RSVP</a> | <span><a href="#" attr-provider="Tribe__Tickets__RSVP" attr-ticket-id="2" id="ticket_delete_2" class="ticket_delete">Delete RSVP</a></span>				</div>
			</div>
		</div>
	</div>
</div>
';
