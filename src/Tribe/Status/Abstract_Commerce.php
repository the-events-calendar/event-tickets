<?php


/**
 * Class Tribe__Tickets__Status__Abstract_Commerce
 *
 * @since TBD
 *
 */
class Tribe__Tickets__Status__Abstract_Commerce {

	public $completed_status_id;

	public $status_names = array();

	public $statuses = array();

	protected $_qty        = 0;
	protected $_line_total = 0;

	/**
	 * Initialize Commerce Provider
	 */
	public function initialize_status_classes() {
	}

	/**
	 * Get the Completed Order
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
	 * @return int
	 */
	public function get_qty() {
		return $this->_qty;
	}

	/**
	 * Add to the Total Order Quantity
	 *
	 * @param int $value
	 */
	public function add_qty( int $value ) {
		$this->_qty += $value;
	}

	/**
	 * Remove from the Total Order Quantity
	 *
	 * @param int $value
	 */
	public function remove_qty( int $value ) {
		$this->_qty -= $value;
	}

	/**
	 * Get Total Order Amount of all Orders for a Post Type, no matter what status they have
	 *
	 * @return int
	 */
	public function get_line_total() {
		return $this->_line_total;
	}

	/**
	 * Add to the Total Line Total
	 *
	 * @param int $value
	 */
	public function add_line_total( int $value ) {
		$this->_line_total += $value;
	}

	/**
	 * Remove from the Total Line Total
	 *
	 * @param int $value
	 */
	public function remove_line_total( int $value ) {
		$this->_line_total -= $value;
	}

	public function ticket_sale( $ticket_sold, $event_id ) {

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

	public function get_name_and_sold_for_ticket( $ticket_sold, $event_id ) {


		$sold_message = ! $ticket_sold['has_stock'] ?
			$sold_message = sprintf( __( 'Sold %d', 'event-tickets-plus' ), esc_html( $ticket_sold['sold'] ) ) :
			'';

		$price = $ticket_sold['ticket']->price ?
			' (' . tribe_format_currency( number_format( $ticket_sold['ticket']->price, 2 ), $event_id ) . ')' :
			'';

		$sku = $ticket_sold['sku'] ?
				'title="' . sprintf( esc_html__( 'SKU: (%s)', 'event-tickets-plus' ), esc_html( $ticket_sold['sku'] ) ) . '"' :
				'';

		ob_start();
		?>
		<strong <?php echo $sku; ?>><?php echo esc_html( $ticket_sold['ticket']->name . $price ); ?>:</strong>
		<?php
		echo esc_html( $sold_message );

		return ob_get_clean();

	}

	public function get_available_incomplete_counts_for_ticket( $ticket_sold ) {

		$availability = array();
		if (  $ticket_sold['ticket']->available() > 0 ) {
			$availability['available'] = sprintf( '%s %s',
				esc_html( $ticket_sold['ticket']->available() ),
				esc_html__( 'available', 'event-tickets-plus' )
			);
		}
		if (  $ticket_sold['incomplete'] > 0 ) {
			$availability['incomplete'] = sprintf( '%s %s',
				 $ticket_sold['incomplete'],
				 _n( 'incomplete order', 'incompleted orders', $ticket_sold['incomplete'], 'event-tickets-plus' )
			);
		}

		if ( empty( $availability ) ) {
			return false;
		}

		return '<div>' . implode( ', ', array_map('esc_html', $availability ) ) . '</div>';


	}
}