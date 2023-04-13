<?php
/**
 * Handles the Series Passes integration at different levels.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use Exception;
use tad_DI52_Container;
use TEC\Common\Provider\Controller;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Exceptions\Custom_Tables_Exception;
use TEC\Tickets\Flexible_Tickets\Exceptions\Invalid_Data_Exception;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Series_Passes.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Series_Passes extends Controller {
	/**
	 * The ticket type handled by this class.
	 *
	 * @since TBD
	 */
	public const HANDLED_TICKET_TYPE = 'series_pass';

	/**
	 * A reference to the templates handler.
	 *
	 * @since TBD
	 *
	 * @var Admin_Views
	 */
	private Admin_Views $admin_views;

	/**
	 * Series_Passes constructor.
	 *
	 * since TBD
	 *
	 * @param tad_DI52_Container $container   The container instance.
	 * @param Admin_Views        $admin_views The templates handler.
	 */
	public function __construct(
		tad_DI52_Container $container,
		Admin_Views $admin_views
	) {
		parent::__construct( $container );
		$this->admin_views = $admin_views;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		add_action( 'tec_tickets_ticket_add', [ $this, 'insert_pass_custom_tables_data' ], 10, 3 );
		add_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'delete_pass_custom_tables_data' ], 5, 2 );
		add_action( 'tec_tickets_ticket_update', [ $this, 'update_pass_custom_tables_data' ], 10, 2 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		remove_action( 'tec_tickets_ticket_add', [ $this, 'insert_pass_custom_tables_data' ] );
		remove_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'delete_pass_custom_tables_data' ], 5 );
		remove_action( 'tec_tickets_ticket_update', [ $this, 'update_pass_custom_tables_data' ] );
	}

	/**
	 * Adds the toggle to the new ticket form.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void The toggle is added to the new ticket form.
	 */
	public function render_form_toggle( $post_id ): void {
		if ( ! ( is_numeric( $post_id ) && $post_id > 0 ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post && $post->post_type === Series_Post_Type::POSTTYPE ) ) {
			return;
		}

		$ticket_providing_modules = array_diff_key( Tickets::modules(), [ RSVP::class => true ] );
		$this->admin_views->template( 'form-toggle', [
			'disabled' => count( $ticket_providing_modules ) === 0,
		] );
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
	public function insert_pass_custom_tables_data( $post_id, $ticket, $data ): bool {
		if ( ! ( $this->check_upsert_data( $post_id, $ticket, $data ) ) ) {
			return false;
		}

		// Reload the ticket object to make sure we have the latest data and the global stock information.
		$ticket = Tickets::load_ticket_object( $ticket->ID );

		if ( ! isset( $data['tribe-ticket']['mode'] ) ) {
			throw new Invalid_Data_Exception(
				'Capacity mode is required',
				Invalid_Data_Exception::CAPACITY_MODE_MISSING
			);
		}

		$capacity_data = $data['tribe-ticket'];
		// No mode means unlimited.
		$capacity_mode = $capacity_data['mode'] ?: Capacities::MODE_UNLIMITED;
		$ticket_id     = $ticket->ID;

		DB::transaction( function () use ( $ticket_id, $post_id, $capacity_mode, $capacity_data ) {
			$posts_and_posts = Posts_And_Posts::table_name();

			// Start by inserting th e Ticket <> Series relationship.
			if ( ! DB::insert(
				$posts_and_posts, [
				'post_id_1' => (int) $ticket_id,
				'post_id_2' => (int) $post_id,
				'type'      => Posts_And_Posts::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE,
			], [ '%d', '%d', '%s', ] ) ) {
				$this->error( "Could not insert into $posts_and_posts table for ticket {$ticket_id} and series {$post_id}" );
				// Throw an exception to rollback the transaction.
				throw new \RuntimeException(
					"Could not insert into $posts_and_posts table for ticket {$ticket_id} and series {$post_id}"
				);
			}

			switch ( $capacity_mode ) {
				case Capacities::MODE_UNLIMITED:
					$this->insert_unlimited_capacity_data( $ticket_id );
					break;
				case Global_Stock::GLOBAL_STOCK_MODE:
					if ( ! isset( $capacity_data['event_capacity'] ) ) {
						throw new Invalid_Data_Exception(
							'Event capacity is required when using global stock mode',
							Invalid_Data_Exception::GLOBAL_STOCK_MODE_MISSING_EVENT_CAPACITY
						);
					}
					// Insert the global capacity for the Event.
					[ $capacity_id ] = $this->insert_global_capacity_data( $post_id, $capacity_data['event_capacity'] );
					// Insert the relationship between the Ticket and the Event global capacity.
					$this->insert_capacities_relationships_data( [
						'object_id'          => $ticket_id,
						'capacity_id'        => $capacity_id,
						'parent_capacity_id' => 0
					] );
					break;
				case Global_Stock::CAPPED_STOCK_MODE:
					if ( ! isset( $capacity_data['event_capacity'] ) ) {
						throw new Invalid_Data_Exception(
							'Event capacity is required when using capped stock mode',
							Invalid_Data_Exception::CAPPED_STOCK_MODE_MISSING_EVENT_CAPACITY
						);
					}
					if ( ! isset( $capacity_data['capacity'] ) ) {
						throw new Invalid_Data_Exception(
							'Ticket capacity is required when using capped stock mode',
							Invalid_Data_Exception::CAPPED_STOCK_MODE_MISSING_TICKET_CAPACITY
						);
					}
					// Insert the global capacity for the Event.
					$capacities_relationships = Capacities_Relationships::table_name();
					$parent_capacity_id       = DB::get_var( DB::prepare(
						"SELECT capacity_id FROM {$capacities_relationships} WHERE parent_capacity_id = 0 AND object_id = %d",
						$post_id
					) );
					if ( ! $parent_capacity_id ) {
						[ $parent_capacity_id ] = $this->insert_global_capacity_data( $post_id, $capacity_data['event_capacity'] );
					}
					// Insert the capped capacity for the Ticket.
					$this->insert_capped_capacity_data( $ticket_id, $parent_capacity_id, $capacity_data['capacity'] );
					break;
				case Global_Stock::OWN_STOCK_MODE:
					$this->insert_own_capacity_data( $ticket_id, $capacity_data['capacity'] );
					break;
				default:
					throw new Invalid_Data_Exception(
						'Invalid capacity mode: ' . $capacity_mode,
						Invalid_Data_Exception::INVALID_CAPACITY_MODE
					);
			}
		} );

		$this->debug( "Added Series Pass custom tables data for Ticket {$ticket->ID} and Series {$post_id}" );

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
	public function delete_pass_custom_tables_data( $post_id, $ticket_id ): bool {
		$check_args = is_int( $post_id ) && $post_id > 0
		              && (
			              ( $series = get_post( $post_id ) ) instanceof WP_Post
			              && $series->post_type === Series_Post_Type::POSTTYPE
		              )
		              && is_int( $ticket_id ) && $ticket_id > 0;

		if ( ! $check_args ) {
			return false;
		}

		$ticket = Tickets::load_ticket_object( $ticket_id );

		if ( ! ( $ticket instanceof Ticket && ( $ticket->type() ?? 'default' ) === self::HANDLED_TICKET_TYPE ) ) {
			return false;
		}

		DB::transaction( function () use ( $post_id, $ticket_id ) {
			$capacities_relationships = Capacities_Relationships::table_name();

			$capacity_id = DB::get_var(
				DB::prepare(
					"SELECT capacity_id FROM $capacities_relationships WHERE object_id = %d",
					$ticket_id
				)
			);

			if ( empty( $capacity_id ) ) {
				$this->error( "Could not get capacity id for ticket {$ticket_id}" );
				// Throw an exception to rollback the transaction.
				throw new \RuntimeException(
					"Could not get capacity id for ticket {$ticket_id}"
				);
			}

			$posts_and_posts = Posts_And_Posts::table_name();

			if ( false === DB::delete(
					$posts_and_posts, [
					'post_id_1' => (int) $ticket_id,
					'post_id_2' => (int) $post_id,
					'type'      => Posts_And_Posts::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE,
				], [ '%d', '%d', '%s', ] ) ) {
				$this->error( "Could not delete from $posts_and_posts table for ticket {$ticket_id} and series {$post_id}" );
				// Throw an exception to rollback the transaction.
				throw new \RuntimeException(
					"Could not delete from $posts_and_posts table for ticket {$ticket_id} and series {$post_id}"
				);
			}

			$capacity_relaionships_count = (int) DB::get_var(
				DB::prepare(
					"SELECT COUNT(*) FROM $capacities_relationships WHERE capacity_id = %d",
					$capacity_id
				)
			);

			if ( false === DB::delete(
					$capacities_relationships, [
					'object_id' => $ticket_id,
				], [ '%d', ] ) ) {
				$this->error( "Could not delete from $capacities_relationships table for ticket {$ticket_id}" );
				// Throw an exception to rollback the transaction.
				throw new \RuntimeException(
					"Could not delete from $capacities_relationships table for ticket {$ticket_id}"
				);
			}

			if ( $capacity_relaionships_count === 1 ) {
				// The ticket being deleted was the only one using this capacity, remove it.
				$capacities = Capacities::table_name();

				if ( false === DB::delete(
						$capacities, [
						'id' => $capacity_id,
					], [ '%d', ] ) ) {
					$this->error( "Could not delete from $capacities table for capacity {$capacity_id}" );
					// Throw an exception to rollback the transaction.
					throw new \RuntimeException(
						"Could not delete from $capacities table for capacity {$capacity_id}"
					);
				}
			}
		} );

		return true;
	}

	/**
	 * Updates data in the custom tables when a Series Pass is updated.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The Series post ID to update the pass for.
	 * @param Ticket $ticket  The ticket object to update the pass for.
	 *
	 * @return bool Whether the data was updated successfully.
	 *
	 * @throws Exception If the data could not be updated.
	 */
	public function update_pass_custom_tables_data( $post_id, $ticket ): bool {
		if ( ! ( $this->check_upsert_data( $post_id, $ticket ) ) ) {
			return false;
		}

		// Reload the ticket object to make sure we have the latest data and the global stock information.
		$ticket = Tickets::load_ticket_object( $ticket->ID );

		return true;
	}

	/**
	 * Parses the data passed as input to insert or update a Series Pass to make sure
	 * it's correct.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID the ticket has been created or updated for.
	 * @param Ticket $ticket  The created or updated ticket object.
	 * @param array  $data    The data to insert or update for the ticket.
	 *
	 * @return bool Whether the data is correct.
	 */
	private function check_upsert_data( $post_id, $ticket, $data ): bool {
		return is_int( $post_id ) && $post_id > 0
		       && (
			       ( $series = get_post( $post_id ) ) instanceof WP_Post
			       && $series->post_type === Series_Post_Type::POSTTYPE
		       )
		       && $ticket instanceof Ticket
		       && ( $ticket->type() ?? 'default' ) === self::HANDLED_TICKET_TYPE
		       && is_array( $data );
	}

	/**
	 * Inserts data in the custom tables for an object with unlimited capacity.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The object ID to insert the unlimited capacity data for.
	 *
	 * @return array<int> The IDs of the inserted capacity and capacity relationship.
	 *
	 * @throws Custom_Tables_Exception If the data could not be inserted.
	 */
	private function insert_unlimited_capacity_data( int $object_id ): array {
		$capacity_id = $this->insert_capacities_data( [
			'max_value'     => (int) Capacities::VALUE_UNLIMITED,
			'current_value' => (int) Capacities::VALUE_UNLIMITED,
			'mode'          => Capacities::MODE_UNLIMITED,
			'name'          => '',
			'description'   => '',
		] );

		$capacity_relationship_id = $this->insert_capacities_relationships_data( [
			'object_id'          => $object_id,
			'parent_capacity_id' => 0,
			'capacity_id'        => $capacity_id,
		] );

		return [ $capacity_id, $capacity_relationship_id ];
	}

	/**
	 * Inserts data into the Capacities custom table.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The data to insert.
	 *
	 * @return int The ID of the inserted row.
	 *
	 * @throws Custom_Tables_Exception
	 */
	private function insert_capacities_data( array $data ): int {
		$capacities = Capacities::table_name();

		if ( ! DB::insert(
			$capacities, [
			'max_value'     => $data['max_value'],
			'current_value' => $data['current_value'],
			'mode'          => $data['mode'],
			'name'          => $data['name'],
			'description'   => $data['description'],
		], [ '%d', '%s', '%s', '%s', ] ) ) {
			$message = "Could not insert data into $capacities table: " . json_encode( $data, JSON_PRETTY_PRINT );

			$this->error( $message );
			// Throw an exception to rollback the transaction.
			throw new Custom_Tables_Exception( $message, Custom_Tables_Exception::CAPACITIES_INSERT_ERROR );
		}

		$last_insert_id = DB::last_insert_id();

		if ( ! $last_insert_id ) {
			$message = "Could not get last insert ID for $capacities table: " . json_encode( $data, JSON_PRETTY_PRINT );

			$this->error( $message );
			// Throw an exception to rollback the transaction.
			throw new Custom_Tables_Exception( $message, Custom_Tables_Exception::CAPACITIES_INSERT_ERROR );
		}

		return $last_insert_id;
	}

	/**
	 * Inserts data into the Capacities Relationships custom table.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The data to insert.
	 *
	 * @return int The ID of the inserted row.
	 *
	 * @throws Custom_Tables_Exception
	 */
	private function insert_capacities_relationships_data( array $data ): int {
		$capacities_relationships = Capacities_Relationships::table_name();

		if ( ! DB::insert(
			$capacities_relationships, [
			'parent_capacity_id' => $data['parent_capacity_id'],
			'capacity_id'        => $data['capacity_id'],
			'object_id'          => $data['object_id']
		], [ '%d', '%d', ] ) ) {
			$message = "Could not insert into $capacities_relationships table: " . json_encode( $data, JSON_PRETTY_PRINT );
			$this->error( $message );
			// Throw an exception to rollback the transaction.
			throw new Custom_Tables_Exception( $message, Custom_Tables_Exception::CAPACITIES_RELATIONSHIPS_INSERT_ERROR );
		}

		$last_insert_id = DB::last_insert_id();

		if ( empty( $last_insert_id ) ) {
			$message = "Could not get last insert ID for $capacities_relationships table: " . json_encode( $data, JSON_PRETTY_PRINT );
			$this->error( $message );
			// Throw an exception to rollback the transaction.
			throw new Custom_Tables_Exception( $message, Custom_Tables_Exception::CAPACITIES_RELATIONSHIPS_INSERT_ERROR );
		}

		return $last_insert_id;
	}

	/**
	 * Inserts data in the custom tables for an object with global capacity.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The object ID to insert the global capacity data for.
	 * @param int $capacity  The global capacity value.
	 *
	 * @return array<int> The IDs of the inserted capacity and capacity relationship.
	 *
	 * @throws Custom_Tables_Exception If the data could not be inserted.
	 */
	private function insert_global_capacity_data( int $object_id, int $capacity ): array {
		$global_capacity_id = $this->insert_capacities_data( [
			'max_value'     => $capacity,
			'current_value' => $capacity,
			'mode'          => Global_Stock::CAPPED_STOCK_MODE,
			'name'          => '',
			'description'   => '',
		] );

		$global_capacity_relationship_id = $this->insert_capacities_relationships_data( [
			'object_id'          => $object_id,
			'parent_capacity_id' => 0,
			'capacity_id'        => $global_capacity_id,
		] );

		return [ $global_capacity_id, $global_capacity_relationship_id ];
	}

	/**
	 * Inserts data in the custom tables for an object with capped capacity.
	 *
	 * @since TBD
	 *
	 * @param int $object_id          The object ID to insert the capped capacity data for.
	 * @param int $parent_capacity_id The ID of the parent capacity.
	 * @param int $capacity           The capped capacity value.
	 *
	 * @return array<int> The IDs of the inserted capacity and capacity relationship.
	 *
	 * @throws Custom_Tables_Exception If the data could not be inserted.
	 */
	private function insert_capped_capacity_data( int $object_id, int $parent_capacity_id, int $capacity ): array {
		$capacity_id = $this->insert_capacities_data( [
			'max_value'     => $capacity,
			'current_value' => $capacity,
			'mode'          => Global_Stock::CAPPED_STOCK_MODE,
			'name'          => '',
			'description'   => '',
		] );

		$capacity_relationship_id = $this->insert_capacities_relationships_data( [
			'object_id'          => $object_id,
			'parent_capacity_id' => $parent_capacity_id,
			'capacity_id'        => $capacity_id,
		] );

		return [ $capacity_id, $capacity_relationship_id ];
	}

	/**
	 * Inserts data in the custom tables for an object with own capacity.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The object ID to insert the own capacity data for.
	 * @param int $capacity  The own capacity value.
	 *
	 * @return array<int> The IDs of the inserted capacity and capacity relationship.
	 *
	 * @throws Custom_Tables_Exception If the data could not be inserted.
	 */
	private function insert_own_capacity_data( $object_id, $capacity ): array {
		$capacity_id = $this->insert_capacities_data( [
			'max_value'     => $capacity,
			'current_value' => $capacity,
			'mode'          => Global_Stock::OWN_STOCK_MODE,
			'name'          => '',
			'description'   => '',
		] );

		$capacity_relationship_id = $this->insert_capacities_relationships_data( [
			'object_id'          => $object_id,
			'parent_capacity_id' => 0,
			'capacity_id'        => $capacity_id,
		] );

		return [ $capacity_id, $capacity_relationship_id ];
	}
}