<?php


/**
 * Class Tribe__Tickets__Status__Abstract_Commerce
 *
 * @since 4.10
 *
 */
class Tribe__Tickets__Status__Abstract_Commerce {

	/**
	 * @var string a string for the completed order status, usually completed or publish
	 */
	public $completed_status_id;

	/**
	 * @var array an array of status names for a commerce
	 */
	public $status_names = array();

	/**
	 * @var array an array of status objects
	 */
	public $statuses = array();

	/**
	 * @var int the quantity of tickets sold for a post type
	 */
	protected $qty = 0;

	/**
	 * @var int the amount of tickets sold for a post type
	 */
	protected $line_total = 0;

	/**
	 * Initialize Commerce Provider
	 *
	 * @since 4.10
	 *
	 */
	public function initialize_status_classes() {}

	/**
	 * Get the Completed Order
	 *
	 * @since 4.10
	 *
	 * @return int
	 */
	public function get_completed_status_class() {

		if ( isset( $this->statuses[ $this->completed_status_id ] ) ) {
			return $this->statuses[ $this->completed_status_id ];
		}

		return false;
	}

	/**
	 * Get Total Quantity of Tickets by Post Type, no matter what status they have
	 *
	 * @since 4.10
	 *
	 * @return int
	 */
	public function get_qty() {
		return $this->qty;
	}

	/**
	 * Add to the Total Order Quantity
	 *
	 * @since 4.10
	 *
	 * @param int $value
	 */
	public function add_qty( $value ) {
		$this->qty += $value;
	}

	/**
	 * Remove from the Total Order Quantity
	 *
	 * @since 4.10
	 *
	 * @param int $value
	 */
	public function remove_qty( $value ) {
		$this->qty -= $value;
	}

	/**
	 * Get Total Order Amount of all Orders for a Post Type, no matter what status they have
	 *
	 * @since 4.10
	 *
	 * @return int
	 */
	public function get_line_total() {
		return $this->line_total;
	}

	/**
	 * Add to the Total Line Total
	 *
	 * @since 4.10
	 *
	 * @param int $value
	 */
	public function add_line_total( $value ) {
		$this->line_total += $value;
	}

	/**
	 * Remove from the Total Line Total
	 *
	 * @since 4.10
	 *
	 * @param int $value
	 */
	public function remove_line_total( $value ) {
		$this->line_total -= $value;
	}

	/**
	 * Get Ticket Sale Information Overview
	 *
	 * @since 4.10
	 *
	 * @param $ticket_sold object an object of the ticket to get counts
	 * @param $post_id int an ID of the post the ticket is attached to
	 *
	 * @return string a string Ticket name, sold, and availability
	 */
	public function get_ticket_sale_infomation( $ticket_sold, $event_id ) {

		ob_start();
		?>
		<div class="tribe-event-meta tribe-event-meta-tickets-sold-itemized">
			<?php
				echo $this->get_name_and_sold_for_ticket( $ticket_sold, $event_id );
				echo $this->get_available_incomplete_counts_for_ticket( $ticket_sold );
			?>
		</div>
		<?php

		return ob_get_clean();

	}

	/**
	 * Get Name of Ticket, SKU, Price, and Amount Sold
	 *
	 * @since 4.10
	 *
	 * @param $ticket_sold object an object of the ticket to get counts
	 * @param $post_id int an ID of the post the ticket is attached to
	 *
	 * @return string a string of the ticket name and sold
	 */
	public function get_name_and_sold_for_ticket( $ticket_sold, $post_id ) {

		$sold = $ticket_sold['completed'] ? $ticket_sold['completed'] : $ticket_sold['sold'];

		$sold_message = ! $ticket_sold['has_stock'] ?
			$sold_message = sprintf( __( 'Sold %d', 'event-tickets' ), esc_html( $sold ) ) :
			'';

		$price = $ticket_sold['ticket']->price ?
			' (' . tribe_format_currency( number_format( $ticket_sold['ticket']->price, 2 ), $post_id ) . ')' :
			'';

		$sku = $ticket_sold['sku'] ?
				'title="' . sprintf( esc_html__( 'SKU: (%s)', 'event-tickets' ), esc_html( $ticket_sold['sku'] ) ) . '"' :
				'';

		ob_start();
		?>
		<strong <?php echo $sku; ?>><?php echo esc_html( $ticket_sold['ticket']->name . $price ); ?>:</strong>
		<?php
		echo esc_html( $sold_message );

		return ob_get_clean();

	}

