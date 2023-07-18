<?php

namespace TEC\Tickets\Tests\FT_CT1_Migration;

use Codeception\Test\Unit;
use tad\WPBrowser\Module\WPLoader\FactoryStore;
use TEC\Events\Custom_Tables\V1\Migration\Events;
use TEC\Events\Custom_Tables\V1\Migration\Process_Worker;
use TEC\Events\Custom_Tables\V1\Migration\Reports\Event_Report;
use TEC\Events\Custom_Tables\V1\Migration\State;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use WP_Object_Cache;

class FT_CT1_Migration_Test_Case extends Unit {
	protected static $factory;
	protected static $hooks_saved = [];
	protected $backupGlobals = false;

	/**
	 * @var array<Event_Report>
	 */
	private array $reports = [];

	public static function setUpBeforeClass() {
		// This will load all the factories.
		self::$factory = new FactoryStore();
		static::$factory->getThingFactory( 'post' );
	}

	protected static function factory() {
		return self::$factory;
	}

	private function backup_hooks() {
		$globals = [ 'wp_actions', 'wp_current_filter' ];
		foreach ( $globals as $key ) {
			self::$hooks_saved[ $key ] = $GLOBALS[ $key ];
		}
		self::$hooks_saved['wp_filter'] = array();
		foreach ( $GLOBALS['wp_filter'] as $hook_name => $hook_object ) {
			self::$hooks_saved['wp_filter'][ $hook_name ] = clone $hook_object;
		}
	}

	public function setUp() {
		$this->flush_cache();
		$this->set_user_to_admin();
		$this->filter_site_url();
		$this->backup_hooks();
	}

	public function tearDown() {
		$this->clean_globals();
	}

	private function set_user_to_admin() {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_users = get_users( [ 'role' => 'administrator', 'limit' => 1, 'fields' => 'ids' ] );
		if ( ! count( $admin_users ) ) {
			throw new RuntimeException( 'No administrator user found!' );
		}
		wp_set_current_user( reset( $admin_users ) );
	}

	private function filter_site_url() {
		$return_wordpress_test = static function () {
			return 'http://wordpress.test';
		};
		$actual_url            = home_url();
		add_filter( 'tribe_resource_url', static function ( $url_param ) use ( $actual_url ) {
			return str_replace( $actual_url, 'http://wordpress.test', $url_param );
		} );
		add_filter( 'home_url', $return_wordpress_test );
		add_filter( 'site_url', $return_wordpress_test );
	}

	private function clean_globals() {
		$_GET  = array();
		$_POST = array();
		$this->restore_hooks();
	}

