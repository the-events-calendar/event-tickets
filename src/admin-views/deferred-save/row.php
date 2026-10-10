<?php
/**
 * The hidden templates the classic editor clones for tickets that are not saved yet.
 *
 * The row mirrors `editor/list-row` and the table is the real `editor/list-table`, so a staged
 * ticket looks like a saved one whether it is the first ticket on the post or the tenth.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var int    $post_id       The ID of the post the metabox is for.
 * @var string $not_saved_yet The marker text for a staged ticket.
 * @var string $will_delete   The marker text for a saved ticket staged for deletion.
 * @var string $will_move     The marker text for a saved ticket staged for a move.
 */

$name_label = sprintf(
	// Translators: %s: the singular ticket label.
	_x( '%s Type:', 'ticket type label', 'event-tickets' ),
	tribe_get_ticket_label_singular( 'ticket_type_label' )
);
?>
<template id="tec-tickets-deferred-save-row">
	<tr class="tec-tickets-deferred-save-row is-expanded" data-tec-deferred-save-position="" data-ticket-type="default">
		<td class="column-primary ticket_name" data-label="<?php echo esc_attr( $name_label ); ?>">
			<div class="tribe-tickets__tickets-editor-ticket-name">
				<div class="tribe-tickets__tickets-editor-ticket-name-sortable"></div>
				<div class="tribe-tickets__tickets-editor-ticket-name-title">
					<span data-tec-slot="name"></span>
					<span class="tec-tickets-deferred-save-badge tec-tickets-deferred-save-badge--staged">
						<span class="screen-reader-text"><?php echo esc_html( $not_saved_yet ); ?>.</span>
						<span aria-hidden="true"><?php echo esc_html( $not_saved_yet ); ?></span>
					</span>
				</div>
			</div>
		</td>
		<td class="ticket_price" data-label="<?php esc_attr_e( 'Price:', 'event-tickets' ); ?>">
			<span class="tec-tickets-price amount" data-tec-slot="price"></span>
		</td>
		<td class="ticket_capacity">
			<span class="tribe-mobile-only"><?php esc_html_e( 'Capacity:', 'event-tickets' ); ?></span>
			<span data-tec-slot="capacity"></span>
		</td>
		<td class="ticket_available">
			<span class="tribe-mobile-only"><?php esc_html_e( 'Available:', 'event-tickets' ); ?></span>
			&mdash;
		</td>
		<td class="ticket_edit">
			<button type="button" class="tec-tickets-deferred-save-row__edit" title="<?php esc_attr_e( 'Edit', 'event-tickets' ); ?>">
				<span class="ticket_edit_text" data-tec-slot="name"></span>
			</button>
			<button type="button" class="tec-tickets-deferred-save-row__duplicate" title="<?php esc_attr_e( 'Duplicate', 'event-tickets' ); ?>">
				<span class="ticket_duplicate_text" data-tec-slot="name"></span>
			</button>
			<button type="button" class="tec-tickets-deferred-save-row__remove" title="<?php esc_attr_e( 'Remove', 'event-tickets' ); ?>">
				<span class="ticket_delete_text" data-tec-slot="name"></span>
			</button>
		</td>
	</tr>
</template>
<template id="tec-tickets-deferred-save-table">
	<?php
	$this->template(
		'editor/list-table',
		[
			'post_id'     => $post_id,
			'tickets'     => [],
			'ticket_type' => 'default',
			'table_title' => tribe_get_ticket_label_plural( 'deferred_save_table' ),
		]
	);
	?>
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
