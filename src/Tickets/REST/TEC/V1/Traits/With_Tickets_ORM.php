<?php
/**
 * Trait to provide tickets ORM access.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use Tribe__Repository__Interface;

/**
 * Trait With_Tickets_ORM.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Tickets_ORM {
	/**
	 * Returns a repository instance.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Repository__Interface The repository instance.
	 */
	public function get_orm(): Tribe__Repository__Interface {
		return tribe_tickets( 'tickets-commerce' );
	}
}