	/**
	 * Get the Available and Incomplete Counts for a Ticket
	 *
	 * @since 4.10
	 *
	 * @param $ticket_sold object an object of the ticket to get counts
	 *
	 * @return bool|string a string of available and/or incomplete counts for a ticket
	 */
	public function get_available_incomplete_counts_for_ticket( $ticket_sold ) {

		$availability = array();
		if (  $ticket_sold['ticket']->available() > 0 ) {
			$availability['available'] = sprintf( '%s %s %s',
				esc_html( $ticket_sold['ticket']->available() ),
				esc_html__( 'available', 'event-tickets' ),
				$this->get_availability_by_ticket_tooltip()
			);
		}
		if (  $ticket_sold['incomplete'] > 0 ) {
			$availability['incomplete'] = sprintf( '%s %s %s',
				 $ticket_sold['incomplete'],
				 __( 'pending order completion', 'event-tickets' ),
				$this->get_pending_by_ticket_tooltip( $ticket_sold )
			);
		}

		if ( empty( $availability ) ) {
			return false;
		}

		return '<div>' . implode( ', ', $availability ) . '</div>';

	}

	/**
	 * Get Sales by Ticket Type Tooltip
	 *
	 * @since 4.10
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_sale_by_ticket_tooltip() {
		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-info"></span>
			<div class="down">
				<?php echo esc_html__( 'Sold counts tickets from completed orders only.', 'event-tickets' ); ?><i></i>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Total Ticket Sales Tooltip
	 *
	 * @since 4.10
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_sale_tooltip() {
		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-info"></span>
			<div class="down">
				<?php echo esc_html__( 'Total Sales counts tickets from all completed orders.', 'event-tickets' ); ?><i></i>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Order Tooltip
	 *
	 * @since 4.10
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_order_tooltip() {
		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-info"></span>
			<div class="down">
				<?php echo esc_html__( 'Total Ordered counts tickets from orders of any status, including pending and refunded.', 'event-tickets' ); ?><i></i>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Pending Tooltip per Ticket
	 *
	 * @since TBD
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_pending_by_ticket_tooltip( $ticket_sold ) {

		$incomplete_statuses = (array) tribe( 'tickets.status' )->get_statuses_by_action( 'count_incomplete', $ticket_sold['ticket']->provider_class, null, true );
		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-info"></span>
			<div class="down">
				<?php echo esc_html__( 'Pending Order Completion refers to anything within the following statuses:', 'event-tickets' ); ?>
				<ul>
					<li><?php echo implode( '</li><li>', $incomplete_statuses ) ?></li>
				</ul>
				<i></i>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}


	/**
	 * Get Availability Tooltip per Ticket
	 *
	 * @since TBD
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_availability_by_ticket_tooltip() {
	/*
	 *
	 * (have a tooltip that serves data per commerce provider: Woo/EDD/TC)

	 Available [info icon]:

	 Inventory (commerce stock):
	 Capacity:
	 Stock:

	 Ticket availability is based on the lowest number of commerce stock, current capacity, and stock. This number is based on the following:
	 - Unlimited tickets should always state unlimited.
	 - Shared capacity is based on either the total shared capacity or if you've limited it to that ticket.
	 - Individual Capacity is what you've assigned to that specific ticket.
	 *
	 *
	 */
		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-info"></span>
			<div class="down">
				<?php echo esc_html__( 'Inventory (commerce stock):', 'event-tickets' ); ?>
				<?php echo esc_html__( 'Capacity:', 'event-tickets' ); ?>
				<?php echo esc_html__( 'Stock:', 'event-tickets' ); ?><i></i>
			</div>
		</div><?php
		return ob_get_clean();
	}

}