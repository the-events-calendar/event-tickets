<?php
/**
 * A Controller to register basic functionalities common to all the ticket types handled by the feature.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Provider\Controller;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;

/**
 * Class Base.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Base extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Repositories\Capacities::class, Repositories\Capacities::class );
		$this->container->singleton( Repositories\Posts_And_Posts::class, Repositories\Posts_And_Posts::class );
		$this->container->singleton( Repositories\Capacities_Relationships::class, Repositories\Capacities_Relationships::class );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
	}
}