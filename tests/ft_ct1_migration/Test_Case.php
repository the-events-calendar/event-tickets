<?php

namespace TEC\Tickets\Tests\FT_CT1_Migration;

use Codeception\Test\Unit;
use tad\WPBrowser\Module\WPLoader\FactoryStore;
use TEC\Events\Custom_Tables\V1\Migration\Events;
use TEC\Events\Custom_Tables\V1\Migration\Process_Worker;
use TEC\Events\Custom_Tables\V1\Migration\Reports\Event_Report;
use TEC\Events\Custom_Tables\V1\Migration\State;

class FT_CT1_Migration_Test_Case extends Unit {
	protected static $factory;
	protected static $hooks_saved = [];
	protected $backupGlobals = false;

	/**
	 * @var array<Event_Report>
	 */
	private array $reports = [];

	/**
	 * @var array<string,string>
	 */
	private $strategy_to_short_form_map = [
		'tec-single-event-strategy'    => 'single',
		'tec-ecp-single-rule-strategy' => 'recurring-single-rule',
		'tec-ecp-multi-rule-strategy'  => 'recurring-multi-rule',
	];

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

		if ( $wp_object_cache instanceof \WP_Object_Cache ) {
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

	protected function run_migration( int $count = 100 ): void {
		$events = tribe( Events::class );
		$state  = tribe( State::class );
		$worker = new Process_Worker( $events, $state );
		foreach ( $events->get_ids_to_process( $count ) as $id ) {
			$this->reports[] = $worker->migrate_event( $id, false );
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

	protected function assert_migration_success(): void {
		[ , $failures ] = $this->split_reports_by_status();

		$this->assertCount( 0,
			$failures,
			'Expected no failures, but got ' . count( $failures ) . ' failures.'
		);
	}

	protected function assert_migration_strategy_count( array $criteria ): void {
		$by_strategy = $this->split_reports_by_strategy();

		foreach ( $criteria as $key => $expected_count ) {
			$this->assertCount(
				$expected_count,
				$by_strategy[ $key ],
				"Expected $expected_count $key events migrated using the $key strategy, but got "
				. count( $by_strategy[ $key ] ) . '.'
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
			function ( array $carry, Event_Report $report ): array {
				$strategy           = reset( $report->strategies_applied );
				$mapped             = $this->strategy_to_short_form_map[ $strategy ];
				$carry[ $mapped ][] = $report;

				return $carry;
			},
			[ 'single' => [], 'recurring-single-rule' => [], 'recurring-multi-rule' => [] ]
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
}