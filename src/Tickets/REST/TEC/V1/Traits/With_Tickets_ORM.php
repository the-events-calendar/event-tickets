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

use TEC\Common\Contracts\Repository_Interface;

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
	 * @since 5.27.0 Updated the return type to Repository_Interface.
	 *
	 * @return Repository_Interface The repository instance.
	 */
	public function get_orm(): Repository_Interface {
		return tec_tc_tickets();
	}
}
