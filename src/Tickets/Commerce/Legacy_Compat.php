<?php
/**
 * Handles registering and setup for legacy compatibility from Ticket Commerce towards the old Tribe Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use \TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Utils\Value;

/**
 * Class Legacy Compat.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */
class Legacy_Compat extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
//		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required to handle legacy compatibility.
	 *
	 * @since 5.1.10
	 */
	protected function add_actions() {

	}

	/**
	 * Adds the filters required to handle legacy compatibility.
	 *
	 * @since 5.1.10
	 */
	protected function add_filters() {
		add_filter( 'tribe_events_tickets_module_name', [ $this, 'set_legacy_module_name' ] );
	}

	/**
	 * In some instances, the cost format is still handled by legacy code. This replaces it for Tickets Commerce code.
	 *
	 * @deprecated 5.5.11
	 *
	 * @since 5.2.3
	 *
	 * @param string $cost    a formatted price string
	 * @param int    $post_id the event id
	 *
	 * @return string
	 */
	public function maybe_reset_cost_format( $cost, $post_id ) {

		_deprecated_function( __METHOD__, '5.5.11', 'Deprecated in favor of the default handling of cost format in Tickets Commerce.' );

		$provider = tribe_get_event_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field );

		if ( Module::class === $provider ) {
			$value = Value::create( $cost );

			return $value->get_currency();
		}

		return $cost;
	}

	/**
	 * Show the legacy PayPal as not recommended.
	 *
	 * @since 5.1.10
	 *
	 * @param string $name Name of the provider.
	 *
	 * @return string
	 */
	public function set_legacy_module_name( $name ) {
		return $name != 'Tribe Commerce' ? $name : __( 'Tribe Commerce ( Legacy PayPal, not recommended )', 'event-tickets' );
	}
}
