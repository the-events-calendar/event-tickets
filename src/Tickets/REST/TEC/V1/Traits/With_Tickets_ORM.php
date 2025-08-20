<?php
/**
 * Trait to provide tickets ORM access.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use Tribe__Repository__Interface;

/**
 * Trait With_Tickets_ORM.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Tickets_ORM {
	/**
	 * Returns a repository instance.
	 *
	 * @since 5.26.0
	 *
	 * @return Tribe__Repository__Interface The repository instance.
	 */
	public function get_orm(): Tribe__Repository__Interface {
		return tec_tc_tickets();
	}
}