	private function flush_cache() {
		global $wp_object_cache;
		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();

		if ( $wp_object_cache instanceof WP_Object_Cache ) {
			$wp_object_cache->flush();
		} elseif ( isset( $wp_object_cache->cache ) ) {
			$wp_object_cache->cache = [];
		}

		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset();
		}
		wp_cache_flush();
		wp_cache_add_global_groups( array(
			'users',
			'userlogins',
			'usermeta',
			'user_meta',
			'site-transient',
			'site-options',
			'site-lookup',
			'blog-lookup',
			'blog-details',
			'rss',
			'global-posts',
			'blog-id-cache'
		) );
		wp_cache_add_non_persistent_groups( array( 'comment', 'counts', 'plugins' ) );
	}

	private function restore_hooks() {
		$globals = [ 'wp_actions', 'wp_current_filter' ];
		foreach ( $globals as $key ) {
			if ( isset( self::$hooks_saved[ $key ] ) ) {
				$GLOBALS[ $key ] = self::$hooks_saved[ $key ];
			}
		}
		if ( isset( self::$hooks_saved['wp_filter'] ) ) {
			$GLOBALS['wp_filter'] = array();
			foreach ( self::$hooks_saved['wp_filter'] as $hook_name => $hook_object ) {
				$GLOBALS['wp_filter'][ $hook_name ] = clone $hook_object;
			}
		}
	}

	/**
	 * @after
	 */
	public function disconnect_shutdown_hooks() {
		global $wp_filter;
		unset( $wp_filter['shutdown'] );
	}

	/**
	 * @before
	 * @after
	 */
	public function reset_migration_data(): void {
		$this->reports = [];
	}

	protected function run_migration( $dry_run = false, int $count = 100 ): void {
		$events = tribe( Events::class );
		$state  = tribe( State::class );
		$worker = new Process_Worker( $events, $state );
		foreach ( $events->get_ids_to_process( $count ) as $id ) {
			$this->reports[] = $worker->migrate_event( $id, $dry_run );
		}
	}

	/**
	 * @return array{
	 *     success: array<Event_Report>,
	 *     failure: array<Event_Report>,
	 * }
	 */
	protected function split_reports_by_status() {
		return array_reduce(
			$this->reports,
			static function ( array $carry, Event_Report $report ): array {
				$key             = $report->status === 'success' ? 0 : 1;
				$carry[ $key ][] = $report;

				return $carry;
			},
			[ [], [] ]
		);
	}

	protected function assert_migration_succeeded(): void {
		[ , $failures ] = $this->split_reports_by_status();

		$this->assertCount( 0,
			$failures,
			'Expected no failures, but got ' . count( $failures ) . ' failures.'
			. PHP_EOL . 'Failures: ' . json_encode( $failures, JSON_PRETTY_PRINT )
		);
	}

	protected function assert_migration_failed(): void {
		[ $successes, $failures ] = $this->split_reports_by_status();

		$this->assertGreaterThan( 0,
			$failures,
			'Expected at least one failure, but got ' . count( $successes ) . ' successes'
		);
	}

	protected function assert_migration_strategy_count( array $criteria ): void {
		$by_strategy = $this->split_reports_by_strategy();

		foreach ( $criteria as $key => $expected_count ) {
			$this->assertCount(
				$expected_count,
				$by_strategy[ $key ] ?? [],
				"Expected $expected_count $key events migrated using the $key strategy, but got "
				. count( $by_strategy[ $key ] ?? [] ) . '.' . PHP_EOL .
				'Reports by strategy: ' . json_encode( $by_strategy, JSON_PRETTY_PRINT )
			);
		}
	}

	/**
	 * @return array{
	 *     single: array<Event_Report>,
	 *     recurring-single-rule: array<Event_Report>,
	 *     recurring-multi-rule: array<Event_Report>,
	 * }
	 */
	protected function split_reports_by_strategy(): array {
		return array_reduce(
			$this->reports,
			static function ( array $carry, Event_Report $report ): array {
				$strategy             = reset( $report->strategies_applied );
				$carry[ $strategy ][] = $report;

				return $carry;
			},
			[]
		);
	}

	protected function get_migration_report_for_event( int $id ): ?Event_Report {
		foreach ( $this->reports as $report ) {
			if ( (int) $report->source_event_post->ID === $id ) {
				return $report;
			}
		}

		return null;
	}

	protected function given_a_non_migrated_multi_rule_recurring_event(): \WP_Post {
		$recurrence = static function ( int $id ): array {
			return ( new Recurrence() )
				->with_start_date( get_post_meta( $id, '_EventStartDate', true ) )
				->with_end_date( get_post_meta( $id, '_EventEndDate', true ) )
				->with_daily_recurrence()
				->with_end_after( 50 )
				->with_weekly_recurrence()
				->with_end_after( 5 )
				->to_event_recurrence();
		};

		$timezone = new \DateTimeZone( 'Europe/Paris' );

		return $this->given_a_non_migrated_recurring_event(
			$recurrence,
			false,
			[
				// A Sunday.
				new \DateTimeImmutable( '2022-10-23 11:30:00', $timezone ),
				new \DateInterval( 'PT7H' ),
				$timezone
			]
		);
	}

}