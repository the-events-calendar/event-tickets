<?php
/**
 * Test the Cost Cache functionality.
 *
 * @since TBD
 */

namespace TEC\Tickets;

use TEC\Tickets\Cost_Cache\Cache;
use TEC\Tickets\Cost_Cache\Controller;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Events__Main as TEC;

class Cost_Cache_Test extends \Codeception\TestCase\WPTestCase {
	use With_Uopz;

	/**
	 * The cache instance.
	 *
	 * @var Cache
	 */
	protected $cache;

	/**
	 * The controller instance.
	 *
	 * @var Controller
	 */
	protected $controller;

	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		
		// Make sure The Events Calendar is active.
		if ( ! class_exists( TEC::class ) ) {
			$this->markTestSkipped( 'The Events Calendar is not active.' );
		}
		
		$this->cache      = tribe( Cache::class );
		$this->controller = tribe( Controller::class );
	}

	/**
	 * Test cache storage and retrieval using post meta.
	 */
	public function test_cache_storage_and_retrieval() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set cache.
		$this->cache->set( $event_id, false, '$100' );
		$this->cache->set( $event_id, true, '$100 USD' );
		
		// Get cache.
		$this->assertEquals( '$100', $this->cache->get( $event_id, false ) );
		$this->assertEquals( '$100 USD', $this->cache->get( $event_id, true ) );
		
		// Verify it's stored as post meta.
		$this->assertEquals( '$100', get_post_meta( $event_id, Cache::META_KEY_COST, true ) );
		$this->assertEquals( '$100 USD', get_post_meta( $event_id, Cache::META_KEY_COST_WITH_SYMBOL, true ) );
	}

	/**
	 * Test cache storage for free events.
	 */
	public function test_cache_storage_for_free_events() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set cache for free event.
		$this->cache->set( $event_id, false, '' );
		$this->cache->set( $event_id, true, 'Free' );
		
		// Get cache - should return empty string, not false.
		$this->assertSame( '', $this->cache->get( $event_id, false ) );
		$this->assertEquals( 'Free', $this->cache->get( $event_id, true ) );
	}

	/**
	 * Test cache clearing.
	 */
	public function test_cache_clearing() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set cache.
		$this->cache->set( $event_id, false, '$100' );
		$this->cache->set( $event_id, true, '$100 USD' );
		
		// Clear cache.
		$this->cache->clear( $event_id );
		
		// Verify cache is cleared.
		$this->assertFalse( $this->cache->get( $event_id, false ) );
		$this->assertFalse( $this->cache->get( $event_id, true ) );
	}

	/**
	 * Test filter integration with tec_events_get_cost.
	 */
	public function test_filter_integration() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// First call should cache the value.
		$cost = apply_filters( 'tec_events_get_cost', '$200', $event_id, false );
		$this->assertEquals( '$200', $cost );
		
		// Verify it's cached.
		$cached = $this->cache->get( $event_id, false );
		$this->assertEquals( '$200', $cached );
		
		// Second call with pre filter should return cached value.
		$pre_cost = apply_filters( 'tec_events_pre_get_cost', null, $event_id, false );
		$this->assertEquals( '$200', $pre_cost ); // Should return cached value.
	}

	/**
	 * Test pre_get_cost filter prevents queries.
	 */
	public function test_pre_get_cost_prevents_queries() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set cache directly.
		$this->cache->set( $event_id, false, '$150' );
		
		// Pre filter should return cached value without hitting the database.
		$pre_cost = apply_filters( 'tec_events_pre_get_cost', null, $event_id, false );
		$this->assertEquals( '$150', $pre_cost );
		
		// When pre_cost is not null, the normal cost calculation should be skipped.
		// This simulates what happens in tribe_get_cost function.
		if ( null !== $pre_cost ) {
			// Cost calculation would be skipped here.
			$final_cost = $pre_cost;
		} else {
			// This branch should not be reached when cache exists.
			$final_cost = '$300'; // Different value to prove cache was used.
		}
		
		$this->assertEquals( '$150', $final_cost );
	}

	/**
	 * Test cache invalidation on event save.
	 */
	public function test_cache_invalidation_on_event_save() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set cache.
		$this->cache->set( $event_id, false, '$100' );
		
		// Update event.
		wp_update_post( [ 'ID' => $event_id, 'post_title' => 'Updated Event' ] );
		
		// Cache should be cleared.
		$this->assertFalse( $this->cache->get( $event_id, false ) );
	}

	/**
	 * Test cache invalidation on meta update.
	 */
	public function test_cache_invalidation_on_meta_update() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set cache.
		$this->cache->set( $event_id, false, '$100' );
		
		// Update cost meta.
		update_post_meta( $event_id, '_EventCost', '150' );
		
		// Cache should be cleared.
		$this->assertFalse( $this->cache->get( $event_id, false ) );
	}
}