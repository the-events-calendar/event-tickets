<?php
/**
 * @var int post_id The current post ID
 * @var WP_Post                                 $post                The current post object
 * @var WP_User                                 $author              The post author
 * @var string                                  $post_singular_label The post type singular label
 * @var int                                     $total_sold          The total number of tickets sold
 * @var int                                     $total_completed     The total number of completed ticket payments
 * @var int                                     $total_not_completed The total number of not completed ticket payments
 * @var float                                   $post_revenue        The total revenue for this post PayPal tickets sales
 * @var array                                   $tickets_sold        A list of PayPal tickets that have at least one sale
 * @var Tribe__Tickets__Commerce__PayPal__Main  $paypal              The tickets handler object
 * @var array                                   $tickets_breakdown   An array of information about all the sold PayPal tickets
 * @var string                                  $table               The orders table output
 */
?>

<div class="wrap tribe-attendees-page">
	<div id="icon-edit" class="icon32 icon32-tickets-orders"><br></div>

	<div id="tribe-attendees-summary" class="welcome-panel">
		<div class="welcome-panel-content">
			<div class="welcome-panel-column-container">

				<div class="welcome-panel-column welcome-panel-first">
					<h3><?php echo esc_html( sprintf( _x( '%s Details', 'post type details', 'event-tickets' ), $post_singular_label ) ); ?></h3>
					<ul>
						<?php
						/**
						 * Provides an action that allows for the injections of fields at the top of the order report details meta ul
						 *
						 * @since 4.7
						 *
						 * @var $post_id
						 */
						do_action( 'tribe_tickets_report_event_details_list_top', $post_id );

						/**
						 * Provides an action that allows for the injections of fields at the bottom of the order report details ul
						 *
						 * @since 4.7
						 *
						 * @var $event_id
						 */
						do_action( 'tribe_tickets_report_event_details_list_bottom', $post_id );
						?>
					</ul>

					<?php
					/**
					 * Fires after the event details list (in the context of the  Orders Report admin view).
					 *
					 * @since 4.7
					 *
					 * @param WP_Post      $post
					 * @param bool|WP_User $author
					 */
					do_action( 'tribe_tickets_after_event_details_list', $post, $author );
					?>

				</div>
				<div class="welcome-panel-column welcome-panel-middle">
					<h3><?php esc_html_e( 'Sales by Ticket', 'event-tickets' ); ?></h3>
					<?php
					/** @var Tribe__Tickets__Ticket_Object $ticket_sold */
					foreach ( $tickets_sold as $ticket_sold ) {

						//Only Display if a PayPal Ticket otherwise kick out
						if ( ! $paypal->is_paypal_ticket( $ticket_sold ) ) {
							continue;
						}

						$price        = '';

						$sold_message = '';

						if ( ! $ticket_sold->managing_stock() ) {
							$sold_message = sprintf( __( 'Sold %d', 'event-tickets' ),
								esc_html( $ticket_sold->qty_sold() )
							);
						} else {
							$sold_message = sprintf( __( 'Sold %d', 'event-tickets' ),
								esc_html( $ticket_sold->qty_sold() )
							);
						}

						if ( $ticket_sold->price ) {
							$price = ' (' . tribe_format_currency( number_format( $ticket_sold->price, 2 ), $post_id ) . ')';
						}

						$sku = '';
						if ( $ticket_sold->sku ) {
							$sku = 'title="' . sprintf( esc_html__( 'SKU: (%s)', 'event-tickets-plus' ), esc_html( $ticket_sold->sku ) ) . '"';
						}
						?>
						<div class="tribe-event-meta tribe-event-meta-tickets-sold-itemized">
							<strong <?php echo $sku; ?>><?php echo esc_html( $ticket_sold->name . $price ); ?>:</strong>
							<?php
							echo esc_html( $sold_message );
							?>
						</div>
						<?php
					}
					?>
				</div>
				<div class="welcome-panel-column welcome-panel-last alternate">

					<?php
					if (  $total_sold ) {
						$total_sold = '(' . $total_sold . ')';
					}; ?>

					<div class="totals-header">
						<h3><?php echo esc_html( sprintf( __( 'Total Sales: %s %s', 'event-tickets' ), esc_html( tribe_format_currency( number_format( $post_revenue, 2 ), $post_id ) ), $total_sold ) ); ?></h3>
					</div>

					<div id="sales_breakdown_wrapper" class="tribe-event-meta-note">
						<?php foreach ( $tickets_breakdown as $status_label => $details ) : ?>
							<div>
								<strong><?php echo esc_html( $status_label ); ?></strong>
								<?php echo esc_html( tribe_format_currency( number_format( $details['total'], 2 ), $post_id ) ); ?>
								<span>(<?php echo esc_html( $details['qty'] ); ?>)</span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<form id="topics-filter" method="get">
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'page' : 'tribe[page]' ); ?>"
		       value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : '' ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_id' : 'tribe[event_id]' ); ?>" id="event_id"
		       value="<?php echo esc_attr( $post_id ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>"
		       value="<?php echo esc_attr( $post->post_type ); ?>"/>
		<?php echo $table; ?>
	</form>
</div>
