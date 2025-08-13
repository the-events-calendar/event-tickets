<?php
/**
 * Handles Event Tickets integration with Classy and Events Pro.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Classy;
 */

namespace TEC\Tickets\Classy;

use Tribe\Events\Virtual\Compatibility\Event_Tickets\Event_Meta;
use Tribe__Editor__Meta as Editor_Meta_Contract;

/**
 * Class ECP_Editor_Meta.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Classy;
 */
class ECP_Editor_Meta extends Editor_Meta_Contract {
	/**
	 * Registers the meta fields supported by Event Tickets when Events Pro is active.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register() {
		register_meta(
			'post',
			Event_Meta::$key_rsvp_email_link,
			$this->text()
		);

		register_meta(
			'post',
			Event_Meta::$key_ticket_email_link,
			$this->text()
		);
	}
}
