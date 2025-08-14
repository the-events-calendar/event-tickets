<?php
/**
 * Trait to provide ticket provider.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Tickets as Ticket_Provider;

/**
 * Trait With_TC_Provider.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_TC_Provider {
	/**
	 * Returns the ticket provider.
	 *
	 * @since TBD
	 *
	 * @return Ticket_Provider
	 */
	protected function get_provider(): Ticket_Provider {
		return tribe( Module::class );
	}
}
