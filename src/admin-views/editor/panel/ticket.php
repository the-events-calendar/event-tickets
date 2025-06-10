<?php
/**
 * Ticket editor panel template for classic editor.
 *
 * @since 5.8.0 Input fields moved to separate templates.
 *
 * @version 5.8.0
 *
 * @var Tribe__Tickets__Ticket_Object|null $ticket                           The ticket object.
 * @var Tribe__Tickets__Tickets            $provider                         The provider instance.
 * @var array<string,string>               $modules                          The available ticket modules.
 * @var array<string,string>               $start_date_errors                The ticket start date errors.
 * @var int                                $post_id                          The post ID.
 * @var int                                $timepicker_step                  The timepicker step.
 * @var int|null                           $ticket_id                        The ticket post ID, or null if new.
 * @var string                             $default_module_class             The default module class.
 * @var string                             $ticket_end_date                  The ticket end date.
 * @var string                             $provider_class                   The provider class.
 * @var string                             $rsvp_form_save_text              The RSVP form save text.
 * @var string                             $rsvp_required_type_error_message Error message for required RSVP type.
 * @var string                             $ticket_start_date                The ticket start date.
 * @var string                             $ticket_description               The ticket description.
 * @var string                             $ticket_end_date_aria_label       The ticket end date ARIA attribute.
 * @var string                             $ticket_end_date_help_text        The ticket end date help text.
 * @var string                             $ticket_end_time                  The ticket end time.
 * @var string                             $ticket_form_save_text            The ticket form save text.
 * @var string                             $ticket_name                      The ticket name.
 * @var string                             $ticket_start_date_aria_label     The ticket start date ARIA attribute.
 * @var string                             $ticket_start_time                The ticket start time.
 * @var string                             $timepicker_round                 The timepicker round.
 * @var string                             $ticket_type                      The type of Ticket the form is for.
 * @var Tribe__Tickets__Admin__Views       $this                             The admin views instance.
 */

$ticket_type = $ticket_type ?? 'default';
?>

<div id="tribe_panel_edit" class="ticket_panel panel_edit tribe-validation" aria-hidden="true"
	 data-default-provider="<?php echo esc_attr( $default_module_class ); ?>"
     data-current-provider="<?php echo esc_attr( $provider_class ); ?>"
