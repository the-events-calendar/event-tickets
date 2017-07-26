<div id="tribe_panel_base" class="ticket_panel panel_base" aria-hidden="false">
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
			 * @param int Post ID
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
		 * @param int Post ID
		 * @since TBD
		 */
		do_action( 'tribe_events_tickets_pre_ticket_list', $post_id );

		$this->ticket_list_markup( $post_id, $tickets );

		/**
		 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
		 *
		 * @param int Post ID
		 * @since TBD
		 */
		do_action( 'tribe_events_tickets_post_ticket_list', $post_id ); ?>

	</div>
	<div>
		<?php
		/**
		 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
		 *
		 * @param int Post ID
		 * @since TBD
		 */
		do_action( 'tribe_events_tickets_new_ticket_buttons', $post_id );
		?>
		<button id="rsvp_form_toggle" class="button-secondary ticket_form_toggle"><span class="ticket_form_toggle_text" aria-label="<?php esc_attr_e( 'Add a new RSVP', 'event-tickets' ); ?>"><?php esc_html_e( 'New RSVP', 'event-tickets' ); ?></span></button>
		<button id="settings_form_toggle" class="button-secondary"><span class="settings_form_toggle_text"><?php esc_html_e( 'Settings', 'event-tickets' ); ?></span></button>
	</div>
</div><!-- #panel_base -->
