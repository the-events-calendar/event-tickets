<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Status_Manager.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Status_Manager extends \Tribe__Tickets__Status__Abstract_Commerce {

	/**
	 * Determines which is the Status that is used for completion of an order.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $completed_status_id = Status\Completed::class;

	/**
	 * @inheritdoc
	 */
	public $status_names = [
		Status\Completed::class,
		Status\Denied::class,
		Status\Not_Completed::class,
		Status\Pending::class,
		Status\Refunded::class,
		Status\Reversed::class,
		Status\Undefined::class,
	];

	/**
	 * @inheritdoc
	 */
	public $statuses = [];

	/**
	 * Sets up all the Status instances for the Classes registered on $status_name.
	 *
	 * @since TBD
	 */
	public function boot() {
		$this->statuses = array_map( static function( $class_name ) {
			return new $class_name;
		}, $this->statuses );
	}
}