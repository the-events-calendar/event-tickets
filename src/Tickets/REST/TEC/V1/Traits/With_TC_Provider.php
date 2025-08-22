<?php
/**
 * Trait to provide ticket provider.
 *
 * @since 5.26.0
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
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_TC_Provider {
	/**
	 * Returns the ticket provider.
	 *
	 * @since 5.26.0
	 *
	 * @return Ticket_Provider
	 */
	protected function get_provider(): Ticket_Provider {
		return tribe( Module::class );
	}
}
