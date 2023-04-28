<?php
/**
 * A pseudo-repository to run CRUD operations on Series Passes.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */

namespace TEC\Tickets\Flexible_Tickets\Repositories;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts as Posts_And_Posts_Table;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Post;

/**
 * Class Series_Passes.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Series_Passes {
	/**
	 * A reference to the Posts_And_Posts repository.
	 *
	 * @since TBD
	 *
	 * @var Posts_And_Posts
	 */
	private Posts_And_Posts $posts_and_posts;

	public function __construct( Posts_And_Posts $posts_and_posts ) {
		$this->posts_and_posts = $posts_and_posts;
	}

	/**
	 * Given the ID of a Series Pass, returns the last Occurrence of the Series
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ID of the ticket.
	 *
	 * @return \WP_Post|null The last occurrence of the series pass.
	 */
	public function get_last_occurrence_by_ticket( int $ticket_id ): ?\WP_Post {
		$str          = Posts_And_Posts_Table::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE;
		$relationship = $this->posts_and_posts
			->prepareQuery()
			->where( 'post_id_1', $ticket_id )
			->where( 'type', $str )
			->get();

		if ( ! $relationship instanceof Post_And_Post ) {
			return null;
		}

		$last = tribe_events()->where( 'series', $relationship->post_id_2 )->last();

		if ( ! $last instanceof \WP_Post ) {
			return null;
		}

		return $last;
	}
}