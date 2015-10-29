<?php
/**
 * Helps to ensure the original ecommerce-engine specific ticketing plugins
 * remain functional alongside The Events Calendar and Event Tickets.
 *
 * @todo consider removing this class once we are satisfied that enough users
 *       have transitioned away from legacy ticketing solutions
 */
class Tribe__Tickets__Legacy_Provider_Support {
	protected $active_legacy_modules = array();


	public function __construct() {
		add_action( 'init', array( $this, 'on_init' ), 100 );
	}

	/**
	 * Expects to be called late during the "init" action (giving ticket modules sufficient
	 * opportunity to register themselves).
	 */
	public function on_init() {
		$this->find_active_legacy_modules();

		if ( ! count( $this->active_legacy_modules ) ) {
			return;
		}

		// We hook up add_price_fields using an early priority for consistent positioning
		add_action( 'tribe_events_tickets_metabox_advanced', array( $this, 'add_price_fields' ), 5 );
	}

	protected function find_active_legacy_modules() {
		$legacy_classes = array(
			'Tribe__Events__Tickets__Woo__Main',
			'Tribe__Events__Tickets__EDD__Main',
			'Tribe__Events__Tickets__Shopp__Main',
			'Tribe__Events__Tickets__Wpec__Main',
		);

		$active_ticket_modules = Tribe__Tickets__Tickets::modules();

		$this->active_legacy_modules = array_intersect(
			array_keys( $active_ticket_modules ),
			$legacy_classes
		);
	}

	/**
	 * Legacy ticketing modules relied on core The Events Calendar code to generate the price field,
	 * this method takes over that responsibility.
	 */
	public function add_price_fields() {
		$metabox_template = Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/legacy-ticket-fields.php';

		foreach ( $this->active_legacy_modules as $legacy_identifier ) {
			include $metabox_template;
		}
	}
}