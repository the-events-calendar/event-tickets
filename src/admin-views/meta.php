<tr class="tribe-tickets-show-attendee-info ticket_advanced ticket_advanced_meta">
	<td style="width: 20%;">
		<?php _e( 'Attendee Information:', 'tribe-events-calendar' ); ?>
	</td>
	<td>
		<?php /* save as checked if the ticket has attendee info */ ?>
		<label><input type="checkbox" name="show_attendee_info" <?php checked( ! empty( $active_meta ) ); ?> id="show_attendee_info" class="ticket_field show_attendee_info"> <?php _e( 'Request information for each attendee during registration', 'tribe-events-calendar' ); ?>
		</label>
	</td>
</tr>
<tr class="eventtable tribe-tickets-attendee-info-form ticket_advanced ticket_advanced_meta">
	<td colspan="2">
		<table class="eventtable">
			<tr>
				<td colspan="2" class="titlewrap">
					<h4 class="tribe_sectionheader">Attendee Information</h4>
				</td>
			</tr>

			<tr class="tribe-attendee-fields-box">
				<td style="width:20%">
					<h5><?php _e( 'Add New Field:', 'tribe-events-calendar' ); ?></h5>
					<ul class="tribe-tickets-attendee-info-options">
						<li id="tribe-tickets-add-text" class="tribe-tickets-attendee-info-option">
							<a href="#" class="add-attendee-field" data-type="text">Text <span class="dashicons dashicons-plus-alt"></span></a>
						</li>
						<li id="tribe-tickets-add-number" class="tribe-tickets-attendee-info-option">
							<a href="#" class="add-attendee-field" data-type="number">Number <span class="dashicons dashicons-plus-alt"></span></a>
						</li>
						<li id="tribe-tickets-add-radio" class="tribe-tickets-attendee-info-option">
							<a href="#" class="add-attendee-field" data-type="radio">Radio <span class="dashicons dashicons-plus-alt"></span></a>
						</li>
						<li id="tribe-tickets-add-checkbox" class="tribe-tickets-attendee-info-option">
							<a href="#" class="add-attendee-field" data-type="checkbox">Checkbox <span class="dashicons dashicons-plus-alt"></span></a>
						</li>
						<li id="tribe-tickets-add-select" class="tribe-tickets-attendee-info-option">
							<a href="#" class="add-attendee-field" data-type="select">Select <span class="dashicons dashicons-plus-alt"></span></a>
						</li>
					</ul>

				</td>
				<td>
					<h5><?php _e( 'Active Fields:', 'tribe-events-calendar' ); ?></h5>
					<div class="tribe-tickets-attendee-saved-fields">
						<div class="tribe-tickets-saved-fields-select">
							<p>No active fields. Add new fields or</p>

							<select class="chosen ticket-attendee-info-dropdown" name="ticket-attendee-info[MetaID]" id="saved_ticket-attendee-info" title="Start with a saved fieldset..." >

									<option selected value="0">Start with a saved fieldset...</option>
									<?php foreach ( $templates as $template ): ?>
										<option data-attendee-group="<?php echo esc_attr( $template ); ?>"
										        value="<?php echo esc_attr( $template ); ?>"><?php echo esc_attr( $template ); ?></option>
									<?php endforeach; ?>
							</select>
						</div>
						
					</div>
					<div id="tribe-tickets-attendee-sortables" class="meta-box-sortables ui-sortable">
						<?php
						foreach ( $active_meta as $meta ) {
							echo $this->get_render_field( $meta['type'], $meta );
						}
						?>

					</div>
					<div class="tribe-tickets-input tribe-tickets-attendee-save-fieldset">
						<label>
							<input type="checkbox" name="tribe-tickets-save-fieldset" id="save_attendee_fieldset" value="on" class="ticket_field save_attendee_fieldset"> Save this fieldset for use on other tickets?
						</label>
					</div>
					<div class="tribe-tickets-input tribe-tickets-attendee-saved-fieldset-name">
							<label for="tribe-tickets-saved-fieldset-name">Name this fieldset:</label>
							<input type="text" class="ticket_field" name="tribe-tickets-saved-fieldset-name" value="">
					</div>
				</td>
			</tr>
		</table>
	</td>
</tr>
