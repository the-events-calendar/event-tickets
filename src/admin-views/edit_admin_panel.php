<div id="tribe_panel_edit" class="ticket_panel panel_edit" aria-hidden="true" >
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
			<?php // @TODO: Do these need to get renamed for RSVPs? ?>
			<h4 id="ticket_title_add" class="ticket_form_title"><?php esc_html_e( 'Add new ticket', 'event-tickets' ); ?></h4>
			<h4 id="ticket_title_edit" class="ticket_form_title"><?php esc_html_e( 'Edit ticket', 'event-tickets' ); ?></h4>
			<h4 id="rsvp_title_add" class="ticket_form_title"><?php esc_html_e( 'Add new RSVP', 'event-tickets' ); ?></h4>
			<h4 id="rsvp_title_edit" class="ticket_form_title"><?php esc_html_e( 'Edit RSVP', 'event-tickets' ); ?></h4>
			<section id="ticket_form_main" class="main">
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left" for="ticket_name"><?php esc_html_e( 'Type:', 'event-tickets' ); ?></label>
					<input type='text' id='ticket_name' name='ticket_name' class="ticket_field ticket_form_right" size='25' value='' />
					<span class="tribe_soft_note ticket_form_right"><?php esc_html_e( 'Ticket type name shows on the front end and emailed tickets', 'event-tickets' ); ?></span>
				</div>
				<fieldset class="input_block tribe_ticket_provider" aria-hidden="true" >
					<legend class="ticket_form_label"><?php esc_html_e( 'Sell using:', 'event-tickets' ); ?></legend>
					<?php
					$default_module = Tribe__Tickets__Tickets::get_default_module();

					foreach ( $modules as $class => $module ) {
						?>
						<input <?php checked( $default_module, $class ); ?> type="radio" name="ticket_provider" id="<?php echo esc_attr( $class . '_radio' ); ?>" value="<?php echo esc_attr( $class ); ?>" class="ticket_field ticket_provider" tabindex="-1">
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
						<?php
						$checked = false;
					}
					?>
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
				<button class="accordion-header" type="button">
					<?php esc_html_e( 'Advanced', 'event-tickets' ); ?>
				</button>
				<section id="ticket_form_advanced" class="advanced accordion-content">
					<h4 class="accordion-label"><?php esc_html_e( 'Advanced Settings', 'event-tickets' ); ?></h4>
					<div class="input_block">
						<label class="ticket_form_label ticket_form_left" for="ticket_description"><?php esc_html_e( 'Ticket Description:', 'event-tickets' ); ?></label>
						<textarea rows="5" cols="40" name="ticket_description" class="ticket_field ticket_form_right" id="ticket_description"></textarea>
						<div class="input_block">
							<label class="tribe_soft_note"><input type="checkbox" name="tribe_show_description" value="1" class="ticket_form_left"> Show description on front end and emailed tickets.</label>
						</div>
					</div>
					<div class="input_block">
						<label class="ticket_form_label ticket_form_left" for="ticket_start_date"><?php esc_html_e( 'Start sale:', 'event-tickets' ); ?></label>
						<div class="ticket_form_right">
							<input autocomplete="off" type="text" class="ticket_field" size='10' name="ticket_start_date" id="ticket_start_date">
							<span class="ticket_start_time ticket_time">
								<?php echo tribe_get_datetime_separator(); ?>
								<select name="ticket_start_hour" id="ticket_start_hour" class="ticket_field tribe-dropdown">
									<?php echo $startHourOptions; ?>
								</select>
								<select name="ticket_start_minute" id="ticket_start_minute" class="ticket_field tribe-dropdown">
									<?php echo $startMinuteOptions; ?>
								</select>
								<?php if ( ! strstr( get_option( 'time_format', Tribe__Date_Utils::TIMEFORMAT ), 'H' ) ) : ?>
									<select name="ticket_start_meridian" id="ticket_start_meridian" class="ticket_field tribe-dropdown">
										<?php echo $startMeridianOptions; ?>
									</select>
								<?php endif; ?>
							</span>
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
							<input autocomplete="off" type="text" class="ticket_field" size='10' name="ticket_end_date" id="ticket_end_date">

							<span class="ticket_end_time ticket_time">
								<?php echo tribe_get_datetime_separator(); ?>
								<select name="ticket_end_hour" id="ticket_end_hour" class="ticket_field tribe-dropdown">
									<?php echo $endHourOptions; ?>
								</select>
								<select name="ticket_end_minute" id="ticket_end_minute" class="ticket_field tribe-dropdown">
									<?php echo $endMinuteOptions; ?>
								</select>
								<?php if ( ! strstr( get_option( 'time_format', Tribe__Date_Utils::TIMEFORMAT ), 'H' ) ) : ?>
									<select name="ticket_end_meridian" id="ticket_end_meridian" class="ticket_field tribe-dropdown">
										<?php echo $endMeridianOptions; ?>
									</select>
								<?php endif; ?>
							</span>
							<span class="tooltip_container">
								<span class="dashicons dashicons-editor-help"></span>
								<span class="tooltip">
									<?php esc_html_e( 'If you don\'t set a start/end date for sales, tickets will be available from now until the event ends.', 'event-tickets' ); ?>
								</span>
							</span>
						</div>


					</div>
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
					<input type="button" id="ticket_form_save" name="ticket_form_save" value="<?php esc_attr_e( 'Save ticket', 'event-tickets' ); ?>" class="button-primary" />
					<input type="button" id="rsvp_form_save" name="ticket_form_save" value="<?php esc_attr_e( 'Save RSVP', 'event-tickets' ); ?>" class="button-primary" />
					<input type="button" id="ticket_form_cancel" name="ticket_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" class="button-secondary" />

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
