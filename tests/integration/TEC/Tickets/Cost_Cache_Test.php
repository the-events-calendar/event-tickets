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
	 * Test cache storage and retrieval.
	 */
	public function test_cache_storage_and_retrieval() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set cache.
		$this->cache->set( $event_id, false, '$100' );
		$this->cache->set( $event_id, true, '$100 USD' );
		
		// Get cache.
		$this->assertEquals( '$100', $this->cache->get( $event_id, false ) );
		$this->assertEquals( '$100 USD', $this->cache->get( $event_id, true ) );
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
	 * Test filter integration.
	 */
	public function test_filter_integration() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// First call should cache the value.
		$cost = apply_filters( 'tribe_get_cost', '$200', $event_id, false );
		$this->assertEquals( '$200', $cost );
		
		// Verify it's cached.
		$cached = $this->cache->get( $event_id, false );
		$this->assertEquals( '$200', $cached );
		
		// Second call should use cached value.
		$cost2 = apply_filters( 'tribe_get_cost', '$300', $event_id, false );
		$this->assertEquals( '$200', $cost2 ); // Should still be $200 from cache.
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