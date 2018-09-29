<?php


/**
 * Class Tribe__Tickets__RSVP__Status_Manager
 *
 * @since TBD
 *
 */
class Tribe__Tickets__RSVP__Status_Manager {

	public $status_names = array(
		'No',
		'Yes',
	);

	public $statuses = array();

	public function __construct() {

		$this->initialize_status_classes();
	}


	public function initialize_status_classes() {

		foreach ( $this->status_names as $name ) {

			$class_name = 'Tribe__Tickets__RSVP__Status__' . $name;

			$this->statuses[ $name ] = new $class_name();
		}
	}
}