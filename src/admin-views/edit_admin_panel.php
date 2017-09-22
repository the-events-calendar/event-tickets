<?php
$timepicker_step = 30;
$timepicker_round = '00:00:00';

$start_date_errors = array(
	'is-required' => __( 'Start sale date cannot be empty.', 'event-tickets' ),
	'is-greater-or-equal-to' => __( 'Start sale date cannot be greater than End Sale date', 'event-tickets' ),
);
$end_date_errors = array(
	'is-required' => __( 'End sale date cannot be empty.', 'event-tickets' ),
	'is-greater-or-equal-to' => __( 'End sale date cannot be less than Start sale date', 'event-tickets' ),
);
?>

<div id="tribe_panel_edit" class="ticket_panel panel_edit tribe-validation" aria-hidden="true">
	<?php
	/**
	 * Allows for the insertion of additional elements into the main ticket edit panel
	 *
	 * @since TBD
	 *
	 * @param int Post ID
	 */
	do_action( 'tribe_events_tickets_pre_edit', $post_id );
	?>

	<div id="ticket_form" class="ticket_form tribe_sectionheader">
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
					<?php esc_html_e( 'Add new ticket', 'event-tickets' ); ?>
				</h4>
				<h4
					id="ticket_title_edit"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-not-empty
				>
					<?php esc_html_e( 'Edit ticket', 'event-tickets' ); ?>
				</h4>
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
					<?php esc_html_e( 'Add new RSVP', 'event-tickets' ); ?>
				</h4>
				<h4
					id="rsvp_title_edit"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-not-empty
				>
					<?php esc_html_e( 'Edit RSVP', 'event-tickets' ); ?>
				</h4>
			</div>
			<section id="ticket_form_main" class="main">
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left" for="ticket_name"><?php esc_html_e( 'Type:', 'event-tickets' ); ?></label>
					<input
						type='text'
						id='ticket_name'
						name='ticket_name'
						class="ticket_field ticket_form_right"
						size='25'
						value=''
						data-validation-is-required
						data-validation-error="<?php esc_attr_e( 'Ticket Type cannot be empty.', 'event-tickets' ); ?>"
					/>
					<span class="tribe_soft_note ticket_form_right"><?php esc_html_e( 'Ticket type name shows on the front end and emailed tickets', 'event-tickets' ); ?></span>
				</div>
				<fieldset id="tribe_ticket_provider_wrapper" class="input_block" aria-hidden="true" >
					<legend class="ticket_form_label"><?php esc_html_e( 'Sell using:', 'event-tickets' ); ?></legend>
					<?php foreach ( $modules as $class => $module ) : ?>
						<input
							type="radio"
							name="ticket_provider"
							id="<?php echo esc_attr( $class . '_radio' ); ?>"
							value="<?php echo esc_attr( $class ); ?>"
							class="ticket_field ticket_provider"
							tabindex="-1"
						>
						<span>
							<?php
							/**
							 * Allows for the editing of the module name before output
							 *
							 * @since TBD
							 *
							 * @param string $module the module name
							 */
							echo esc_html( apply_filters( 'tribe_events_tickets_module_name', $module ) );
							?>
						</span>
					<?php endforeach; ?>
				</fieldset>
				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form - main section
				 *
				 * @since TBD
				 *
				 * @param int Post ID
				 * @param null Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, null ); ?>
			</section>
			<div class="accordion">
				<div class="accordion-header tribe_advanced_meta">
					<?php esc_html_e( 'Advanced', 'event-tickets' ); ?>
				</div>
				<section id="ticket_form_advanced" class="advanced accordion-content">
					<h4 class="accordion-label screen_reader_text"><?php esc_html_e( 'Advanced Settings', 'event-tickets' ); ?></h4>
					<div class="input_block">
						<label class="ticket_form_label ticket_form_left" for="ticket_description"><?php esc_html_e( 'Description:', 'event-tickets' ); ?></label>
						<textarea rows="5" cols="40" name="ticket_description" class="ticket_field ticket_form_right" id="ticket_description"></textarea>
						<div class="input_block">
							<label class="tribe_soft_note"><input type="checkbox" id="tribe_tickets_show_description" name="ticket_show_description" value="1" class="ticket_field ticket_form_left" checked> <?php esc_html_e( 'Show description on front end and emailed tickets.', 'event-tickets' ); ?></label>
						</div>
					</div>
					<div class="input_block">
						<label class="ticket_form_label ticket_form_left" for="ticket_start_date"><?php esc_html_e( 'Start sale:', 'event-tickets' ); ?></label>
						<div class="ticket_form_right">
							<input
								autocomplete="off"
								tabindex="<?php tribe_events_tab_index(); ?>"
								type="text"
								class="tribe-datepicker tribe-field-start_date ticket_field"
								name="ticket_start_date"
								id="ticket_start_date"
								value=""
								data-validation-is-required
								data-validation-type="datepicker"
								data-validation-is-less-or-equal-to="#ticket_end_date"
								data-validation-error="<?php echo esc_attr( json_encode( $start_date_errors ) ) ?>"
							/>
							<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ) ?></span>
							<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
							<input
								autocomplete="off"
								tabindex="<?php tribe_events_tab_index(); ?>"
								type="text"
								class="tribe-timepicker tribe-field-start_time ticket_field"
								name="ticket_start_time"
								id="ticket_start_time"
								<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : '' ?>
								data-step="<?php echo esc_attr( $timepicker_step ); ?>"
								data-round="<?php echo esc_attr( $timepicker_round ); ?>"
								value=""
								data-validation-is-required
								data-validation-error="<?php esc_attr_e( 'Start sale time cannot be empty.', 'event-tickets' ) ?>"
							/>
							<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ) ?></span>
							<span class="tooltip_container">
								<span class="dashicons dashicons-editor-help"></span>
								<span class="tooltip">
									<?php esc_html_e( 'If you don\'t set a start/end date for sales, tickets will be available from now until the event ends.', 'event-tickets' ); ?>
								</span>
							</span>
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
								data-validation-is-required
								data-validation-type="datepicker"
								data-validation-is-greater-or-equal-to="#ticket_start_date"
								data-validation-error="<?php echo esc_attr( json_encode( $end_date_errors ) ) ?>"
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
								data-validation-is-required
								data-validation-error="<?php esc_attr_e( 'End sale time cannot be empty.', 'event-tickets' ) ?>"
							/>
							<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ) ?></span>

							<span class="tooltip_container">
								<span class="dashicons dashicons-editor-help"></span>
								<span class="tooltip">
									<?php esc_html_e( 'If you don\'t set a start/end date for sales, tickets will be available from now until the event ends.', 'event-tickets' ); ?>
								</span>
							</span>
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
				<?php
				/**
				 * Allows for the insertion of additional content sections into the ticket edit form accordion
				 *
				 * @since TBD
				 *
				 * @param int Post ID
				 * @param null Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_edit_accordion_content', $post_id, null );
				?>
			</div> <!-- //.accordion -->

			<?php
			/**
			 * Allows for the insertion of additional elements into the main ticket edit panel below the accordion section
			 *
			 * @since TBD
			 *
			 * @param int Post ID
			 */
			do_action( 'tribe_events_tickets_post_accordion', $post_id );
			?>
			<div class="ticket_bottom">
				<input type="hidden" name="ticket_id" id="ticket_id" class="ticket_field" />
				<input
					type="button"
					id="ticket_form_save"
					class="button-primary tribe-dependent tribe-validation-submit"
					name="ticket_form_save"
					value="<?php esc_attr_e( 'Save ticket', 'event-tickets' ); ?>"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-not-checked
				/>
				<input
					type="button"
					id="rsvp_form_save"
					class="button-primary tribe-dependent tribe-validation-submit"
					name="ticket_form_save"
					value="<?php esc_attr_e( 'Save RSVP', 'event-tickets' ); ?>"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-checked
				/>
				<input type="button" id="ticket_form_cancel" class="button-secondary" name="ticket_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" />

				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form bottom (buttons) section
				 *
				 * @since TBD
				 *
				 * @param int Post ID
				 */
				do_action( 'tribe_events_tickets_bottom', $post_id );
				?>

				<div id="ticket_bottom_right">
					<?php
					/**
					 * Allows for the insertion of additional content into the ticket edit form bottom (links on right) section
					 *
					 * @since TBD
					 *
					 * @param int Post ID
					 */
					do_action( 'tribe_events_tickets_bottom_right', $post_id );
					?>
				</div>
			</div>

		</div><!-- #ticket_form_table -->
	</div><!-- #ticket_form -->
</div><!-- #tribe_panel_edit -->
