<table class="eventtable ticket_list eventForm">
	<?php
	$provider = null;
	$count    = 0;
	global $post;

	$post_type = 'post';

	if ( $post ) {
		$post_id = get_the_ID();
		$post_type = $post->post_type;
	} else {
		$post_id = $_POST['post_ID'];


		if ( ! empty( $_POST['post_type'] ) ) {
			$post_type = $_POST['post_type'];
		} elseif ( ! empty( $_GET['post_type'] ) ) {
			$post_type = $_GET['post_type'];
		}
	}

	$modules = Tribe__Tickets__Tickets::modules();

	foreach ( $tickets as $ticket ) {
		/**
		 * @var Tribe__Tickets__Ticket_Object $ticket
		 */
		$controls     = array();
		$provider     = $ticket->provider_class;
		$provider_obj = call_user_func( array( $provider, 'get_instance' ) );

		$controls[] = sprintf( '<span><a href="#" attr-provider="%1$s" attr-ticket-id="%2$s" id="ticket_edit_%2$s" class="ticket_edit">' . esc_html__( 'Edit', 'event-tickets' ) . '</a></span>', $ticket->provider_class, $ticket->ID );
		$controls[] = sprintf( '<span><a href="#" attr-provider="%1$s" attr-ticket-id="%2$s" id="ticket_delete_%2$s" class="ticket_delete">' . esc_html__( 'Delete', 'event-tickets' ) . '</a></span>', $ticket->provider_class, $ticket->ID );

		if ( $ticket->frontend_link && get_post_status( $post_id ) == 'publish' ) {
			$controls[] = sprintf( "<span><a href='%s'>" . esc_html__( 'View', 'event-tickets' ) . '</a></span>', esc_url( $ticket->frontend_link ) );
		}

		if ( is_admin() ) {
			if ( $ticket->admin_link ) {
				$controls[] = sprintf( "<span><a href='%s'>" . esc_html__( 'Edit in %s', 'event-tickets' ) . '</a></span>', esc_url( $ticket->admin_link ), $modules[ $ticket->provider_class ] );
			}

			$report = $provider_obj->get_ticket_reports_link( $post_id, $ticket->ID );
			if ( $report ) {
				$controls[] = $report;
			}
		}

		if ( ( $ticket->provider_class !== $provider ) || $count == 0 ) :
			?>
			<td colspan="4" class="titlewrap">
				<h4 class="tribe_sectionheader">
					<?php
					echo esc_html( apply_filters( 'tribe_events_tickets_module_name', $modules[ $ticket->provider_class ], $ticket->provider_class ) );
					echo $provider_obj->get_event_reports_link( $post_id );
					?>
					<small>&nbsp;|&nbsp;</small>
					<?php
					$attendees_url = add_query_arg(
						array(
							'post_type' => $post_type,
							'page' => Tribe__Tickets__Tickets_Handler::$attendees_slug,
							'event_id' => $post_id,
						),
						admin_url( 'edit.php' )
					);

					echo sprintf(
						"<small><a title='" . esc_attr__( 'See who purchased tickets to this event', 'event-tickets' ) . "' href='%s'>%s</a></small>",
						esc_url( apply_filters( 'tribe_events_tickets_attendees_url', $attendees_url, $post_id ) ),
						esc_html__( 'Attendees', 'event-tickets' )
					);
					?>
				</h4>
			</td>
		<?php endif; ?>
		<tr>
			<td>
				<p class="ticket_name">
					<?php
					printf(
						"<a href='#' attr-provider='%s' attr-ticket-id='%s' class='ticket_edit'>%s</a>",
						esc_attr( $ticket->provider_class ),
						esc_attr( $ticket->ID ),
						esc_html( $ticket->name )
					);
					do_action( 'event_tickets_ticket_list_after_ticket_name', $ticket );
					?>
				</p>

				<div class="ticket_controls">
					<?php echo join( ' | ', $controls ); ?>
				</div>

			</td>

			<td valign="top">
				<?php echo $provider_obj->get_price_html( $ticket->ID ); ?>
			</td>

			<td nowrap="nowrap">
				<?php echo tribe_tickets_get_ticket_stock_message( $ticket ); ?>
			</td>
			<td width="40%" valign="top">
				<?php echo esc_html( $ticket->description ); ?>
			</td>
		</tr>
		<?php
		$count ++;
	} ?>
</table>
