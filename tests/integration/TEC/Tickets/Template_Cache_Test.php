<?php
/**
 * Test the Template Cache functionality.
 *
 * @since TBD
 */

namespace TEC\Tickets;

use TEC\Tickets\Cost_Cache\Template_Cache;
use Tribe__Events__Main as TEC;

class Template_Cache_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * The template cache instance.
	 *
	 * @var Template_Cache
	 */
	protected $cache;

	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		
		// Make sure The Events Calendar is active.
		if ( ! class_exists( TEC::class ) ) {
			$this->markTestSkipped( 'The Events Calendar is not active.' );
		}
		
		$this->cache = tribe( Template_Cache::class );
	}

	/**
	 * Test simple template cache storage and retrieval.
	 */
	public function test_simple_template_cache() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		$hook_name = 'tickets/v2/day/event/cost';
		$html = '<div class="cost">$50.00</div>';
		
		// Set cache.
		$this->assertTrue( $this->cache->set( $event_id, $hook_name, $html ) );
		
		// Get cache.
		$cached = $this->cache->get( $event_id, $hook_name );
		$this->assertEquals( $html, $cached );
		
		// Different hook should not get cache.
		$cached_different = $this->cache->get( $event_id, 'tickets/v2/list/event/cost' );
		$this->assertFalse( $cached_different );
	}

	/**
	 * Test clearing template cache.
	 */
	public function test_clear_template_cache() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set multiple template caches.
		$templates = [
			'tickets/v2/day/event/cost' => '<div>Day Cost</div>',
			'tickets/v2/list/event/cost' => '<div>List Cost</div>',
			'tickets/v2/photo/event/cost' => '<div>Photo Cost</div>',
		];
		
		foreach ( $templates as $hook => $html ) {
			$this->cache->set( $event_id, $hook, $html );
		}
		
		// Verify all are cached.
		foreach ( $templates as $hook => $expected ) {
			$cached = $this->cache->get( $event_id, $hook );
			$this->assertEquals( $expected, $cached );
		}
		
		// Clear cache for event.
		$this->cache->clear( $event_id );
		
		// Verify all are cleared.
		foreach ( $templates as $hook => $html ) {
			$cached = $this->cache->get( $event_id, $hook );
			$this->assertFalse( $cached );
		}
	}

	/**
	 * Test that different events have separate caches.
	 */
	public function test_separate_event_caches() {
		$event1 = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		$event2 = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		$hook_name = 'tickets/v2/day/event/cost';
		$html1 = '<div>Event 1 Cost</div>';
		$html2 = '<div>Event 2 Cost</div>';
		
		// Set cache for both events.
		$this->cache->set( $event1, $hook_name, $html1 );
		$this->cache->set( $event2, $hook_name, $html2 );
		
		// Verify each has its own cache.
		$this->assertEquals( $html1, $this->cache->get( $event1, $hook_name ) );
		$this->assertEquals( $html2, $this->cache->get( $event2, $hook_name ) );
		
		// Clear one event's cache.
		$this->cache->clear( $event1 );
		
		// Verify only one is cleared.
		$this->assertFalse( $this->cache->get( $event1, $hook_name ) );
		$this->assertEquals( $html2, $this->cache->get( $event2, $hook_name ) );
	}
}