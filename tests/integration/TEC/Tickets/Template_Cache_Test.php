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
	 * Test template cache storage and retrieval.
	 */
	public function test_template_cache_storage_and_retrieval() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		$template_path = 'blocks/tickets/extra-price';
		$variables = [
			'ticket' => (object) [
				'ID' => 123,
				'price' => '50.00',
				'on_sale' => true,
				'regular_price' => '75.00',
			],
			'post_id' => $event_id,
		];
		$output = '<div class="price">$50.00</div>';
		
		// Set cache.
		$this->cache->set( $template_path, $variables, $event_id, $output );
		
		// Get cache with same variables.
		$cached = $this->cache->get( $template_path, $variables, $event_id );
		$this->assertEquals( $output, $cached );
		
		// Change a variable - should not get cache.
		$variables['ticket']->price = '60.00';
		$cached_different = $this->cache->get( $template_path, $variables, $event_id );
		$this->assertFalse( $cached_different );
	}

	/**
	 * Test template path normalization.
	 */
	public function test_template_path_normalization() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		$variables = [ 'ticket_id' => 123 ];
		$output = '<span>Test</span>';
		
		// Test various path formats.
		$paths = [
			'blocks/tickets/extra-price.php',
			'/blocks/tickets/extra-price',
			'tribe/tickets/blocks/tickets/extra-price',
			'views/blocks/tickets/extra-price.php',
		];
		
		// Set cache with first path.
		$this->cache->set( $paths[0], $variables, $event_id, $output );
		
		// All paths should retrieve the same cache.
		foreach ( $paths as $path ) {
			$cached = $this->cache->get( $path, $variables, $event_id );
			$this->assertEquals( $output, $cached, "Failed for path: $path" );
		}
	}

	/**
	 * Test clearing template cache.
	 */
	public function test_clear_template_cache() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Set multiple template caches.
		$templates = [
			'blocks/tickets/extra-price' => '<div>Price 1</div>',
			'v2/commerce/ticket/price' => '<div>Price 2</div>',
			'blocks/tickets/footer-total' => '<div>Total</div>',
		];
		
		$variables = [ 'ticket_id' => 123 ];
		
		foreach ( $templates as $path => $output ) {
			$this->cache->set( $path, $variables, $event_id, $output );
		}
		
		// Verify all are cached.
		foreach ( $templates as $path => $expected ) {
			$cached = $this->cache->get( $path, $variables, $event_id );
			$this->assertEquals( $expected, $cached );
		}
		
		// Clear cache for event.
		$this->cache->clear( $event_id );
		
		// Verify all are cleared.
		foreach ( $templates as $path => $output ) {
			$cached = $this->cache->get( $path, $variables, $event_id );
			$this->assertFalse( $cached );
		}
	}

	/**
	 * Test key variable extraction for different templates.
	 */
	public function test_key_variable_extraction() {
		$event_id = $this->factory->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		
		// Test ticket price template.
		$ticket_vars = [
			'ticket' => (object) [
				'ID' => 123,
				'price' => '50.00',
				'on_sale' => true,
				'regular_price' => '75.00',
			],
			'unrelated_var' => 'should_be_ignored',
		];
		
		$output1 = '<div>Price 1</div>';
		$this->cache->set( 'blocks/tickets/extra-price', $ticket_vars, $event_id, $output1 );
		
		// Adding unrelated variables shouldn't affect cache.
		$ticket_vars['another_var'] = 'also_ignored';
		$cached = $this->cache->get( 'blocks/tickets/extra-price', $ticket_vars, $event_id );
		$this->assertEquals( $output1, $cached );
		
		// But changing ticket price should.
		$ticket_vars['ticket']->price = '60.00';
		$cached = $this->cache->get( 'blocks/tickets/extra-price', $ticket_vars, $event_id );
		$this->assertFalse( $cached );
	}
}