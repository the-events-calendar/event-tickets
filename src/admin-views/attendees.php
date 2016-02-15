<?php
$this->attendees_table->prepare_items();

$event_id = $this->attendees_table->event->ID;
$event = $this->attendees_table->event;
$tickets = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
$post_type_object = get_post_type_object( $event->post_type );

$checkedin = Tribe__Tickets__Tickets::get_event_checkedin_attendees_count( $event_id );
$total_sold = 0;
$total_pending = 0;
$total_completed = 0;

foreach ( $tickets as $ticket ) {
	$total_sold += $ticket->qty_sold() + $ticket->qty_pending();
	$total_pending += $ticket->qty_pending();
}
$total_completed   = $total_sold - $total_pending;
$total_attendees   = Tribe__Tickets__Tickets::get_event_attendees_count( $event_id );
$deleted_attendees = $total_sold - $total_attendees;
?>

<div class="wrap tribe-attendees-page">
	<h1><?php esc_html_e( 'Attendees', 'event-tickets' ); ?></h1>
	<div id="tribe-attendees-summary" class="welcome-panel">
		<div class="welcome-panel-content">
			<h3><?php echo '<a href="' . get_edit_post_link( $event_id ) . '" title="' . esc_attr__( 'Edit Event', 'event-tickets' ) . '">' . wp_kses( apply_filters( 'tribe_events_tickets_attendees_event_title', $event->post_title, $event->ID ), array() ) . '</a>'; ?></h3>
			<p class="about-description"><?php echo '<a href="' . get_permalink( $event_id ) . '" title="' . esc_attr__( 'See Event Page', 'event-tickets' ) . '">' . get_permalink( $event_id ) . '</a>'; ?></p>
			<div class="welcome-panel-column-container">
				<div class="welcome-panel-column welcome-panel-first">
					<h4><?php esc_html_e( 'Event Details', 'event-tickets' ); ?></h4>
					<?php do_action( 'tribe_events_tickets_attendees_event_details_top', $event_id ); ?>

					<ul>
						<?php
						/**
						 * Provides an action that allows for the injections of fields at the top of the event details meta ul
						 *
						 * @var $event_id
						 */
						do_action( 'tribe_tickets_attendees_event_details_list_top', $event_id );
						?>
						<li>
							<strong><?php esc_html_e( 'Post Type:', 'event-tickets' ); ?></strong>
							<?php echo esc_html( $post_type_object->labels->singular_name ); ?>
						</li>
						<?php
						/**
						 * Provides an action that allows for the injections of fields at the bottom of the event details meta ul
						 *
						 * @var $event_id
						 */
						do_action( 'tribe_tickets_attendees_event_details_list_bottom', $event_id );
						?>
					</ul>
					<?php do_action( 'tribe_events_tickets_attendees_event_details_bottom', $event_id ); ?>
				</div>
				<div class="welcome-panel-column welcome-panel-middle">
					<h4><?php esc_html_e( 'Sales by Ticket', 'event-tickets' ); ?></h4>
					<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_top', $event_id ); ?>

					<ul>
					<?php foreach ( $tickets as $ticket ) { ?>
						<li>
							<a href="<?php echo get_edit_post_link( $ticket->ID ); ?>" title="<?php esc_html_e( 'Edit Ticket', 'event-tickets' ); ?>"><strong><?php echo esc_html( $ticket->name ) ?>: </strong></a>
							<?php echo tribe_tickets_get_ticket_stock_message( $ticket ); ?>
						</li>
					<?php } ?>
					</ul>
					<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_bottom', $event_id );  ?>
				</div>
				<div class="welcome-panel-column welcome-panel-last alternate">
					<?php do_action( 'tribe_events_tickets_attendees_totals_top', $event_id ); ?>
					<ul>
						<li>
							<strong><?php esc_html_e( 'Total Sold:', 'event-tickets' ) ?></strong>
							<span><?php echo esc_html( $total_sold ); ?></span>
						</li>

						<li>
							<strong><?php esc_html_e( 'Finalized:', 'event-tickets' ); ?></strong>
							<span><?php echo esc_html( $total_completed ); ?></span>
						</li>
					</ul>
					<ul>
						<li>
							<strong><?php esc_html_e( 'Awaiting review:', 'event-tickets' ); ?></strong>
							<span><?php echo esc_html( $total_pending ); ?></span>
						</li>

						<li>
							<strong><?php esc_html_e( 'Checked in:', 'event-tickets' ); ?></strong>
							<span id="total_checkedin"><?php echo esc_html( $checkedin ); ?></span>
						</li>

						<?php if ( $deleted_attendees > 0 ): ?>
							<li>
								<strong><?php esc_html_e( 'Deleted:', 'event-tickets' ); ?></strong>
								<span id="total_deleted"><?php echo esc_html( $deleted_attendees ); ?></span>
							</li>
						<?php endif; ?>
					</ul>
					<?php do_action( 'tribe_events_tickets_attendees_totals_bottom', $event_id ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php do_action( 'tribe_events_tickets_attendees_event_summary_table_after', $event_id ); ?>

	<form id="topics-filter" method="post">
		<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : '' ); ?>" />
		<input type="hidden" name="event_id" id="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
		<input type="hidden" name="post_type" value="<?php echo esc_attr( $event->post_type ); ?>" />
		<?php $this->attendees_table->display() ?>
	</form>
</div>
