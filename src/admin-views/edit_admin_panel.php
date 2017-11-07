<?php
$timepicker_step = 30;
if ( class_exists( 'Tribe__Events__Main' ) ) {
	$timepicker_step = (int) tribe( 'tec.admin.event-meta-box' )->get_timepicker_step( 'start' );
}

$timepicker_round = '00:00:00';

$start_date_errors = array(
	'is-required' => __( 'Start sale date cannot be empty.', 'event-tickets' ),
	'is-greater-or-equal-to' => __( 'Start sale date cannot be greater than End Sale date', 'event-tickets' ),
);
?>

<div id="tribe_panel_edit" class="ticket_panel panel_edit tribe-validation" aria-hidden="true">
	<?php
	/**
	 * Allows for the insertion of additional elements into the main ticket edit panel
	 *
	 * @since 4.6
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
						data-validation-error="<?php esc_attr_e( 'Ticket Type is a required field.', 'event-tickets' ); ?>"
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
							 * @since 4.6
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
				 * @since 4.6
				 *
				 * @param int Post ID
				 * @param null Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, null ); ?>
			</section>

			<div class="accordion">
				<?php require_once( 'tickets-advanced.php' ); ?>
				<?php require_once( 'tickets-history.php' ); ?>
				<?php
				/**
				 * Allows for the insertion of additional content sections into the ticket edit form accordion
				 *
				 * @since 4.6
				 *
				 * @param int Post ID
				 * @param null Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_edit_accordion_content', $post_id, null );
				?>
			</div>

			<?php
			/**
			 * Allows for the insertion of additional elements into the main ticket edit panel below the accordion section
			 *
			 * @since 4.6
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
				 * @since 4.6
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
					 * @since 4.6
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
