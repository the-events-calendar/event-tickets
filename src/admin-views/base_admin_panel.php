<?php
$total_tickets = Tribe__Tickets__Tickets_Handler::instance()->get_total_event_capacity( $post_id );
$container_class = 'tribe_sectionheader ticket_list_container';
$container_class .= ( empty( $total_tickets ) ) ? ' tribe_no_capacity' : '' ;
?>
<div id="tribe_panel_base" class="ticket_panel panel_base" aria-hidden="false">
	<div class="<?php echo esc_attr( $container_class ); ?>">
		<?php
		// only show if there are tickets
		if ( ! empty( $total_tickets ) ) :
			?>
			<div class="ticket_table_intro">
				<?php
				/**
				 * Allows for the insertion of total capacity element into the main ticket admin panel "header"
				 *
				 * @since TBD
				 *
				 * @param int $post_id the id of the post
				 */
				do_action( 'tribe_events_tickets_capacity', $post_id );

				/**
				 * Allows for the insertion of additional elements (buttons/links) into the main ticket admin panel "header"
				 *
				 * @since TBD
				 *
				 * @param int $post_id the id of the post
				 */
				do_action( 'tribe_events_tickets_post_capacity', $post_id );
				?>
				<a id="ticket_form_view_attendees" class="ticket_form_view_attendees" href="<?php echo esc_url( $attendees_url ); ?>"><?php esc_html_e( 'View Attendees', 'event-tickets' ); ?></a>
			</div>
		<?php endif;

		/**
		 * Allows for the insertion of additional content into the main ticket admin panel before the tickets listing
		 *
		 * @param int $post_id the id of the post
		 * @since TBD
		 */
		do_action( 'tribe_events_tickets_pre_ticket_list', $post_id );

		$this->ticket_list_markup( $post_id, $tickets );

		/**
		 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
		 *
		 * @param int $post_id the id of the post
		 * @since TBD
		 */
		do_action( 'tribe_events_tickets_post_ticket_list', $post_id );
		?>
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
		<button id="rsvp_form_toggle" class="button-secondary ticket_form_toggle"><span class="ticket_form_toggle_text" aria-label="<?php esc_attr_e( 'Add a new RSVP', 'event-tickets' ); ?>"><?php esc_html_e( 'New RSVP', 'event-tickets' ); ?></span></button>
		<button id="settings_form_toggle" class="button-secondary"><span class="settings_form_toggle_text"><?php esc_html_e( 'Settings', 'event-tickets' ); ?></span></button>
	</div>
</div><!-- #panel_base -->
