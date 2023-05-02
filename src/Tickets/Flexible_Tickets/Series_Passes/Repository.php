<?php
/**
 * A pseudo-repository to run CRUD operations on Series Passes.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Exception;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities as Capacities_Table;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts as Posts_And_Posts_Table;
use TEC\Tickets\Flexible_Tickets\Exceptions\Custom_Tables_Exception;
use TEC\Tickets\Flexible_Tickets\Exceptions\Invalid_Data_Exception;
use TEC\Tickets\Flexible_Tickets\Models\Capacity;
use TEC\Tickets\Flexible_Tickets\Models\Capacity_Relationship;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Post;
use TEC\Tickets\Flexible_Tickets\Repositories\Capacities;
use TEC\Tickets\Flexible_Tickets\Repositories\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Repositories\Posts_And_Posts;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Repository.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Repository {
	/**
	 * A reference to the Posts_And_Posts repository.
	 *
	 * @since TBD
	 *
	 * @var Posts_And_Posts
	 */
	private Posts_And_Posts $posts_and_posts;
	/**
	 * A reference to the Capacities_Relationships repository.
	 *
	 * @since TBD
	 *
	 * @var Capacities_Relationships
	 */
	private Capacities_Relationships $capacities_relationships;
	/**
	 * An instance of the Capacities repository.
	 *
	 * @since TBD
	 *
	 * @var Capacities
	 */
	private Capacities $capacities;

	public function __construct(
		Posts_And_Posts $posts_and_posts,
		Capacities_Relationships $capacities_relationships,
		Capacities $capacities
	) {
		$this->posts_and_posts          = $posts_and_posts;
		$this->capacities_relationships = $capacities_relationships;
		$this->capacities               = $capacities;
	}

	/**
	 * Given the ID of a Series Pass, returns the last Occurrence of the Series
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ID of the ticket.
	 *
	 * @return WP_Post|null The last occurrence of the series pass.
	 */
	public function get_last_occurrence_by_ticket( int $ticket_id ): ?WP_Post {
		$relationship_type = Posts_And_Posts_Table::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE;
		$relationship      = $this->posts_and_posts
			->prepareQuery()
			->where( 'post_id_1', $ticket_id )
			->where( 'type', $relationship_type )
			->get();

		if ( ! $relationship instanceof Post_And_Post ) {
			return null;
		}

		$last = tribe_events()
			->where( 'series', $relationship->post_id_2 )
			->order_by( 'event_date', 'DESC' )
			->per_page( 1 )
			->first();

		if ( ! $last instanceof WP_Post ) {
			return null;
		}

		return $last;
	}

	/**
	 * Inserts or updates the data to the custom tables for the series pass.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The Series post ID to add the pass to.
	 * @param Ticket $ticket  A reference to the ticket object.
	 *
	 * @return bool Whether the data was added successfully.
	 * @throws Exception If the data could not be added.
	 */
	public function insert_pass_data( int $post_id, Ticket $ticket, $data ): bool {
		// Reload the ticket object to make sure we have the latest data and the global stock information.
		$ticket = Tickets::load_ticket_object( $ticket->ID );

		$capacity_data = $data['tribe-ticket'];
		// No mode means unlimited.
		$capacity_mode = $capacity_data['mode'] ?: Capacities_Table::MODE_UNLIMITED;
		$ticket_id     = $ticket->ID;

		DB::transaction( function () use ( $ticket_id, $post_id, $capacity_mode, $capacity_data ): void {
			// Start by inserting the post_and_post relationship between the ticket and the series.
			Post_And_Post::create( [
				'post_id_1' => (int) $ticket_id,
				'post_id_2' => (int) $post_id,
				'type'      => Posts_And_Posts_Table::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE,
			] );

			if ( $capacity_mode === Capacities_Table::MODE_UNLIMITED ) {
				Capacity_Relationship::create( [
					'object_id'          => $ticket_id,
					'capacity_id'        => Capacity::create_unlimited()->id,
					'parent_capacity_id' => 0,
				] );

				return;
			}

			if ( ! isset( $capacity_data['capacity'] ) ) {
				throw new Invalid_Data_Exception( 'The capacity data is missing.', Invalid_Data_Exception::CAPACITY_VALUE_MISSING );
			}

			if ( in_array( $capacity_mode, [
				Global_Stock::GLOBAL_STOCK_MODE,
				Global_Stock::CAPPED_STOCK_MODE
			], true ) ) {
				if ( ! isset( $capacity_data['event_capacity'] ) ) {
					throw new Invalid_Data_Exception( 'The post capacity data is missing.', Invalid_Data_Exception::EVENT_CAPACITY_VALUE_MISSING );
				}

				// Update or insert the global capacity for the Event.
				$global_capacity_relationship = $this->capacities_relationships->find_by_object_id( $post_id );

				if ( $global_capacity_relationship === null ) {
					$global_capacity              = Capacity::create_global( $capacity_data['event_capacity'] );
					$global_capacity_relationship = Capacity_Relationship::create( [
						'object_id'          => $post_id,
						'capacity_id'        => $global_capacity->id,
						'parent_capacity_id' => 0,
					] );
				}

				if ( $capacity_mode === Global_Stock::GLOBAL_STOCK_MODE ) {
					// Relate the ticket with the global capacity for the Event.
					Capacity_Relationship::create( [
						'object_id'          => $ticket_id,
						'capacity_id'        => $global_capacity_relationship->capacity_id,
						'parent_capacity_id' => 0,
					] );

					return;
				}

				// Capped; create a new capacity for the ticket subordinated to the global capacity for the Event.
				Capacity_Relationship::create( [
					'object_id'          => $ticket_id,
					'capacity_id'        => Capacity::create_capped( $capacity_data['capacity'] )->id,
					'parent_capacity_id' => $global_capacity_relationship->id,
				] );

				return;
			}

			if ( $capacity_mode === Global_Stock::OWN_STOCK_MODE ) {
				// Create a new capacity for the ticket.
				Capacity_Relationship::create( [
					'object_id'          => $ticket_id,
					'capacity_id'        => Capacity::create_own( $capacity_data['capacity'] )->id,
					'parent_capacity_id' => 0,
				] );

				return;
			}

			throw new Invalid_Data_Exception( 'The capacity mode is invalid.', Invalid_Data_Exception::CAPACITY_MODE_INVALID );
		} );

		return true;
	}

	/**
	 * Deletes data from the custom tables when a Series Pass is deleted.
	 *
	 * @since TBD
	 *
	 * @param int $post_id   The Series post ID to delete the pass from.
	 * @param int $ticket_id The ticket ID to delete the pass from.
	 *
	 * @return bool Whether the data was deleted successfully.
	 *
	 * @throws Exception If the data could not be deleted.
	 */
	public function delete_pass_data( int $post_id, int $ticket_id ): bool {
		$return_value = true;
		DB::transaction( function () use ( $post_id, $ticket_id, &$return_value ): void {
			$capacity_relationship = $this->capacities_relationships->find_by_object_id( $ticket_id );

			if ( $capacity_relationship === null ) {
				// No point in continuing if there is no capacity relationship, it might have been deleted already.
				$return_value = false;

				return;
			}

			$capacity_relationship->delete();

			$this->capacities->prepareQuery()
			                 ->where( 'id', $capacity_relationship->capacity_id )
			                 ->delete();

			$this->posts_and_posts->prepareQuery()
			                      ->where( 'post_id_1', $ticket_id )
			                      ->where( 'post_id_2', $post_id )
			                      ->where( 'type', Posts_And_Posts_Table::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE )
			                      ->delete();
		} );

		return $return_value;
	}

	/**
	 * Updates data in the custom tables when a Series Pass is updated.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The Series post ID to update the pass for.
	 * @param Ticket              $ticket  The ticket object to update the pass for.
	 * @param array<string,mixed> $data    The data to update the pass with.
	 *
	 * @return bool Whether the data was updated successfully.
	 *
	 * @throws Exception If the data could not be updated.
	 */
	public function update_pass_data( int $post_id, Ticket $ticket, array $data ): bool {
		// Reload the ticket object to make sure we have the latest data and the global stock information.
		$ticket        = Tickets::load_ticket_object( $ticket->ID );
		$ticket_id     = $ticket->ID;
		$capacity_data = $data['tribe-ticket'];
		// No mode means unlimited.
		$new_mode = $capacity_data['mode'] ?: Capacities_Table::MODE_UNLIMITED;

		DB::transaction( function () use ( $post_id, $ticket_id, $new_mode, $capacity_data ) {
			$capacities_relationships = $this->capacities_relationships;
			$capacity_relationship    = $capacities_relationships->find_by_object_id( $ticket_id );

			if ( $capacity_relationship === null ) {
				throw new Custom_Tables_Exception(
					'The capacity relationship for the Series Pass is missing.',
					Custom_Tables_Exception::CAPACITY_RELATIONSHIP_MISSING
				);
			}

			$capacity = $this->capacities->find_by_id( $capacity_relationship->capacity_id );

			if ( $capacity === null ) {
				throw new Custom_Tables_Exception(
					'The capacity for the Series Pass is missing.',
					Custom_Tables_Exception::CAPACITY_MISSING
				);
			}

			$global_capacity_relationship = $capacities_relationships->find_by_object_id( $post_id );

			$current_mode_is_local  = in_array( $capacity->mode, [
				Capacities_Table::MODE_UNLIMITED,
				Global_Stock::OWN_STOCK_MODE,
				Global_Stock::CAPPED_STOCK_MODE,
			], true );
			$new_mode_is_local      = in_array( $new_mode, [
				Capacities_Table::MODE_UNLIMITED,
				Global_Stock::OWN_STOCK_MODE,
				Global_Stock::CAPPED_STOCK_MODE,
			], true );
			$new_mode_is_unlimited  = $new_mode === Capacities_Table::MODE_UNLIMITED;
			$new_mode_is_capped     = $new_mode === Global_Stock::CAPPED_STOCK_MODE;
			$new_mode_is_global     = $new_mode === Global_Stock::GLOBAL_STOCK_MODE;
			$current_mode_is_global = $capacity->mode === Global_Stock::GLOBAL_STOCK_MODE;

			// If the new mode is not unlimited, we need a capacity value.
			if ( ( ! $new_mode_is_unlimited ) && ! isset( $capacity_data['capacity'] ) ) {
				throw new Invalid_Data_Exception( 'The capacity is missing.', Invalid_Data_Exception::CAPACITY_VALUE_MISSING );
			}

			if ( $new_mode_is_global || $new_mode_is_capped ) {
				// If the new mode is global or capped, we need a global capacity value.
				if ( ! isset( $capacity_data['event_capacity'] ) ) {
					throw new Invalid_Data_Exception( 'The global capacity is missing.', Invalid_Data_Exception::EVENT_CAPACITY_VALUE_MISSING );
				}

				// Update or insert the global capacity.
				if ( $global_capacity_relationship === null ) {
					$global_capacity = Capacity::create_global( $capacity_data['event_capacity'] );
					Capacity_Relationship::create( [
						'object_id'          => $post_id,
						'capacity_id'        => $global_capacity->id,
						'parent_capacity_id' => 0,
					] );
				} else {
					$global_capacity = $this->capacities->find_by_id( $global_capacity_relationship->capacity_id );
				}

				// Update the global capacity.
				$delta                          = $global_capacity->max_value - $global_capacity->current_value;
				$global_capacity->max_value     = (int) $capacity_data['event_capacity'];
				$global_capacity->current_value = $global_capacity->max_value - $delta;
				$global_capacity->save();
			}

			if ( $current_mode_is_local && $new_mode_is_local ) {
				$delta               = $capacity->max_value - $capacity->current_value;
				$capacity->mode      = $new_mode;
				$capacity->max_value = $new_mode_is_unlimited ?
					Capacities_Table::VALUE_UNLIMITED : (int) $capacity_data['capacity'];

				if ( $new_mode_is_unlimited ) {
					$capacity->current_value = Capacities_Table::VALUE_UNLIMITED;
				} else {
					$capacity->current_value = $capacity->max_value - $delta;
				}

				$capacity->save();

				// Update the parent capacity ID depending on the new mode.
				$parent_capacity_id                        = $new_mode_is_capped ? $global_capacity->id : 0;
				$capacity_relationship->parent_capacity_id = $parent_capacity_id;
				$capacity_relationship->save();

				return $capacity;
			}

			if ( $current_mode_is_local && $new_mode_is_global ) {
				$capacity_relationship->capacity_id        = $global_capacity->id;
				$capacity_relationship->parent_capacity_id = 0;
				$capacity_relationship->save();
				$capacity->delete();

				return $global_capacity;
			}

			if ( $current_mode_is_global && $new_mode_is_local ) {
				$capacity = new Capacity( [
					'mode'          => $new_mode,
					'max_value'     => $new_mode_is_unlimited ?
						Capacities_Table::VALUE_UNLIMITED : (int) $capacity_data['capacity'],
					'current_value' => $new_mode_is_unlimited ?
						Capacities_Table::VALUE_UNLIMITED : (int) $capacity_data['capacity'],
					'description'   => '',
					'name'          => ''
				] );
				$capacity->save();
				$capacity_relationship->capacity_id        = $capacity->id;
				$capacity_relationship->parent_capacity_id = $new_mode_is_capped ? $global_capacity->id : 0;
				$capacity_relationship->save();

				return $capacity;
			}

			if ( $new_mode_is_global ) {
				return $global_capacity;
			}

			throw new Custom_Tables_Exception( 'Capacity update from ' . $capacity->mode . ' to ' . $new_mode . ' is not supported.', );
		} );

		return true;
	}
}
