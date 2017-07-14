<?php
/**
 * @var WP_Post $post
 * @var bool $show_global_stock
 * @var Tribe__Tickets__Global_Stock $global_stock
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$post_id = get_the_ID();
$header_id = get_post_meta( $post_id, $this->image_header_field, true );
$header_id = ! empty( $header_id ) ? $header_id : '';
$header_img = '';
if ( ! empty( $header_id ) ) {
	$header_img = wp_get_attachment_image( $header_id, 'full' );
	$header_filename = basename ( get_attached_file( $header_id ) );
}

$modules = Tribe__Tickets__Tickets::modules();
$total_tickets = Tribe__Tickets__Tickets_Handler::instance()->get_total_event_capacity( $post_id );
$attendees_url = Tribe__Tickets__Tickets_Handler::instance()->get_attendee_report_link( get_post( $post_id ) );
?>

<div id="event_tickets" class="eventtable"  aria-live="polite">
	<?php
	wp_nonce_field( 'tribe-tickets-meta-box', 'tribe-tickets-post-settings' );

	// the main panel ?>
	<div id="tribe_panel_base" class="ticket_panel panel_base" aria-hidden="false">
		<div class="tribe_sectionheader ticket_list_container">
			<div class="ticket_table_intro">
				<span class="ticket_form_total_capacity">
					<?php esc_html_e( 'Total Event Capacity:', 'event-tickets' ); ?>
					<span id="ticket_form_total_capacity_value">
						<?php
						switch ( $total_tickets ) {
							case -1:
								?><i><?php esc_html_e( 'unlimited', 'event-tickets' ); ?></i><?php
								break;
							case 0:
								?><i><?php esc_html_e( 'No tickets created yet', 'event-tickets' ); ?></i><?php
								break;
							default:
								echo absint( $total_tickets );
								break;
						}
						?>
					</span>
				</span>
				<?php
				/**
				 * Allows for the insertion of additional elements into the main ticket admin panel "header"
				 *
				 * @param int $post_id the id of the post
				 * @since TBD
				 */
				do_action( 'tribe_events_tickets_post_capacity', $post_id );
				?>
				<a id="ticket_form_view_attendees" class="ticket_form_view_attendees" href="<?php echo esc_url( $attendees_url ); ?>"><?php esc_html_e( 'View Attendees', 'event-tickets' ); ?></a>
			</div>

			<?php
			/**
			 * Allows for the insertion of additional content into the main ticket admin panel before the tickets listing
			 *
			 * @param int $post_id the id of the post
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_pre_ticket_list', $post_id );

			$this->ticket_list_markup( $tickets );

			/**
			 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
			 *
			 * @param int $post_id the id of the post
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_post_ticket_list', $post_id ); ?>

		</div>
		<div>
			<?php
			/**
			 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
			 *
			 * @param int $post_id the id of the post
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_new_ticket_buttons', $post_id );
			?>
			<button id="rsvp_form_toggle" class="button-secondary ticket_form_toggle"><span class="ticket_form_toggle_text" aria-label="<?php esc_attr_e( 'Add a new RSVP' ); ?>"><?php esc_html_e( 'New RSVP', 'event-tickets' ); ?></span></button>
			<button id="settings_form_toggle" class="button-secondary"><span class="settings_form_toggle_text"><?php esc_html_e( 'Settings', 'event-tickets' ); ?></span></button>
		</div>
	</div><!-- #panel_base -->

	<?php // the add/edit panel ?>
	<div id="tribe_panel_edit" class="ticket_panel panel_edit" aria-hidden="true" >
		<?php
		/**
		 * Allows for the insertion of additional elements into the main ticket edit panel
		 *
		 * @param int $post_id the id of the post
		 * @since TBD
		 */
		do_action( 'tribe_events_tickets_pre_edit', $post_id );
		?>
		<?php if ( get_post_meta( $post_id, '_EventOrigin', true ) === 'community-events' ) {
			?>
			<?php // @TODO: this should get moved to Community Events? Use tribe_events_tickets_pre_edit ?>
			<div>
				<div class="tribe_sectionheader updated">
					<p class="error-message"><?php esc_html_e( 'This event was created using Community Events. Are you sure you want to sell tickets for it?', 'event-tickets' ); ?></p>
				</div>
			</div>
		<?php
		}
		?>

		<div id="ticket_form" class="ticket_form tribe_sectionheader">
			<div id="ticket_form_table" class="eventtable ticket_form">
				<?php // @TODO: Do these need to get renamed for RSVPs? ?>
				<h4 class="ticket_form_title_add"><?php esc_html_e( 'Add new ticket', 'event-tickets' ); ?></h4>
				<h4 class="ticket_form_title_edit"><?php esc_html_e( 'Edit ticket', 'event-tickets' ); ?></h4>
				<section id="ticket_form_main" class="main">
					<div class="input_block">
						<label class="ticket_form_left" for="ticket_name"><?php esc_html_e( 'Ticket Name:', 'event-tickets' ); ?></label>
						<input type='text' id='ticket_name' name='ticket_name' class="ticket_field" size='25' value='' />
					</div>
				</section>
				<div class="accordion">
					<button class="accordion-header" type="button">
						<?php esc_html_e( 'Advanced', 'event-tickets' ); ?>
					</button>
					<section id="ticket_form_advanced" class="advanced accordion-content">
						<h4 class="accordion-label"><?php esc_html_e( 'Advanced Settings', 'event-tickets' ); ?></h4>
						<div class="input_block">
							<label class="ticket_form_left" for="ticket_description"><?php esc_html_e( 'Ticket Description:', 'event-tickets' ); ?></label>
							<textarea rows="5" cols="40" name="ticket_description" class="ticket_field"
										id="ticket_description"></textarea>
							<div class="input_block">
								<label><input type="checkbox" name="tribe_show_description" value="1"> <?php esc_html_e( 'Show description on front end and emailed tickets.', 'event-ticket' ); ?> </label>
							</div>
						</div>
						<div class="input_block">
							<label class="ticket_form_left" for="ticket_start_date"><?php esc_html_e( 'Start sale:', 'event-tickets' ); ?></label>
							<input autocomplete="off" type="text" class="ticket_field" size='10' name="ticket_start_date" id="ticket_start_date" value="" >
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
						</div>
						<div class="input_block">
							<label class="ticket_form_left" for="ticket_end_date"><?php esc_html_e( 'End sale:', 'event-tickets' ); ?></label>
							<input autocomplete="off" type="text" class="ticket_field" size='10' name="ticket_end_date" id="ticket_end_date" value="">

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

							<p class="ticket_form_right">
								<?php esc_html_e( 'When will ticket sales occur?', 'event-tickets' ); ?>
								<?php
								// Why break in and out of PHP? because I want the space between the phrases without including them in the translations
								if ( class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === get_post_type( $post ) ) {
									esc_html_e( "If you don't set a start/end date for sales, tickets will be available from now until the event ends.", 'event-tickets' );
								}
								?>
							</p>
						</div>
						<?php
						/**
						 * Allows for the insertion of additional content into the ticket edit form - advanced section
						 *
						 * @param Post ID
						 * @param null Ticket ID (for backwards compatibility)
						 */
						do_action( 'tribe_events_tickets_metabox_advanced', $post_id, null ); ?>
					</section><!-- #ticket_form_advanced -->
					<?php
					/**
					 * Allows for the insertion of additional elements into the main ticket edit panel below the advances section
					 *
					 * @param int $post_id the id of the post
					 * @since TBD
					 */
					do_action( 'tribe_events_tickets_post_advanced', $post_id );
					?>
					<div class="ticket_bottom">
							<input type="hidden" name="ticket_id" id="ticket_id" class="ticket_field" value="" />
							<input type="button" id="ticket_form_save" name="ticket_form_save" value="<?php esc_attr_e( 'Save this ticket', 'event-tickets' ); ?>" class="button-primary" />
							<input type="button" id="ticket_form_cancel" name="ticket_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" class="button-secondary" />
					</div>
				</div> <!-- //.accordion -->

			</div><!-- #ticket_form_table -->
		</div><!-- #ticket_form -->
	</div><!-- #tribe_panel_edit -->

	<?php // the settings panel ?>
	<div id="tribe_panel_settings" class="ticket_panel panel_settings" aria-hidden="true" >
		<h4 class="ticket_title"><?php esc_html_e( 'Ticket Settings', 'event-tickets' ); ?></h4>

		<section class="settings_main">
			<?php
			/**
			 * Allows for the insertion of additional elements into the ticket settings admin panel below the ticket table
			 *
			 * @param int $post_id the id of the post
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_settings_content', $post_id );
			?>
		</section>
		<?php // the ticket image section ?>
		<section id="tribe-tickets-image">
			<div class="tribe-tickets-image-upload">
				<div class="input_block">
					<span class="ticket_form_left"><?php esc_html_e( 'Ticket header image:', 'event-tickets' ); ?></span>
					<p class="ticket_form_right"><?php esc_html_e( 'Select an image from your media library to display on emailed tickets. For best results, use a .jpg, .png, or .gif at least 1160px wide.', 'event-tickets' ); ?></p>

					<div class="ticket_form_right">
						<input type="button" class="button" name="tribe_ticket_header_image" id="tribe_ticket_header_image" value="<?php esc_html_e( 'Select an Image', 'event-tickets' ); ?>" />
						<span id="tribe_tickets_image_preview_filename" <?php if ( empty( $header_filename  ) ) { echo 'style="display: none"'; } ?>><span class="dashicons dashicons-format-image"></span><span class="filename"><?php echo esc_html( $header_filename ); ?></span></span>
					</div>

					<div class="ticket_form_right tribe-tickets-image-preview">
							<a class="tribe_preview" id="tribe_ticket_header_preview">
								<?php echo $header_img; ?>
							</a>
							<a href="#" id="tribe_ticket_header_remove"><?php esc_html_e( 'Remove', 'event-tickets' ); ?></a>

							<input type="hidden" id="tribe_ticket_header_image_id" class="settings_field" name="tribe_ticket_header_image_id" value="<?php echo esc_attr( $header_id ); ?>" />
					</div>
				</div>
			</div>

		</section>

		<input type="button" id="tribe_settings_form_cancel" name="tribe_settings_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" class="button-secondary" />
		<input type="button" id="tribe_settings_form_save" name="tribe_settings_form_save" value="<?php esc_attr_e( 'Save settings', 'event-tickets' ); ?>" class="button-primary" />
	</div><!-- #tribe_panel_settings -->
</div>
