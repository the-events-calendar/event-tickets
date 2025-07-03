<?php
/**
 * Controller for performance monitoring.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Performance
 */

namespace TEC\Tickets\Performance;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Performance
 */
class Controller extends Controller_Contract {

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		$this->container->singleton( Monitor::class );

		// Only add hooks if monitoring is enabled
		$monitor = $this->container->make( Monitor::class );
		if ( $monitor->is_enabled() ) {
			$this->add_hooks();
		}
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
	 * Add performance monitoring hooks.
	 *
	 * @since TBD
	 */
	protected function add_hooks() {
		// Monitor ticket count operations
		add_action( 'tec_tickets_before_get_ticket_counts', [ $this, 'start_ticket_counts_monitoring' ] );
		add_action( 'tec_tickets_after_get_ticket_counts', [ $this, 'stop_ticket_counts_monitoring' ] );

		// Monitor Views V2 rendering
		add_action( 'tribe_events_views_v2_view_before_template', [ $this, 'start_view_monitoring' ], 1 );
		add_action( 'tribe_events_views_v2_view_after_template', [ $this, 'stop_view_monitoring' ], 20 );

		// Monitor attendee operations
		add_action( 'tribe_tickets_before_get_attendees', [ $this, 'start_attendee_monitoring' ] );
		add_action( 'tribe_tickets_after_get_attendees', [ $this, 'stop_attendee_monitoring' ] );

		// Add performance data to admin bar
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_menu' ], 100 );

		// Add performance tracking filter
		add_filter( 'tribe_tickets_performance_tracking', [ $this, 'add_performance_data' ] );
	}

	/**
	 * Remove performance monitoring hooks.
	 *
	 * @since TBD
	 */
	protected function remove_hooks() {
		remove_action( 'tec_tickets_before_get_ticket_counts', [ $this, 'start_ticket_counts_monitoring' ] );
		remove_action( 'tec_tickets_after_get_ticket_counts', [ $this, 'stop_ticket_counts_monitoring' ] );
		remove_action( 'tribe_events_views_v2_view_before_template', [ $this, 'start_view_monitoring' ], 1 );
		remove_action( 'tribe_events_views_v2_view_after_template', [ $this, 'stop_view_monitoring' ], 20 );
		remove_action( 'tribe_tickets_before_get_attendees', [ $this, 'start_attendee_monitoring' ] );
		remove_action( 'tribe_tickets_after_get_attendees', [ $this, 'stop_attendee_monitoring' ] );
		remove_action( 'admin_bar_menu', [ $this, 'add_admin_bar_menu' ], 100 );
		remove_filter( 'tribe_tickets_performance_tracking', [ $this, 'add_performance_data' ] );
	}

	/**
	 * Start monitoring ticket counts.
	 *
	 * @since TBD
	 */
	public function start_ticket_counts_monitoring() {
		$monitor = $this->container->make( Monitor::class );
		$monitor->start( 'ticket_counts' );
	}

	/**
	 * Stop monitoring ticket counts.
	 *
	 * @since TBD
	 */
	public function stop_ticket_counts_monitoring() {
		$monitor = $this->container->make( Monitor::class );
		$monitor->stop( 'ticket_counts' );
	}

	/**
	 * Start monitoring view rendering.
	 *
	 * @since TBD
	 *
	 * @param \Tribe\Events\Views\V2\View $view The view instance.
	 */
	public function start_view_monitoring( $view ) {
		$monitor = $this->container->make( Monitor::class );
		$monitor->start( 'view_render_' . $view->get_slug() );
	}

	/**
	 * Stop monitoring view rendering.
	 *
	 * @since TBD
	 *
	 * @param \Tribe\Events\Views\V2\View $view The view instance.
	 */
	public function stop_view_monitoring( $view ) {
		$monitor = $this->container->make( Monitor::class );
		$metrics = $monitor->stop( 'view_render_' . $view->get_slug() );

		// Store metrics for admin bar display
		if ( ! empty( $metrics ) ) {
			$this->store_metrics( $metrics );
		}
	}

	/**
	 * Start monitoring attendee operations.
	 *
	 * @since TBD
	 */
	public function start_attendee_monitoring() {
		$monitor = $this->container->make( Monitor::class );
		$monitor->start( 'get_attendees' );
	}

	/**
	 * Stop monitoring attendee operations.
	 *
	 * @since TBD
	 */
	public function stop_attendee_monitoring() {
		$monitor = $this->container->make( Monitor::class );
		$monitor->stop( 'get_attendees' );
	}

	/**
	 * Add performance data to tracking array.
	 *
	 * @since TBD
	 *
	 * @param array $data Existing performance data.
	 *
	 * @return array
	 */
	public function add_performance_data( $data ) {
		global $wpdb;

		$data['query_count'] = $wpdb->num_queries;
		$data['cache_hits'] = tribe_cache()->get_hits();
		$data['load_time'] = timer_stop( 0, 3 );
		$data['memory_usage'] = size_format( memory_get_usage() );
		$data['peak_memory'] = size_format( memory_get_peak_usage() );

		return $data;
	}

	/**
	 * Add performance metrics to admin bar.
	 *
	 * @since TBD
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin bar instance.
	 */
	public function add_admin_bar_menu( $admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$metrics = $this->get_stored_metrics();
		if ( empty( $metrics ) ) {
			return;
		}

		$title = sprintf(
			'Tickets Perf: %dq / %sms',
			$metrics['queries'] ?? 0,
			$metrics['duration'] ?? 0
		);

		$admin_bar->add_node( [
			'id' => 'tribe-tickets-performance',
			'title' => $title,
			'meta' => [
				'title' => 'Event Tickets Performance Metrics',
			],
		] );

		// Add detailed metrics as sub-items
		if ( isset( $metrics['memory_used'] ) ) {
			$admin_bar->add_node( [
				'id' => 'tribe-tickets-performance-memory',
				'parent' => 'tribe-tickets-performance',
				'title' => 'Memory: ' . $metrics['memory_used'],
			] );
		}

		if ( isset( $metrics['cache_hits'] ) ) {
			$admin_bar->add_node( [
				'id' => 'tribe-tickets-performance-cache',
				'parent' => 'tribe-tickets-performance',
				'title' => 'Cache Hits: ' . $metrics['cache_hits'],
			] );
		}
	}

	/**
	 * Store metrics for later retrieval.
	 *
	 * @since TBD
	 *
	 * @param array $metrics The metrics to store.
	 */
	private function store_metrics( $metrics ) {
		static $stored_metrics = [];
		$stored_metrics[] = $metrics;
		
		// Store in a way that persists for the admin bar
		set_transient( 'tec_tickets_last_performance_metrics', $metrics, 60 );
	}

	/**
	 * Get stored metrics.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	private function get_stored_metrics() {
		return get_transient( 'tec_tickets_last_performance_metrics' ) ?: [];
	}
}