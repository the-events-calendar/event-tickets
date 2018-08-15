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
	protected $status_managers = array(
		//'EDD' => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Status_Manager',
		//'RSVP' => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Status_Manager',
		//'Tribe Commerce' => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Status_Manager',
		'WooCommerce' => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Status_Manager',
	);


	/**
	 * An array of status objects for WooCommerce Tickets
	 *
	 * @var array
	 */
	protected $statues = array();

	/**
	 * Static Singleton Holder
	 *
	 * @var self
	 */
	protected $instance;

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @return self
	 */
	public function instance() {
		if ( ! $this->instance ) {
			$this->instance = new self;
		}

		return $this->instance;
	}


	public function hook() {
		add_action( 'init', array( $this, 'setup' ), 10 );
	}

	public function setup() {
		$this->active_modules = Tribe__Tickets__Tickets::modules();
		$this->get_statuses_by_provider();

		//log_me( $this->statues );
	}

	protected function get_statuses_by_provider() {

		foreach ( $this->active_modules as $module_class => $module_name ) {

			if ( ! isset( $this->status_managers[ $module_name ] ) ) {
				continue;
			}

			$status_class                  = $this->status_managers[ $module_name ];
			$this->statues[ $module_name ] = new $status_class();
		}

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
	protected function get_provider_by_id( $id ) {
		return tribe( 'tickets.data_api' )->get_ticket_provider( $id );
	}

	public function get_trigger_statuses( $commerce ) {

		$trigger_statuses = array();

		if ( ! isset( $this->statues[ $commerce ]->statuses ) ) {
			return $trigger_statuses;
		}

		$filtered_statuses = wp_list_filter(
			$this->statues[ $commerce ]->statuses,
			array(
		    'trigger_option' => true
			)
		);

		foreach ( $filtered_statuses as $status ) {
			$trigger_statuses[ $status->provider_name ] = $status->name;
		}

		return $trigger_statuses;

	}

}