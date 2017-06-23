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
}

$modules = Tribe__Tickets__Tickets::modules();
$total_tickets = Tribe__Tickets__Tickets_Handler::instance()->get_total_event_capacity( $post_id );
$attendees_url = Tribe__Tickets__Tickets_Handler::instance()->get_attendee_report_link( get_post( $post_id ) );
?>

<div id="event_tickets" class="eventtable">
	<?php
	wp_nonce_field( 'tribe-tickets-meta-box', 'tribe-tickets-post-settings' );

	// the main panel ?>
	<div id="panel_base" class="ticket_panel panel_base">
		<div class="tribe_sectionheader ticket_list_container">
			<div class="ticket_table_intro">
				<span class="ticket_form_total_capacity">
					Total Event Capacity:
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
				 * @param Post ID
				 * @since TBD
				 */
				do_action( 'tribe_events_tickets_post_capcity', $post_id );
				// Move view orders to ET+, use "tribe_events_tickets_post_capcity" action
				?>
				<a id="ticket_form_view_orders" href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>" class="ticket_form_view_orders"><?php esc_html_e( 'View Orders', 'event-tickets' ); ?></a>
				<?php


				?>
				<a id="ticket_form_view_attendees" class="ticket_form_view_attendees" href="<?php echo esc_url( $attendees_url ); ?>"><?php esc_html_e( 'View Attendees', 'event-tickets' ); ?></a>
			</div>

			<?php
			/**
			 * Allows for the insertion of additional content into the main ticket admin panel before the tickets listing
			 *
			 * @param Post ID
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_pre_ticket_list', $post_id );

			$this->ticket_list_markup( $tickets );

			/**
			 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
			 *
			 * @param Post ID
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_post_ticket_list', $post_id ); ?>

		</div>
		<button id="ticket_form_toggle" class="button-secondary ticket_form_toggle"><span class="ticket_form_toggle_text" aria-label="<?php esc_attr_e( 'Add a new ticket' ); ?>"><?php esc_html_e( 'New ticket', 'event-tickets' ); ?></span></button>
		<button id="rsvp_form_toggle" class="button-secondary ticket_form_toggle"><span class="ticket_form_toggle_text" aria-label="<?php esc_attr_e( 'Add a new RSVP' ); ?>"><?php esc_html_e( 'New RSVP', 'event-tickets' ); ?></span></button>
		<button id="settings_form_toggle" class="button-secondary"><span class="settings_form_toggle_text"><?php esc_html_e( 'Settings', 'event-tickets' ); ?></span></button>
	</div>

	<?php // the add/edit panel ?>
	<div id="panel_edit" class="ticket_panel panel_edit">
		<?php if ( get_post_meta( $post_id, '_EventOrigin', true ) === 'community-events' ) {
			?>
			<div>
				<div class="tribe_sectionheader updated">
					<p class="error-message"><?php esc_html_e( 'This event was created using Community Events. Are you sure you want to sell tickets for it?', 'event-tickets' ); ?></p>
				</div>
			</div>
		<?php
		}
		?>

		<div id="ticket_form" class="ticket_form tribe_sectionheader">
			<div id="tribe-loading"><span></span></div>
			<div id="ticket_form_table" class="eventtable ticket_form">
				<h4 class="ticket_form_title_add"><?php esc_html_e( 'Add new ticket', 'event-tickets' ); ?></h4>
				<h4 class="ticket_form_title_edit"><?php esc_html_e( 'Edit ticket', 'event-tickets' ); ?></h4>
				<fieldset class="ticket">
					<legend><?php esc_html_e( 'Sell using:', 'event-tickets' ); ?></elgend>
					<?php
					$checked = true;
					foreach ( $modules as $class => $module ) {
						?>
						<input <?php checked( $checked ); ?> type="radio" name="ticket_provider"
															id="<?php echo esc_attr( $class . '_radio' ); ?>"
															value="<?php echo esc_attr( $class ); ?>"
															class="ticket_field ticket_provider">
						<span><?php echo esc_html( apply_filters( 'tribe_events_tickets_module_name', $module ) ); ?></span>
						<?php
						$checked = false;
					}
					?>
				</fieldset>
				<div class="ticket">
					<label for="ticket_name"><?php esc_html_e( 'Ticket Name:', 'event-tickets' ); ?></label>
					<input type='text' id='ticket_name' name='ticket_name' class="ticket_field" size='25' value='' />
				</div>
				<div class="ticket">
					<label for="ticket_description"><?php esc_html_e( 'Ticket Description:', 'event-tickets' ); ?></label>
					<textarea rows="5" cols="40" name="ticket_description" class="ticket_field"
								id="ticket_description"></textarea>
				</div>
				<div class="ticket">
					<label for="ticket_start_date"><?php esc_html_e( 'Start sale:', 'event-tickets' ); ?></label>
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

				<div class="ticket">
					<label for="ticket_end_date"><?php esc_html_e( 'End sale:', 'event-tickets' ); ?></label>
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

					<p class="description">
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
				 * Allows for the insertion of additional content into the ticket admin form
				 *
				 * @var Post ID
				 * @var null Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_advanced', $post_id, null ); ?>

				<div class="ticket bottom">
						<input type="hidden" name="ticket_id" id="ticket_id" class="ticket_field" value="" />
						<input type="button" id="ticket_form_save" name="ticket_form_save" value="<?php esc_attr_e( 'Save this ticket', 'event-tickets' ); ?>" class="button-primary" />
						<input type="button" id="ticket_form_cancel" name="ticket_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" class="button-secondary" />
				</div>
			</div>
		</div>
	</div>

	<?php // the settings panel ?>
	<div id="panel_settings" class="ticket_panel panel_settings">

		<?php if ( $show_global_stock ) : ?>
			<div id="tribe-global-stock-settings" class="event-wide-settings">
				<div class="eventtable ticket_list eventForm">
					<div>
						<label for="tribe-tickets-enable-global-stock">
							<?php esc_html_e( 'Enable global stock', 'event-tickets' ); ?>
						</label>
						<input type="checkbox" name="tribe-tickets-enable-global-stock" id="tribe-tickets-enable-global-stock" value="1" <?php checked( $global_stock->is_enabled() ); ?> />
					</div>
					<div id="tribe-tickets-global-stock-level">
							<label for="tribe-tickets-global-stock">
								<?php esc_html_e( 'Global stock level', 'event-tickets' ); ?>
							</label>
							<input type="number" name="tribe-tickets-global-stock" id="tribe-tickets-global-stock" value="<?php echo esc_attr( $global_stock->get_stock_level() ); ?>" />
							<span class="tribe-tickets-global-sales">
								<?php echo esc_html( sprintf( _n( '(%s sold)', '(%s sold)', $global_stock->tickets_sold(), 'event-tickets' ), $global_stock->tickets_sold() ) ); ?>
							</span>
					</div>
				</div>
			</div>
		<?php endif; ?>


		<div class="eventtable ticket_list eventForm">
			<div class="tribe-tickets-image-upload">
				<?php esc_html_e( 'Upload image for the ticket header.', 'event-tickets' ); ?>
				<p class="description"><?php esc_html_e( 'The maximum image size in the email will be 580px wide by any height, and then scaled for mobile. If you would like "retina" support use an image sized to 1160px wide.', 'event-tickets' ); ?></p>
				<input type="button" class="button" name="tribe_ticket_header_image" id="tribe_ticket_header_image" value="<?php esc_html_e( 'Select an Image', 'event-tickets' ); ?>" />
			</div>
			<div class="tribe-tickets-image-preview">
				<div>
					<div class="tribe_preview" id="tribe_ticket_header_preview">
						<?php echo $header_img; ?>
					</div>
					<p class="description"><a href="#" id="tribe_ticket_header_remove"><?php esc_html_e( 'Remove', 'event-tickets' ); ?></a></p>

					<input type="hidden" id="tribe_ticket_header_image_id" name="tribe_ticket_header_image_id" value="<?php echo esc_attr( $header_id ); ?>" />
				</div>
			</div>
		</div>

		<input type="button" id="settings_form_cancel" name="settings_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" class="button-secondary" />
		<input type="button" id="settings_form_save" name="settings_form_save" value="<?php esc_attr_e( 'Save settings', 'event-tickets' ); ?>" class="button-primary" />
	</div>
</div>
