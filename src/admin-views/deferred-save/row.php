<?php
/**
 * The hidden templates the classic editor clones for tickets that are not saved yet.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $not_saved_yet The marker text for a staged ticket.
 * @var string $will_delete   The marker text for a saved ticket staged for deletion.
 * @var string $will_move     The marker text for a saved ticket staged for a move.
 */

?>
<template id="tec-tickets-deferred-save-row">
	<tr class="tec-tickets-deferred-save-row is-expanded" data-tec-deferred-save-position="">
		<td class="column-primary ticket_name">
			<span class="tec-tickets-deferred-save-row__name" data-tec-slot="name"></span>
			<span class="tec-tickets-deferred-save-badge tec-tickets-deferred-save-badge--staged">
				<span class="screen-reader-text"><?php echo esc_html( $not_saved_yet ); ?>.</span>
				<span aria-hidden="true"><?php echo esc_html( $not_saved_yet ); ?></span>
			</span>
		</td>
		<td class="ticket_price" data-tec-slot="price"></td>
		<td class="ticket_capacity" data-tec-slot="capacity"></td>
		<td class="ticket_available">&mdash;</td>
		<td class="ticket_edit">
			<button type="button" class="button-link tec-tickets-deferred-save-row__edit">
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</button>
			<button type="button" class="button-link tec-tickets-deferred-save-row__duplicate">
				<?php esc_html_e( 'Duplicate', 'event-tickets' ); ?>
			</button>
			<button type="button" class="button-link tec-tickets-deferred-save-row__remove">
				<?php esc_html_e( 'Remove', 'event-tickets' ); ?>
			</button>
		</td>
	</tr>
</template>
<template id="tec-tickets-deferred-save-table">
	<table class="tribe_ticket_list_table tribe-tickets-editor-table ticket_list tec-tickets-deferred-save-table" data-tec-slot="table">
		<thead>
			<tr class="table-header">
				<th class="column-primary ticket_name"><?php echo esc_html( tribe_get_ticket_label_singular( 'deferred_save_table' ) ); ?></th>
				<th class="ticket_price"><?php esc_html_e( 'Price', 'event-tickets' ); ?></th>
				<th class="ticket_capacity"><?php esc_html_e( 'Capacity', 'event-tickets' ); ?></th>
				<th class="ticket_available"><?php esc_html_e( 'Available', 'event-tickets' ); ?></th>
				<th class="ticket_edit"></th>
			</tr>
		</thead>
		<tbody class="tribe-tickets-editor-table-tickets-body"></tbody>
	</table>
</template>
<template id="tec-tickets-deferred-save-marker">
	<span class="tec-tickets-deferred-save-badge" data-tec-slot="marker">
		<span class="screen-reader-text" data-tec-slot="marker-text-sr"></span>
		<span aria-hidden="true" data-tec-slot="marker-text"></span>
		<span class="tec-tickets-deferred-save-badge__target" data-tec-slot="marker-target"></span>
		<button type="button" class="button-link tec-tickets-deferred-save-badge__undo"><?php esc_html_e( 'Undo', 'event-tickets' ); ?></button>
	</span>
	<span hidden data-tec-marker-text="staged"><?php echo esc_html( $not_saved_yet ); ?></span>
	<span hidden data-tec-marker-text="delete"><?php echo esc_html( $will_delete ); ?></span>
	<span hidden data-tec-marker-text="move"><?php echo esc_html( $will_move ); ?></span>
</template>
