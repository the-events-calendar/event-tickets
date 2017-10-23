<button class="accordion-header tribe_advanced_meta">
	<?php esc_html_e( 'Advanced', 'event-tickets' ); ?>
</button>
<section id="ticket_form_advanced" class="advanced accordion-content" data-datepicker_format="<?php echo esc_attr( tribe_get_option( 'datepickerFormat' ) ); ?>">
	<h4 class="accordion-label screen_reader_text"><?php esc_html_e( 'Advanced Settings', 'event-tickets' ); ?></h4>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_description"><?php esc_html_e( 'Description:', 'event-tickets' ); ?></label>
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
					checked
				>
				<?php esc_html_e( 'Show description on front end ticket form.', 'event-tickets' ); ?>
			</label>
		</div>
	</div>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_start_date"><?php esc_html_e( 'Start sale:', 'event-tickets' ); ?></label>
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
				data-validation-error="<?php echo esc_attr( json_encode( $start_date_errors ) ) ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ) ?></span>
			<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-start_time ticket_field"
				name="ticket_start_time"
				id="ticket_start_time"
				<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : '' ?>
				data-step="<?php echo esc_attr( $timepicker_step ); ?>"
				data-round="<?php echo esc_attr( $timepicker_round ); ?>"
				value=""
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ) ?></span>
			<span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'If you do not set a start sale date, tickets will be available immediately.', 'event-tickets' ); ?>"></span>
		</div>
	</div>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_end_date"><?php esc_html_e( 'End sale:', 'event-tickets' ); ?></label>
		<div class="ticket_form_right">
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-end_date ticket_field"
				name="ticket_end_date"
				id="ticket_end_date"
				value=""
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ) ?></span>
			<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-end_time ticket_field"
				name="ticket_end_time"
				id="ticket_end_time"
				<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : '' ?>
				data-step="<?php echo esc_attr( $timepicker_step ); ?>"
				data-round="<?php echo esc_attr( $timepicker_round ); ?>"
				value=""
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ) ?></span>
			<span
				class="dashicons dashicons-editor-help"
				<?php if ( 'tribe_event' === get_post_type( $post_id ) ) : ?>
					title="<?php esc_attr_e( 'If you do not set an end sale date, tickets will be available until the event begins.', 'event-tickets' ); ?>"
				<?php else : ?>
					title="<?php esc_attr_e( 'If you do not set an end sale date, tickets sales will never end.', 'event-tickets' ); ?>"
				<?php endif; ?>
			></span>
		</div>
	</div>
	<div id="advanced_fields">
		<?php
		/**
		 * Allows for the insertion of additional content into the ticket edit form - advanced section
		 *
		 * @since TBD
		 *
		 * @param int Post ID
		 * @param null Ticket ID
		 */
		do_action( 'tribe_events_tickets_metabox_edit_advanced', $post_id, null );
		?>
	</div>
</section><!-- #ticket_form_advanced -->
