<table class="eventtable ticket_list eventForm wp-list-table widefat fixed">
	<?php
	global $post;
	$provider = null;
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

	/**
	 * Render the ticket row into the ticket table
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @since TBD
	 */
	function render_ticket_row( $ticket ) {
		$provider     = $ticket->provider_class;
		$provider_obj = call_user_func( array( $provider, 'get_instance' ) );
		?>
		<tr class="<?php echo esc_attr( $provider ); ?>" data-ticket-order-id="order_<?php echo esc_attr( $ticket->ID ); ?>" data-ticket-type-id="<?php echo esc_attr( $ticket->ID ); ?>">
			<!-- (handle, name), price, capacity, available, editlink -->
			<td class=" column-primary ticket_name <?php echo esc_attr( $provider ); ?>">
				<span class="ticket_cell_label"><?php esc_html_e( 'Ticket Type:', 'event-tickets' ); ?></span>
				<p><?php echo esc_html( $ticket->name ); ?></p>
				<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'event-tickets' ); ?></span></button>
			</td>

			<?php
			/**
			 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
			 *
			 * @param Post ID
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_ticket_table_add_tbody_column', $ticket, $provider_obj );
			?>

			<td class="ticket_capacity">
				<span class="ticket_cell_label"><?php esc_html_e( 'Capacity:', 'event-tickets' ); ?></span>
				<?php
				// escaping handled in function
				echo $ticket->display_original_stock();
				?>
			</td>

			<td class="ticket_available">
				<span class="ticket_cell_label"><?php esc_html_e( 'Available:', 'event-tickets' ); ?></span>
				<?php
				if (  'unlimited' === $ticket->global_stock_mode()  ) {
					esc_html_e( 'unlimited', 'event-tickets' );
				} elseif ( 'own' === $ticket->global_stock_mode() ) {
					echo absint( $ticket->remaining() );
				} else {
					echo '(' . absint( $ticket->remaining() ) . ')';
				}
				?>
			</td>

			<td class="ticket_edit">
				<?php
				printf(
					"<button data-provider='%s' data-ticket-id='%s' class='ticket_edit_button'><span class='ticket_edit_text'>%s</span></a>",
					esc_attr( $ticket->provider_class ),
					esc_attr( $ticket->ID ),
					esc_html( $ticket->name )
				);
				?>
			</td>
		</tr>
		<?php
	}

	$modules = Tribe__Tickets__Tickets::modules();
	?>
	<thead>
		<tr class="table-header">
			<th class="ticket_name"><?php esc_html_e( 'Tickets', 'event-tickets' ); ?></th>
			<?php
			/**
			 * Allows for the insertion of additional columns into the ticket table header
			 *
			 * @since TBD
			 */
			do_action( 'tribe_events_tickets_ticket_table_add_header_column' );
			?>
			<th class="ticket_capacity"><?php esc_html_e( 'Capacity', 'event-tickets' ); ?></th>
			<th class="ticket_available"><?php esc_html_e( 'Available', 'event-tickets' ); ?></th>
			<th class="ticket_edit"></th>
		</th>
	</thead>
	<?php

	foreach ( $tickets as $key => $ticket ) {
		if ( strpos( $ticket->provider_class, 'RSVP' ) !== false ) {
			$rsvp[] = $ticket;
		} else {
			$non_rsvp[] = $ticket;
		}
	}

	?>
	<tbody>
		<?php
		if ( ! empty( $non_rsvp ) ) {
			foreach ( $non_rsvp as $ticket ) {
				render_ticket_row( $ticket );
			}
		}

		if ( ! empty( $rsvp ) ) {
			foreach ( $rsvp as $ticket ) {
				render_ticket_row( $ticket );
			}
		}
		?>
	</tbody>
</table>
<?php do_action( 'tribe_ticket_order_field', $post_id );
