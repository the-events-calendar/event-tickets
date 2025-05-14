<?php
/**
 * Ticket editor panel template for classic editor.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object|null $ticket                           The ticket object.
 * @var Tribe__Tickets__Tickets            $provider                         The provider instance.
 * @var array<string,string>               $modules                          The available ticket modules.
 * @var array<string,string>               $start_date_errors                The ticket start date errors.
 * @var int                                $post_id                          The post ID.
 * @var int                                $timepicker_step                  The timepicker step.
 * @var int|null                           $rsvp_id                          The ticket post ID, or null if new.
 * @var int|null                           $rsvp_limit                       The limit for the RSVP.
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

$ticket_type = $ticket_type ?? 'rsvp';
?>

<div id="tec_event_tickets_rsvp_panel" class="tribe-dependent panel_edit tribe-validation" aria-hidden="true"
	 data-default-provider="<?php echo esc_attr( $default_module_class ); ?>"
	 data-current-provider="<?php echo esc_attr( $provider_class ); ?>"

	 class="tribe-dependent"
	 data-depends="#tec_tickets_rsvp_enable"
	 data-condition-is-checked
>
	<?php
	/**
	 * Allows for the insertion of additional elements into the main ticket edit panel.
	 *
	 * @since TBD
	 *
	 * @param int Post ID
	 * @param int Ticket ID
	 */
	do_action( 'tribe_events_rsvp_pre_edit', $post_id, $rsvp_id );
	?>

	<div id="ticket_form" class="ticket_form tribe_sectionheader tribe-validation">
		<div id="ticket_form_table" class="eventtable ticket_form">
			<section id="ticket_form_main" class="main"
					 data-datepicker_format="<?php echo esc_attr( Tribe__Date_Utils::get_datepicker_format_index() ); ?>">

				<?php
				/**
				 * Allows for the insertion of additional elements into the start of the main rsvp form.
				 *
				 * @since TBD
				 *
				 * @param int      $post_id     The post ID of the post the ticket is attached to.
				 * @param string   $ticket_type The type of ticket the form is being rendered for.
				 * @param int|null $rsvp_id     The post ID of the ticket that is being edited, `null` if the ticket is
				 *                              being added.
				 */
				do_action( 'tec_event_tickets_rsvp_form__start', $post_id, $ticket_type, $rsvp_id );
				?>

				<input
					type='hidden'
					id='ticket_type'
					name='ticket_type'
					value="<?php echo esc_attr( $ticket_type ); ?>"
				/>

				<input
					type='hidden'
					id='post_ID'
					name='post_ID'
					value="<?php echo absint( $post_id ); ?>"
				/>

				<?php $this->template( 'editor/panel/fields/limit', get_defined_vars() ); ?>

				<?php $this->template( 'editor/panel/fields/rsvp/dates', get_defined_vars() ); ?>

				<input type="hidden" id="tec_tickets_ticket_provider" name="ticket_provider" value="<?php echo esc_attr( $provider_class ); ?>"/>

				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form - main section
				 *
				 * @since TBD
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 * @param string Ticket Type Whether this is a request for a ticket or an RSVP or something else.
				 */
				do_action( 'tec_event_tickets_rsvp_metabox_edit_main', $post_id, $rsvp_id, $ticket_type );
				?>
			</section>
			<div
				class="tec-tickets-rsvp-form__options"
			>
				<h4
					id="rsvp_title_edit"
					class="ticket_form_title"
				>
					<?php
					echo esc_html_x( 'Options', 'admin edit rsvp option panel heading', 'event-tickets' );
					?>
				</h4>
				<?php
				$this->template( [ 'components', 'switch-field' ], [
						'id'      => 'tec_tickets_rsvp_enable_cannot_go',
						'name'    => 'tec_tickets_rsvp_enable_cannot_go',
						'label'   => 'Enable "Can\'t go" responses',
						'tooltip' => '',
						'value'   => '',
					] );

				/**
				 * Allows for the insertion of additional elements into the main ticket edit panel below the accordion
				 * section
				 *
				 * @since TBD
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tec_event_tickets_rsvp_post_options', $post_id, $rsvp_id );
				?>
			</div>
			<div class="ticket_bottom">
				<input
					type="hidden"
					name="rsvp_id"
					id="rsvp_id"
					class="ticket_field"
					value="<?php echo esc_attr( $rsvp_id ); ?>"
				/>
				<input
					type="button"
					id="tc_ticket_form_save"
					class="button-primary tribe-validation-submit"
					name="tc_ticket_form_save"
					value="<?php esc_attr_e( 'Save', 'event-tickets' ); ?>"
				/>
				<input
					type="button"
					id="tc_ticket_form_cancel"
					class="button-secondary"
					name="tc_ticket_form_cancel"
					value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>"
				/>

				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form bottom (buttons) section
				 *
				 * @since TBD
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tribe_events_tickets_bottom', $post_id, $rsvp_id );
				?>

				<div id="ticket_bottom_right">
					<?php
					/**
					 * Allows for the insertion of additional content into the ticket edit form bottom (links on right)
					 * section
					 *
					 * @since TBD
					 *
					 * @param int Post ID
					 * @param int Ticket ID
					 */
					do_action( 'tribe_events_tickets_bottom_right', $post_id, $rsvp_id );
					?>
				</div>
			</div>
		</div>
	</div>
</div>
