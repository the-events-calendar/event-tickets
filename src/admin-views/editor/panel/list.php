<?php

use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * @var Tribe__Tickets__Attendees $tickets_attendees The Attendees class instance.
 * @var Ticket_Object[]           $tickets           The tickets for the event, any type.
 * @var int                       $total_tickets     The total number of tickets available for the event, any type.
 */
$tickets_attendees = tribe( 'tickets.attendees' );

$attendees_url = $tickets_attendees->get_report_link( get_post( $post_id ) );

$total_tickets = (int) tribe_get_event_capacity( $post_id );

$container_class = 'tribe_sectionheader ticket_list_container';
$container_class .= ( empty( $total_tickets ) ) ? ' tribe_no_capacity' : '';
?>
<div
	id="tribe_panel_base"
	class="ticket_panel panel_base"
	aria-hidden="false"
	data-save-prompt="<?php echo esc_attr( __( 'You have unsaved changes to your tickets. Discard those changes?', 'event-tickets' ) ); ?>"
>
	<div class="<?php echo esc_attr( $container_class ); ?>">
			<div class="ticket_table_intro">
				<?php
				/**
				 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing.
				 *
				 * @since 4.6
				 * @since TBD All metabox buttons to toggle ticket forms are now loaded from this action; moved to
				 *        list intro from after list. Pass `$total_tickets` and `$tickets` to the action.
				 *
				 * @param int $post_id The ID Of the post the ticket lists are being displayed for.
				 * @param int $total_tickets The total number of tickets available for the event, any type.
				 * @param array<Ticket_Object> $tickets The tickets for the event, any type.
				 */
				do_action( 'tribe_events_tickets_new_ticket_buttons', $post_id, $total_tickets, $tickets );
				?>

				<?php if ( empty( $tickets ) ) : ?>
					<button id="settings_form_toggle" class="button-secondary tribe-button-icon tribe-button-icon-settings">
						<?php esc_html_e( 'Settings', 'event-tickets' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<div class="ticket_table_intro__warnings">
				<?php
				/**
				 * Allows for the insertion of warnings before the settings button.
				 *
				 * @since 4.6
				 * @since TBD Moved to list intro from after list. Pass `$total_tickets` and `$tickets` parameters.
				 *
				 * @param int $post_id The ID Of the post the ticket lists are being displayed for.
				 * @param int $total_tickets The total number of tickets available for the event, any type.
				 * @param array<Ticket_Object> $tickets The tickets for the event, any type.
				 */
				do_action( 'tribe_events_tickets_new_ticket_warnings', $post_id, $total_tickets, $tickets );
				?>
			</div>

		<?php if ( ! empty( $tickets ) ) {
			/** @var Tribe__Tickets__Admin__Views $admin_views */
			$admin_views = tribe( 'tickets.admin.views' );

			// Split tickets by type to render a list for each type of ticket.
			$ticket_types = [];
			foreach ( $tickets as $ticket ) {
				$provider_class = $ticket->provider_class ?? null;

				if ( $provider_class === 'Tribe__Tickets__RSVP' ) {
					// RSVP is its own type.
					$ticket_types['rsvp'][] = $ticket;
					continue;
				}

				$ticket_type                    = $ticket->type ?? 'default';
				$ticket_types[ $ticket_type ][] = $ticket;
			}

			foreach ( $ticket_types as $ticket_type => $tickets ) {
				$table_data = [ 'ticket_type' => $ticket_type, 'tickets' => $tickets ];

				switch ( $ticket_type ) {
					case 'rsvp':
						$table_data ['table_title'] = tribe_get_rsvp_label_plural( 'list-table' );
						break;
					case 'default':
					default:
						$table_data['table_title'] = tribe_get_ticket_label_plural( 'list-table' );
						break;
				}

				/**
				 * Filters the data that will be passed to the tickets list table template.
				 *
				 * Emptying the 'tickets' key will prevent the table from being rendered.
				 *
				 * @since TBD
				 *
				 * @param array<string,mixed> $table_data  The list table data.
				 * @param string              $ticket_type The ticket type.
				 */
				$table_data = apply_filters( "tec_tickets_editor_list_table_data", $table_data, $ticket_type );

				/**
				 * Filters the data that will be passed to the tickets list table template for a specific ticket type.
				 *
				 * Emptying the 'tickets' key will prevent the table from being rendered.
				 *
				 * @since TBD
				 *
				 * @param array<string,mixed> $table_data The list table data.
				 */
				$table_data = apply_filters( "tec_tickets_editor_list_table_data_{$ticket_type}", $table_data );

				// If a filtering function emptied the tickets, do not render the table at all.
				if ( ! ( isset( $table_data['tickets'] ) && count( $table_data['tickets'] ) ) ) {
					continue;
				}

				$admin_views->template( 'editor/list-table', $table_data );
			}
		}
		?>
	</div>
	<div class="tribe-ticket-control-wrap">
		<div class="tribe-ticket-control-wrap__ctas">
			<?php if ( ! empty( $tickets ) ) : ?>
				<a
					href="<?php echo esc_url( $attendees_url ); ?>"
				>
					<?php esc_html_e( 'View Attendees', 'event-tickets' ); ?>
				</a>

				<?php
				/**
				 * Allows for the insertion of additional elements (buttons/links) into the main ticket admin panel "header".
				 *
				 * @since 4.6
				 *
				 * @param int $post_id Post ID.
				 */
				do_action( 'tribe_events_tickets_post_capacity', $post_id );
				?>
			<?php endif; ?>
		</div>

		<div class="tribe-ticket-control-wrap__settings">
			<?php if ( ! empty( $tickets ) ) : ?>
				<?php
				/**
				 * Allows for the insertion of total capacity element into the main ticket admin panel.
				 *
				 * @since 4.6
				 *
				 * @param int $post_id Post ID.
				 */
				do_action( 'tribe_events_tickets_capacity', $post_id );
				?>
			<?php endif; ?>

			<?php if ( ! empty( $tickets ) ) : ?>
				<button id="settings_form_toggle" class="button-secondary tribe-button-icon tribe-button-icon-settings">
					<?php esc_html_e( 'Settings', 'event-tickets' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php
	/**
	 * Allows for the insertion of content at the end of the new ticket admin panel.
	 *
	 * @since 4.6
	 *
	 * @param int Post ID.
	 */
	do_action( 'tribe_events_tickets_after_new_ticket_panel', $post_id );
	?>

</div>