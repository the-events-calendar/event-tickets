<?php
namespace Tribe\Tickets;

use Tribe__Tickets__Cache as Cache;

class CacheTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		Cache::reset_all();
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Cache::class, $sut );
	}

	/**
	 * @test
	 * it should return a list of all the posts without a ticket
	 */
	public function it_should_return_a_list_of_all_the_posts_without_a_ticket() {
		tribe_update_option( 'ticket-enabled-post-types', [ 'page', 'post' ] );
		$ids_without_tickets = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$ids_without_tickets = array_merge( $ids_without_tickets, $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] ) );

		$sut = $this->make_instance();
		$ids = $sut->posts_without_tickets();

		$this->assertEqualSets( $ids_without_tickets, $ids );
	}

	/**
	 * @return Cache
	 */
	private function make_instance() {

		return new Cache();
	}
}