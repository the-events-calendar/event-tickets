<?php
/**
 * A Controller to register basic functionalities common to all the ticket types handled by the feature.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;

/**
 * Class Base.
 *
 * @since TBD
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
		add_filter( 'tribe_tickets_post_types', [ $this, 'update_ticket_post_types' ] );
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

	public function update_ticket_post_types( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return $post_types;
		}

		$post_types[] = Series_Post_Type::POSTTYPE;

		return $post_types;
	}
}