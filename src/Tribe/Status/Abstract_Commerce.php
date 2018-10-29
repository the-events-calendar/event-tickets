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
	public function initialize_status_classes() {}

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
}