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

	function render_ticket_row( $ticket ) {
		/**
		 * @var Tribe__Tickets__Ticket_Object $ticket
		 */
		$provider     = $ticket->provider_class;
		$provider_obj = call_user_func( array( $provider, 'get_instance' ) );
		?>
		<tr class="<?php echo esc_attr( $provider ); ?>" data-ticket-order-id="order_<?php echo esc_attr( $ticket->ID ); ?>" data-ticket-type-id="<?php echo esc_attr( $ticket->ID ); ?>">
			<!-- (handle, name), price, capacity, available, editlink -->
			<td class=" column-primary ticket_name <?php echo esc_attr( $provider ); ?>">
				<span class="ticket_cell_label">Ticket Type:</span>
				<p><?php echo esc_html( $ticket->name ); ?></p>
				<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
			</td>

			<td class="ticket_price" valign="top">
				<span class="ticket_cell_label">Price:</span>
				<?php
				if ( $provider_obj->get_price_html( $ticket->ID ) ) {
					echo $provider_obj->get_price_html( $ticket->ID );
				} else {
					esc_html_e( 'Free', 'event-tickets' );
				}
				?>
			</td>

			<td class="ticket_capacity">
				<span class="ticket_cell_label">Capacity:</span>
				<?php echo $ticket->display_original_stock(); ?>
			</td>

			<td class="ticket_available">
				<span class="ticket_cell_label">Available:</span>
				<?php
				if ( 'own' === $ticket->global_stock_mode() ) {
					echo absint( $ticket->remaining() );
				} else {
					echo '(' . absint( $ticket->remaining() ) . ')';
				}
				?>
			</td>

			<td class="ticket_edit">
				<?php
				printf(
					"<button attr-provider='%s' attr-ticket-id='%s' class='ticket_edit_button'><span class='ticket_edit_text'>%s</span></a>",
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
			<th class="ticket_name">Tickets</th>
			<th class="ticket_price">Price</th>
			<th class="ticket_capacity">Capacity</th>
			<th class="ticket_available">Available</th>
			<th class="ticket_edit"></th>
		</th>
	</thead>
	<?php
	foreach ($tickets as $key => $ticket) {
		if ( strpos( $ticket->provider_class, "RSVP" ) !== false ) {
			$rsvp[] = $ticket;
			unset( $tickets[ $key ] );
		}
	}

	?>
	<tbody>
		<?php
		foreach ($tickets as $ticket) {
			render_ticket_row( $ticket );
		}

		foreach ($rsvp as $ticket) {
			render_ticket_row( $ticket );
		}
		?>
	</tbody>
</table>
<input type="hidden" name="tickets_order" id="tickets_order" >
