<?php
/**
 * Controller for batch query optimization.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Batch_Query
 */

namespace TEC\Tickets\Batch_Query;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Events__Main as TEC;
use WP_Query;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Batch_Query
 */
class Controller extends Controller_Contract {

	/**
	 * The batch query manager instance.
	 *
	 * @since TBD
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		$this->container->singleton( Manager::class );
		$this->manager = $this->container->make( Manager::class );

		$this->add_hooks();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		$this->remove_hooks();
	}

	/**
	 * Add hooks for batch query optimization.
	 *
	 * @since TBD
	 */
	protected function add_hooks() {
		// Hook into Views V2 queries to collect event IDs.
		add_action( 'tribe_events_views_v2_view_before_template', [ $this, 'collect_event_ids' ], 5 );
		add_action( 'tribe_events_views_v2_view_after_template', [ $this, 'clear_batch' ], 15 );

		// Hook into event queries.
		add_action( 'pre_get_posts', [ $this, 'maybe_collect_from_query' ], 20 );

		// Filter ticket counts to use batch data.
		add_filter( 'tec_tickets_get_ticket_counts', [ $this, 'filter_ticket_counts' ], 10, 2 );
	}

	/**
	 * Remove hooks.
	 *
	 * @since TBD
	 */
	protected function remove_hooks() {
		remove_action( 'tribe_events_views_v2_view_before_template', [ $this, 'collect_event_ids' ], 5 );
		remove_action( 'tribe_events_views_v2_view_after_template', [ $this, 'clear_batch' ], 15 );
		remove_action( 'pre_get_posts', [ $this, 'maybe_collect_from_query' ], 20 );
		remove_filter( 'tec_tickets_get_ticket_counts', [ $this, 'filter_ticket_counts' ], 10, 2 );
	}

	/**
	 * Collect event IDs from Views V2.
	 *
	 * @since TBD
	 *
	 * @param \Tribe\Events\Views\V2\View $view The view instance.
	 */
	public function collect_event_ids( $view ) {
		if ( ! $view->get_context()->get( 'tickets_view', false ) ) {
			return;
		}

		$events = $view->get_context()->get( 'events', [] );
		if ( empty( $events ) ) {
			return;
		}

		$event_ids = wp_list_pluck( $events, 'ID' );
		$this->manager->add_events( $event_ids );
		$this->manager->preload();
	}

	/**
	 * Maybe collect event IDs from WP_Query.
	 *
	 * @since TBD
	 *
	 * @param WP_Query $query The query object.
	 */
	public function maybe_collect_from_query( $query ) {
		if ( ! $query->is_main_query() || ! $query->is_post_type_archive( TEC::POSTTYPE ) ) {
			return;
		}

		// Hook to collect IDs after query runs.
		add_action( 'loop_start', [ $this, 'collect_from_loop' ] );
	}

	/**
	 * Collect event IDs from the loop.
	 *
	 * @since TBD
	 *
	 * @param WP_Query $query The query object.
	 */
	public function collect_from_loop( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		// Remove this hook so it doesn't run multiple times.
		remove_action( 'loop_start', [ $this, 'collect_from_loop' ] );

		if ( ! empty( $query->posts ) ) {
			$event_ids = wp_list_pluck( $query->posts, 'ID' );
			$this->manager->add_events( $event_ids );
			$this->manager->preload();
		}
	}

	/**
	 * Clear the batch after rendering.
	 *
	 * @since TBD
	 */
	public function clear_batch() {
		$this->manager->clear();
	}

	/**
	 * Filter ticket counts to use batch data if available.
	 *
	 * @since TBD
	 *
	 * @param array $types   The ticket counts.
	 * @param int   $post_id The event ID.
	 *
	 * @return array
	 */
	public function filter_ticket_counts( $types, $post_id ) {
		$batch_counts = $this->manager->get_ticket_counts( $post_id );

		if ( null !== $batch_counts ) {
			return $batch_counts;
		}

		return $types;
	}
}
