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

		// @TODO: @lucatume do we really need this filter? It is not needed to add Series to the list of post types that can have tickets,
		// and it actually forces the Ticket metabox to appear on the Series edit screen even if Series are set to not have tickets.
		//add_filter( 'tribe_tickets_post_types', [ $this, 'update_ticket_post_types' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tribe_tickets_post_types', [ $this, 'update_ticket_post_types' ] );
	}

	/**
	 * Updates the list of post types that can have tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string> $post_types The list of post types that can have tickets.
	 *
	 * @return array<string> The updated list of post types that can have tickets.
	 */
	public function update_ticket_post_types( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return $post_types;
		}

		$post_types[] = Series_Post_Type::POSTTYPE;

		return $post_types;
	}
}