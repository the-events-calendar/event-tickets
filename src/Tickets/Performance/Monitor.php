<?php
/**
 * Performance monitoring for Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Performance
 */

namespace TEC\Tickets\Performance;

/**
 * Class Monitor
 *
 * @since TBD
 *
 * @package TEC\Tickets\Performance
 */
class Monitor {

	/**
	 * Start time for performance tracking.
	 *
	 * @since TBD
	 *
	 * @var float
	 */
	private $start_time;

	/**
	 * Start query count.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private $start_queries;

	/**
	 * Performance data collected.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Whether monitoring is enabled.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private $enabled = false;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->enabled = defined( 'TEC_TICKETS_PERFORMANCE_MONITORING' ) && TEC_TICKETS_PERFORMANCE_MONITORING;
	}

	/**
	 * Start monitoring.
	 *
	 * @since TBD
	 *
	 * @param string $operation The operation being monitored.
	 */
	public function start( $operation = 'default' ) {
		if ( ! $this->enabled ) {
			return;
		}

		$this->start_time = microtime( true );
		$this->start_queries = $this->get_query_count();

		$this->data[ $operation ] = [
			'start_time' => $this->start_time,
			'start_queries' => $this->start_queries,
			'start_memory' => memory_get_usage(),
		];
	}

	/**
	 * Stop monitoring and record metrics.
	 *
	 * @since TBD
	 *
	 * @param string $operation The operation being monitored.
	 *
	 * @return array The performance metrics.
	 */
	public function stop( $operation = 'default' ) {
		if ( ! $this->enabled || ! isset( $this->data[ $operation ] ) ) {
			return [];
		}

		$end_time = microtime( true );
		$end_queries = $this->get_query_count();
		$end_memory = memory_get_usage();

		$metrics = [
			'operation' => $operation,
			'duration' => round( ( $end_time - $this->data[ $operation ]['start_time'] ) * 1000, 2 ), // milliseconds
			'queries' => $end_queries - $this->data[ $operation ]['start_queries'],
			'memory_used' => $this->format_bytes( $end_memory - $this->data[ $operation ]['start_memory'] ),
			'peak_memory' => $this->format_bytes( memory_get_peak_usage() ),
			'cache_hits' => $this->get_cache_hits(),
			'timestamp' => current_time( 'mysql' ),
		];

		// Log to error_log if debug is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[Event Tickets Performance] %s: %sms, %d queries, %s memory',
				$metrics['operation'],
				$metrics['duration'],
				$metrics['queries'],
				$metrics['memory_used']
			) );
		}

		/**
		 * Fires after performance metrics are collected.
		 *
		 * @since TBD
		 *
		 * @param array  $metrics   The performance metrics.
		 * @param string $operation The operation that was monitored.
		 */
		do_action( 'tec_tickets_performance_metrics_collected', $metrics, $operation );

		unset( $this->data[ $operation ] );

		return $metrics;
	}

	/**
	 * Get current query count.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	private function get_query_count() {
		global $wpdb;
		return $wpdb->num_queries;
	}

	/**
	 * Get cache hit count.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	private function get_cache_hits() {
		$cache = tribe_cache();
		
		// This would need to be implemented in Tribe__Cache
		if ( method_exists( $cache, 'get_hits' ) ) {
			return $cache->get_hits();
		}

		return 0;
	}

	/**
	 * Format bytes to human readable.
	 *
	 * @since TBD
	 *
	 * @param int $bytes The bytes to format.
	 *
	 * @return string
	 */
	private function format_bytes( $bytes ) {
		if ( $bytes < 0 ) {
			return '0 B';
		}

		$units = [ 'B', 'KB', 'MB', 'GB' ];
		$i = floor( log( $bytes, 1024 ) );
		
		return round( $bytes / pow( 1024, $i ), 2 ) . ' ' . $units[ $i ];
	}

	/**
	 * Get all collected metrics.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_metrics() {
		return $this->data;
	}

	/**
	 * Check if monitoring is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}
}