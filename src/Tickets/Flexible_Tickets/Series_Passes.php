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
use TEC\Events_Pro\Custom_Tables\V1\Templates\Series_Filters;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Exceptions\Invalid_Data_Exception;
use TEC\Tickets\Flexible_Tickets\Models\Capacity;
use TEC\Tickets\Flexible_Tickets\Models\Capacity_Relationship;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Post;
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
		add_action( 'tec_tickets_ticket_update', [ $this, 'update_pass_custom_tables_data' ], 10, 3 );
		add_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );

		$this->container->singleton( Series_Passes\Capacity_Updater::class, Series_Passes\Capacity_Updater::class );
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
		remove_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );
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

		$capacity_data = $data['tribe-ticket'];
		// No mode means unlimited.
		$capacity_mode = $capacity_data['mode'] ?: Capacities::MODE_UNLIMITED;
		$ticket_id     = $ticket->ID;

		DB::transaction( function () use ( $ticket_id, $post_id, $capacity_mode, $capacity_data ) {
			$capacities_relationships = $this->container->get( Repositories\Capacities_Relationships::class );

			// Start by inserting the post_and_post relationship between the ticket and the series.
			Post_And_Post::create( [
				'post_id_1' => (int) $ticket_id,
				'post_id_2' => (int) $post_id,
				'type'      => Posts_And_Posts::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE,
			] );

			if ( $capacity_mode === Capacities::MODE_UNLIMITED ) {
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
				$global_capacity_relationship = $capacities_relationships->find_by_object_id( $post_id );

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
		              && is_int( $ticket_id ) && $ticket_id > 0
		              && ( $ticket = Tickets::load_ticket_object( $ticket_id ) ) instanceof Ticket
		              && $ticket->type() === self::HANDLED_TICKET_TYPE;

		if ( ! $check_args ) {
			return false;
		}

		DB::transaction( function () use ( $post_id, $ticket_id ) {
			$capacities_relationships = $this->container->get( Repositories\Capacities_Relationships::class );
			$capacity_relationship    = $capacities_relationships->find_by_object_id( $ticket_id );

			if ( $capacity_relationship === null ) {
				// No point in continuing if there is no capacity relationship, it might have been deleted already.
				$this->debug( 'No capacity relationship found for ticket ' . $ticket_id );

				return;
			}

			$capacity_relationship->delete();

			$capacities = $this->container->get( Repositories\Capacities::class );
			$capacities->prepareQuery()
			           ->where( 'id', $capacity_relationship->capacity_id )
			           ->delete();

			$posts_and_posts = $this->container->get( Repositories\Posts_And_Posts::class );
			$posts_and_posts->prepareQuery()
			                ->where( 'post_id_1', $ticket_id )
			                ->where( 'post_id_2', $post_id )
			                ->where( 'type', Posts_And_Posts::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE )
			                ->delete();
		} );

		return true;
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
	public function update_pass_custom_tables_data( $post_id, $ticket, $data ): bool {
		if ( ! ( $this->check_upsert_data( $post_id, $ticket, $data ) ) ) {
			return false;
		}

		// Reload the ticket object to make sure we have the latest data and the global stock information.
		$ticket        = Tickets::load_ticket_object( $ticket->ID );
		$ticket_id     = $ticket->ID;
		$capacity_data = $data['tribe-ticket'];
		// No mode means unlimited.
		$new_mode = $capacity_data['mode'] ?: Capacities::MODE_UNLIMITED;

		DB::transaction( function () use ( $post_id, $ticket_id, $new_mode, $capacity_data ) {
			$updater = $this->container->make( Series_Passes\Capacity_Updater::class );
			$updater->update( $post_id, $ticket_id, $new_mode, $capacity_data );
		} );

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
	 * Re-orders the Series content filter to run after the ticket content filter to
	 * have the tickets display after the Series content and before the Series list
	 * of Events.
	 *
	 * This method uses `the_content` filter priority 0 to run once before the Series or Ticket
	 * logic run
	 *
	 * @since TBD
	 *
	 * @param string $content The post content.
	 *
	 * @return string The filtered post content.
	 */
	public function reorder_series_content( $content ) {
		$series_filters = $this->container->make( Series_Filters::class );
		// Move the Series content filter from its default priority of 10 to 20; tickest are injected at 11.
		remove_filter( 'the_content', [ $series_filters, 'inject_content' ] );
		add_filter( 'the_content', [ $series_filters, 'inject_content' ], 20 );
		// It's enough to run this once.
		remove_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );

		return $content;
	}
}