>
	<?php
	/**
	 * Allows for the insertion of additional elements into the main ticket edit panel.
	 *
	 * @since 4.6
	 *
	 * @param int Post ID
	 * @param int Ticket ID
	 */
	do_action( 'tribe_events_tickets_pre_edit', $post_id, $ticket_id, $ticket_type );
	?>

	<div id="ticket_form" class="ticket_form tribe_sectionheader tribe-validation">
		<div id="ticket_form_table" class="eventtable ticket_form">
			<div
					class="tribe-dependent"
					data-depends="#tec_tickets_ticket_provider"
					data-condition-not="Tribe__Tickets__RSVP"
			>
				<h4
						id="ticket_title_add"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-empty
				>
					<?php
					echo esc_html(
							sprintf(
							// Translators: %s: dynamic 'ticket' text.
									_x(
											'Add new %s',
											'admin add new ticket panel heading',
											'event-tickets'
									),
									tribe_get_ticket_label_singular_lowercase( 'admin_add_new_ticket_panel_heading' )
							)
					);
					?>
				</h4>
				<h4
						id="ticket_title_edit"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-not-empty
				>
					<?php
					echo esc_html(
							sprintf(
							// Translators: %s: dynamic 'ticket' text.
									_x(
											'Edit %s',
											'admin edit ticket panel heading',
											'event-tickets'
									),
									tribe_get_ticket_label_singular_lowercase( 'admin_edit_ticket_panel_heading' )
							)
					); ?>
				</h4>
			</div>
			<div
					class="tribe-dependent"
					data-depends="#tec_tickets_ticket_provider"
					data-condition="Tribe__Tickets__RSVP"
			>
				<h4
						id="rsvp_title_add"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-empty
				>
					<?php
					echo esc_html(
							sprintf(
							// Translators: %s: dynamic 'RSVP' text.
									_x(
											'Add new %s',
											'admin add new ticket panel heading',
											'event-tickets'
									),
									tribe_get_rsvp_label_singular( 'admin_add_new_ticket_panel_heading' )
							)
					);
					?>
				</h4>
				<h4
						id="rsvp_title_edit"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-not-empty
				>
					<?php
					echo esc_html(
							sprintf(
							// Translators: %s: dynamic 'RSVP' text.
									_x(
											'Edit %s',
											'admin edit ticket panel heading',
											'event-tickets'
									),
									tribe_get_rsvp_label_singular( 'admin_edit_ticket_panel_heading' )
							)
					);
					?>
				</h4>
			</div>
			<section id="ticket_form_main" class="main"
					 data-datepicker_format="<?php echo esc_attr( Tribe__Date_Utils::get_datepicker_format_index() ); ?>">

				<?php
				/**
				 * Allows for the insertion of additional elements into the start of the main ticket form.
				 *
				 * @since 5.8.0
				 *
				 * @param int      $post_id     The post ID of the post the ticket is attached to.
				 * @param string   $ticket_type The type of ticket the form is being rendered for.
				 * @param int|null $ticket_id   The post ID of the ticket that is being edited, `null` if the ticket is
				 *                              being added.
				 */
				do_action( 'tec_tickets_ticket_form_main_start', $post_id, $ticket_type, $ticket_id );

				/**
				 * Allows for the insertion of additional elements into the start of the main ticket form for a specific
				 * ticket type.
				 *
				 * @since 5.8.0
				 *
				 * @param int      $post_id     The post ID of the post the ticket is attached to.
				 * @param int|null $ticket_id   The post ID of the ticket that is being edited, `null` if the ticket is
				 *                              being added.
				 */
				do_action( "tec_tickets_ticket_form_main_start_{$ticket_type}", $post_id, $ticket_id );
				?>

				<input
					type='hidden'
					id='ticket_type'
					name='ticket_type'
					value="<?php echo esc_attr( $ticket_type ?? 'default' ); ?>"
				/>

				<?php $this->template( 'editor/panel/fields/name', get_defined_vars() ); ?>

				<?php $this->template( 'editor/panel/fields/description', get_defined_vars() ); ?>

				<?php $this->template( 'editor/panel/fields/dates', get_defined_vars() ); ?>

				<input type="hidden" id="tec_tickets_ticket_provider" name="ticket_provider" value="<?php echo esc_attr( $provider_class ); ?>" />

				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form - main section
				 *
				 * @since 4.6
				 * @since 5.18.0 Added the $ticket_type parameter.
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 * @param string Ticket Type Whether this is a request for a ticket or an RSVP or something else.
				 */
				do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, $ticket_id, $ticket_type );
				?>
			</section>
			<div class="accordion">
				<?php
				/** @var Tribe__Tickets__Admin__Views $admin_views */
				$admin_views = tribe( 'tickets.admin.views' );

				$admin_context = [
						'post_id'   => $post_id,
						'ticket_id' => $ticket_id,
						'provider'  => $provider,
				];

				$admin_views->template( 'editor/fieldset/advanced', $admin_context );
				$admin_views->template( 'editor/fieldset/history', [
						'post_id'   => $post_id,
						'ticket_id' => $ticket_id
				] );

				/**
				 * Allows for the insertion of additional content sections into the ticket edit form accordion
				 *
				 * @since 4.6
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_edit_accordion_content', $post_id, $ticket_id );
				?>
			</div>

			<?php
			/**
			 * Allows for the insertion of additional elements into the main ticket edit panel below the accordion
			 * section
			 *
			 * @since 4.6
			 *
			 * @param int Post ID
			 * @param int Ticket ID
			 */
			do_action( 'tribe_events_tickets_post_accordion', $post_id, $ticket_id );
			?>
			<div class="ticket_bottom">

				<?php
				/**
				 * Allows for the insertion of additional content into the beginning of the ticket edit form bottom (buttons) section
				 *
				 * @since 5.24.1
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tribe_events_tickets_bottom_start', $post_id, $ticket_id );
				?>
				<div class="ticket_bottom_buttons">
					<input
							type="hidden"
							name="ticket_id"
							id="ticket_id"
							class="ticket_field"
							value="<?php echo esc_attr( $ticket_id ); ?>"
					/>
					<input
							type="button"
							id="ticket_form_save"
							class="button-primary tribe-dependent tribe-validation-submit"
							name="ticket_form_save"
							value="<?php echo esc_attr( $ticket_form_save_text ); ?>"
							data-depends="#tec_tickets_ticket_provider"
							data-condition-not="Tribe__Tickets__RSVP"
					/>
					<input
							type="button"
							id="rsvp_form_save"
							class="button-primary tribe-dependent tribe-validation-submit"
							name="ticket_form_save"
							value="<?php echo esc_attr( $rsvp_form_save_text ); ?>"
							data-depends="#tec_tickets_ticket_provider"
							data-condition="Tribe__Tickets__RSVP"
					/>
					<input
							type="button"
							id="ticket_form_cancel"
							class="button-secondary"
							name="ticket_form_cancel"
							value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>"
					/>
				</div>
				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form bottom (buttons) section
				 *
				 * @since 4.6
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tribe_events_tickets_bottom', $post_id, $ticket_id );
				?>

				<div id="ticket_bottom_right">
					<?php
					/**
					 * Allows for the insertion of additional content into the ticket edit form bottom (links on right)
					 * section
					 *
					 * @since 4.6
					 *
					 * @param int Post ID
					 * @param int Ticket ID
					 */
					do_action( 'tribe_events_tickets_bottom_right', $post_id, $ticket_id );
					?>
				</div>
			</div>
		</div>
	</div>
</div>
