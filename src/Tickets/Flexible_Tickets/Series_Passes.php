<?php
/**
 * Handles the Series Passes integration at different levels.
 *
 * @since TBD
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
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Global_Stock as Global_Stock;
use WP_Post;

/**
 * Class Series_Passes.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Series_Passes extends Controller {

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
	 * @param tad_DI52_Container $container The container instance.
	 * @param Admin_Views $admin_views The templates handler.
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
		add_action( 'tec_tickets_ticket_added_series_pass', [ $this, 'add_pass_custom_tables_data' ], 10, 3 );

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
		remove_action( "tec_tickets_ticket_added_series_pass", [ $this, 'add_pass_custom_tables_data' ] );
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
	 * Adds the data to the custom tables for the series pass.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The Series post ID to add the pass to.
	 * @param int $ticket_id The ticket post ID.
	 * @param array<string,mixed> $ticket_data The ticket data.
	 *
	 * @return bool Whether the data was added successfully.
	 * @throws Exception If the data could not be added.
	 */
	public function add_pass_custom_tables_data( $post_id, $ticket_id, $ticket_data ): bool {
		$check_args = is_int( $post_id ) && $post_id > 0
		              && is_int( $ticket_id ) && $ticket_id > 0
		              && is_array( $ticket_data )
		              && (
			              ( $series = get_post( $post_id ) ) instanceof WP_Post
			              && $series->post_type === Series_Post_Type::POSTTYPE
		              );

		if ( ! $check_args ) {
			return false;
		}

		DB::transaction( function () use ( $post_id, $ticket_id, $ticket_data ) {
			if ( ! ( DB::insert(
				Posts_And_Posts::table_name(), [
				'post_id_1' => (int) $ticket_id,
				'post_id_2' => (int) $post_id,
				'type'      => Posts_And_Posts::TYPE_TICKET_AND_POST_PREFIX . Series_Post_Type::POSTTYPE,
			], [ '%d', '%d', '%s', ] ) ) ) {
				$this->error( "Could not insert into posts_and_posts table for ticket {$ticket_id} and series {$post_id}" );
				// Throw an exception to rollback the transaction.
				throw new DatabaseQueryException(
					"Could not insert into posts_and_posts table for ticket {$ticket_id} and series {$post_id}"
				);
			}

			if ( ! DB::insert(
				Capacities::table_name(), [
				'value'       => $ticket_data['ticket-ticket']['capacity'] ?? Capacities::VALUE_UNLIMITED,
				'mode'        => $ticket_data ['ticket-ticket']['capacity_type'] ?? Global_Stock::OWN_STOCK_MODE,
				'name'        => '',
				'description' => '',
			], [ '%d', '%s', '%s', '%s', ] ) ) {
				$this->error( "Could not insert into capacities table for ticket {$ticket_id}" );
				// Throw an exception to rollback the transaction.
				throw new DatabaseQueryException(
					"Could not insert into capacities table for ticket {$ticket_id}"
				);
			}
			$capacity_id = DB::last_insert_id();

			if ( ! DB::insert(
				Capacities_Relationships::table_name(), [
				'capacity_id' => $capacity_id,
				'object_id'   => $ticket_id,
			], [ '%d', '%d', ] ) ) {
				$this->error( "Could not insert into capacities_relationships table for ticket {$ticket_id} and capacity {$capacity_id}" );
				// Throw an exception to rollback the transaction.
				throw new DatabaseQueryException(
					"Could not insert into capacities_relationships table for ticket {$ticket_id} and capacity {$capacity_id}"
				);
			}
		} );

		$this->debug( "Added Series Pass custom tables data for Ticket {$ticket_id} and Series {$post_id}" );

		return true;
	}
}