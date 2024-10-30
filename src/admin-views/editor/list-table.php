<?php
/**
 * @var string $table_title The title of the table.
 * @var Tribe__Tickets__Ticket_Object[] $tickets The tickets to display.
 * @var string|null $ticket_type The type of ticket the table is being rendered for.
 */

if ( ! isset( $post_id ) ) {
	$post_id = get_the_ID();
}

if ( ! $post_id ) {
	$post_id = tribe_get_request_var( 'post_id', 0 );
}

// Makes sure we are dealing an int
$post_id = (int) $post_id;

if ( 0 === $post_id ) {
	$post_type = tribe_get_request_var( 'post_type', 'post' );
} else {
	$post_type = get_post_type( $post_id );
}

$modules = Tribe__Tickets__Tickets::modules();

/** @var Tribe__Tickets__Admin__Views $admin_views */
$admin_views = tribe( 'tickets.admin.views' );

$ticket_type = $ticket_type ?? 'default';
?>

<?php if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) : ?>
<div class="ticket_list_wrapper">
<?php endif; ?>
	<table class="tribe_ticket_list_table tribe-tickets-editor-table eventtable ticket_list eventForm widefat fixed">
		<thead>
			<tr class="table-header">
				<th class="ticket_name column-primary">
					<?php
					/**
					 * Allows for the insertion of icons into the ticket table header for a specific ticket type.
					 *
					 * @since 5.8.0
					 */
					do_action( "tec_tickets_editor_list_table_title_icon_{$ticket_type}" );
					?>
					<?php echo esc_html( $table_title ); ?>
				</th>
				<?php
				/**
				 * Allows for the insertion of additional columns into the ticket table header.
				 *
				 * @since 4.6
				 */
				do_action( 'tribe_events_tickets_ticket_table_add_header_column' );
				?>
				<th class="ticket_capacity"><?php esc_html_e( 'Capacity', 'event-tickets' ); ?></th>
				<th class="ticket_available"><?php esc_html_e( 'Available', 'event-tickets' ); ?></th>
				<th class="ticket_edit"></th>
			</tr>
		</thead>
		<?php

		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$tickets = $handler->sort_tickets_by_menu_order( $tickets );
		?>
		<tbody class="tribe-tickets-editor-table-tickets-body">
		<?php
		if ( ! empty( $tickets ) ) {
			foreach ( $tickets as $ticket ) {
				$admin_views->template( [ 'editor', 'list-row' ], [ 'ticket' => $ticket, 'post_id' => $post_id ] );
			}
		}
		?>
		</tbody>
	</table>
<?php do_action( 'tribe_ticket_order_field', $post_id ); ?>

<?php if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) : ?>
</div>
<?php endif;
