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
use TEC\Common\StellarWP\DB\DB;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
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
		add_action( 'tec_tickets_ticket_add', [ $this, 'insert_pass_custom_tables_data' ], 10, 2 );
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
	public function insert_pass_custom_tables_data( $post_id, $ticket ): bool {
		if ( ! ( $this->check_upsert_data( $post_id, $ticket ) ) ) {
			return false;
		}

		// Reload the ticket object to make sure we have the latest data and the global stock information.
		$ticket = Tickets::load_ticket_object( $ticket->ID );

		DB::transaction( function () use ( $post_id, $ticket ) {
			$ticket_id       = $ticket->ID;
			$capacity        = $ticket->capacity();
			$capacity_mode   = $ticket->global_stock_mode();
			$posts_and_posts = Posts_And_Posts::table_name();

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

			$capacities = Capacities::table_name();

			if ( ! DB::insert(
				$capacities, [
				'value'       => $capacity ?: Capacities::VALUE_UNLIMITED,
				'mode'        => $capacity_mode ?: Global_Stock::OWN_STOCK_MODE,
				'name'        => '',
				'description' => '',
			], [ '%d', '%s', '%s', '%s', ] ) ) {
				$this->error( "Could not insert into $capacities table for ticket {$ticket_id}" );
				// Throw an exception to rollback the transaction.
				throw new \RuntimeException(
					"Could not insert into $capacities table for ticket {$ticket_id}"
				);
			}
			$capacity_id = DB::last_insert_id();

			if ( empty( $capacity_id ) ) {
				$this->error( "Could not get last insert id for $capacities table for ticket {$ticket_id}" );
				// Throw an exception to rollback the transaction.
				throw new \RuntimeException(
					"Could not get last insert id for $capacities table for ticket {$ticket_id}"
				);
			}

			$capacities_relationships = Capacities_Relationships::table_name();

			if ( ! DB::insert(
				$capacities_relationships, [
				'capacity_id' => $capacity_id,
				'object_id'   => $ticket_id,
			], [ '%d', '%d', ] ) ) {
				$this->error( "Could not insert into $capacities_relationships table for ticket {$ticket_id} and capacity {$capacity_id}" );
				// Throw an exception to rollback the transaction.
				throw new \RuntimeException(
					"Could not insert into $capacities_relationships table for ticket {$ticket_id} and capacity {$capacity_id}"
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
	 *
	 * @return bool Whether the data is correct.
	 */
	private function check_upsert_data( $post_id, $ticket ): bool {
		return is_int( $post_id ) && $post_id > 0
		       && (
			       ( $series = get_post( $post_id ) ) instanceof WP_Post
			       && $series->post_type === Series_Post_Type::POSTTYPE
		       )
		       && $ticket instanceof Ticket
		       && ( $ticket->type() ?? 'default' ) === self::HANDLED_TICKET_TYPE;
	}
}