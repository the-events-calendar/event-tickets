<?php return '<div
	id="tribe_panel_base"
	class="ticket_panel panel_base"
	aria-hidden="false"
	data-save-prompt="You have unsaved changes to your tickets. Discard those changes?"
>
	<div class="tribe_sectionheader ticket_list_container">
					<div class="ticket_table_intro">
				
	<a
		href="http://test.tribe.dev/wp-admin/edit.php?post_type=post&#038;page=tpp-orders&#038;event_id=1"
		class="button-secondary"
	>
		View Orders	</a>
				<a
					class="button-secondary"
					href="http://test.tribe.dev/wp-admin/edit.php?page=tickets-attendees&#038;event_id=1"
				>
					View Attendees				</a>
			</div>
			


	<table id="tribe_ticket_list_table" class="tribe-tickets-editor-table eventtable ticket_list eventForm widefat fixed">
		<thead>
			<tr class="table-header">
				<th class="ticket_name column-primary">Tickets</th>
				<th class="ticket_price">Price</th>
				<th class="ticket_capacity">Capacity</th>
				<th class="ticket_available">Available</th>
				<th class="ticket_edit"></th>
			</tr>
		</thead>
				<tbody class="tribe-tickets-editor-table-tickets-body">
			<tr class="Tribe__Tickets__Commerce__PayPal__Main is-expanded" data-ticket-order-id="order_2" data-ticket-type-id="2">
	<td class="column-primary ticket_name Tribe__Tickets__Commerce__PayPal__Main" data-label="Ticket Type:">
		<span class="dashicons dashicons-screenoptions tribe-handle"></span>
		<input
			type="hidden"
			class="tribe-ticket-field-order"
			name="tribe-tickets[list][2][order]"
			value="0"
					>

		
		Test ticket name
			</td>

	<td class="ticket_price" data-label="Price:">
	<span class="tribe-tickets-price-amount amount">&#x24;12.00</span></td>

	<td class="ticket_capacity">
		<span class=\'tribe-mobile-only\'>Capacity:</span>
		(16)	</td>

	<td class="ticket_available">
		<span class=\'tribe-mobile-only\'>Available:</span>
				(16)	</td>

	<td class="ticket_edit">
		<button data-provider=\'Tribe__Tickets__Commerce__PayPal__Main\' data-ticket-id=\'2\' title=\'Ticket ID: 2\' class=\'ticket_edit_button\'><span class=\'ticket_edit_text\'>Test ticket name</span></a>	</td>
</tr>
		</tbody>

		<tbody>
					</tbody>
	</table>

			</div>
	<div class="tribe-ticket-control-wrap">
		
		<button
			id="ticket_form_toggle"
			class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
			aria-label="Add a new ticket"
			""
		>
		New ticket		</button>

		<button
			id="rsvp_form_toggle"
			class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
			aria-label="Add a new RSVP"
		>
			New RSVP		</button>


		<button id="settings_form_toggle" class="button-secondary tribe-button-icon tribe-button-icon-settings">
			Settings		</button>

		
	</div>
	
</div>';
