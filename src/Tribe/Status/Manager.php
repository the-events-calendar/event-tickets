<?php


/**
 * Class Tribe__Tickets__Status__Manager
 *
 * @since TBD
 */
class Tribe__Tickets__Status__Manager {

	/**
	 * todo
	 *
	 * Determine which provider to use
	 * Get all the Statuses for a provider
	 * get_trigger_statues and set it status objects
	 *
	 * load once for all ecommerce
	 *
	 *
	 *
	 */

	/**
	 * @var array
	 */
	protected $active_modules;

	/**
	 * An array of status objects for WooCommerce Tickets
	 *
	 * @var array
	 */
	 protected $woo_statues = array();

	/**
	 * An array of status objects for EDD Tickets
	 *
	 * @var array
	 */
	 protected $edd_statues = array();

	/**
	 * An array of status objects for Tribe Commerce Tickets
	 *
	 * @var array
	 */
	 protected $tpp_statues = array();

	/**
	 * An array of status objects for RSVP
	 *
	 * @var array
	 */
	 protected $rsvp_statues = array();


	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->active_modules = Tribe__Tickets__Tickets::modules();
		//$this->setup_data();
	}


	/**
	 * Gets the ticket provider class when passed an id
	 *
	 * @since TBD
	 *
	 * @param integer|string $id a rsvp order key, order id, attendee id, ticket id, or product id
	 *
	 * @return bool|object
	 */
	protected function get_ticket_provider( $id ) {
		return tribe( 'tickets.data_api' )->get_ticket_provider( $id );
	}

}