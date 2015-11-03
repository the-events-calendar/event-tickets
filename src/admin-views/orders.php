<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-tickets-orders"><br></div>
	<h2><?php esc_html_e( 'Orders', 'event-tickets' ); ?></h2>

	<h2><?php echo esc_html( get_the_title( $event->ID ) ); ?></h2>

	<div id="tribe-filters" class="metabox-holder">
		<div id="filters-wrap" class="postbox">
			<table class="eventtable ticket_list">
				<tr>
					<td width="33%" valign="top">
						<h4><?php esc_html_e( 'Event Summary', 'event-tickets' ); ?></h4>
						<div class="tribe-event-meta tribe-event-meta-date">
							<strong><?php echo esc_html__( 'Date:', 'event-tickets' ); ?></strong>
							<?php echo esc_html( tribe_get_start_date( $event, false ) ); ?>
						</div>
						<div class="tribe-event-meta tribe-event-meta-id">
							<strong><?php echo esc_html__( 'Event ID:', 'event-tickets' ); ?></strong>
							<?php echo absint( $event_id ); ?>
						</div>
						<div class="tribe-event-meta tribe-event-meta-organizer">
							<strong><?php echo esc_html__( 'Organizer:', 'event-tickets' ); ?></strong>
							<a href="<?php echo esc_url( add_query_arg( array( 'user_id' => $organizer->ID ), admin_url( 'profile.php' ) ) ); ?>"><?php echo esc_html( $organizer->user_nicename ); ?></a>
							<?php echo esc_html( sprintf( _x( ' (ID: %s)', 'ID of community organizer', 'event-tickets' ), absint( $event->post_author ) ) ); ?>
						</div>
						<?php do_action( 'tribe_events_community_orders_report_after_organizer', $event, $organizer ); ?>
					</td>
					<td width="33%" valign="top">
						<h4><?php esc_html_e( 'Ticket Sales', 'event-tickets' ); ?></h4>
						<div class="tribe-event-meta tribe-event-meta-tickets-sold">
							<strong><?php echo esc_html__( 'Tickets sold:', 'event-tickets' ); ?></strong>
							<?php echo absint( $total_sold ); ?>
							<?php if ( $total_pending > 0 ) : ?>
								<div id="sales_breakdown_wrapper" class="tribe-event-meta-note">
									<div>
										<?php esc_html_e( 'Completed:', 'event-tickets' ); ?>
										<span id="total_issued"><?php echo esc_html( $total_completed ); ?></span>
									</div>
									<div>
										<?php esc_html_e( 'Awaiting review:', 'event-tickets' ); ?>
										<span id="total_pending"><?php echo esc_html( $total_pending ); ?></span>
									</div>
								</div>
							<?php endif ?>
						</div>
						<?php
						foreach ( $tickets_sold as $ticket_sold ) {
							$price = '';
							$pending = '';
							$sold_message = '';

							if ( $ticket_sold['pending'] > 0 ) {
								$pending = sprintf( _n( '(%d awaiting review)', '(%d awaiting review)', 'event-tickets', $ticket_sold['pending'] ), (int) $ticket_sold['pending'] );
							}

							if ( ! $ticket_sold['has_stock'] ) {
								$sold_message = sprintf( __( 'Sold %d %s', 'event-tickets' ), esc_html( $ticket_sold['sold'] ), $pending );
							} else {
								$sold_message = sprintf( __( 'Sold %d of %d %s', 'event-tickets' ), esc_html( $ticket_sold['sold'] ), esc_html( $ticket_sold['sold'] + absint( $ticket_sold['ticket']->stock() ) ), $pending );
							}

							if ( $ticket_sold['ticket']->price ) {
								$price = ' (' . tribe_format_currency( number_format( $ticket_sold['ticket']->price, 2 ), $event_id ) . ')';
							}
							?>
							<div class="tribe-event-meta tribe-event-meta-tickets-sold-itemized">
								<strong><?php echo esc_html( $ticket_sold['ticket']->name . $price ); ?>:</strong>
								<?php
								echo esc_html( $sold_message );
								if ( $ticket_sold['sku'] ) {
									?>
									<div class="tribe-event-meta-note tribe-event-ticket-sku">
										<?php printf( esc_html__( 'SKU: (%s)', 'event-tickets' ), esc_html( $ticket_sold['sku'] ) ); ?>
									</div>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>
					</td>
					<td width="33%" valign="top">
						<h4>Totals</h4>

						<div class="tribe-event-meta tribe-event-meta-total-revenue">
							<strong><?php esc_html_e( 'Total Revenue:', 'event-tickets' ) ?></strong>
							<?php
							echo esc_html( tribe_format_currency( number_format( $event_revenue, 2 ), $event_id ) );

							if ( $event_fees ) {
								?>
								<div class="tribe-event-meta-note">
									<?php echo esc_html__( '(Tickets + Site Fees)', 'event-tickets' ); ?>
								</div>
								<?php
							}
							?>
						</div>
						<?php
						if ( $event_fees ) {
							?>
							<div class="tribe-event-meta tribe-event-meta-total-ticket-sales">
								<strong><?php esc_html_e( 'Total Ticket Sales:', 'event-tickets' ) ?></strong>
								<?php echo esc_html( tribe_format_currency( number_format( $event_sales, 2 ), $event_id ) ); ?>
							</div>
							<div class="tribe-event-meta tribe-event-meta-total-site-fees">
								<strong><?php esc_html_e( 'Total Site Fees:', 'event-tickets' ) ?></strong>
								<?php echo esc_html( tribe_format_currency( number_format( $event_fees, 2 ), $event_id ) ); ?>
								<div class="tribe-event-meta-note">
									<?php
									echo apply_filters( 'tribe_events_orders_report_site_fees_note', '', $event, $organizer );
									?>
								</div>
							</div>
							<?php
						}//end if
						?>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<form id="topics-filter" method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : '' ); ?>" />
		<input type="hidden" name="event_id" id="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
		<input type="hidden" name="post_type" value="<?php echo esc_attr( $event->post_type ); ?>" />
		<?php echo $table; ?>
	</form>
</div>
