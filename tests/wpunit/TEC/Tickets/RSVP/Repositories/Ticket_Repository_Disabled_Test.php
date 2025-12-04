<?php
/**
 * Tests for the Ticket_Repository_Disabled null-object class.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\Repositories;

use Codeception\TestCase\WPTestCase;

/**
 * Class Ticket_Repository_Disabled_Test
 *
 * @since TBD
 */
class Ticket_Repository_Disabled_Test extends WPTestCase {

	/**
	 * The repository instance.
	 *
	 * @var Ticket_Repository_Disabled
	 */
	private $repository;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->repository = new Ticket_Repository_Disabled();
	}

	/**
	 * @test
	 */
	public function test_all_returns_empty_array(): void {
		$this->assertSame( [], $this->repository->all() );
	}

	/**
	 * @test
	 */
	public function test_count_returns_zero(): void {
		$this->assertSame( 0, $this->repository->count() );
	}

	/**
	 * @test
	 */
	public function test_found_returns_zero(): void {
		$this->assertSame( 0, $this->repository->found() );
	}

	/**
	 * @test
	 */
	public function test_first_returns_null(): void {
		$this->assertNull( $this->repository->first() );
	}

	/**
	 * @test
	 */
	public function test_last_returns_null(): void {
		$this->assertNull( $this->repository->last() );
	}

	/**
	 * @test
	 */
	public function test_by_primary_key_returns_null(): void {
		$this->assertNull( $this->repository->by_primary_key( 1 ) );
	}

	/**
	 * @test
	 */
	public function test_by_returns_this(): void {
		$this->assertSame( $this->repository, $this->repository->by( 'key', 'value' ) );
	}

	/**
	 * @test
	 */
	public function test_where_returns_this(): void {
		$this->assertSame( $this->repository, $this->repository->where( 'key', 'value' ) );
	}

	/**
	 * @test
	 */
	public function test_get_ids_returns_empty_array(): void {
		$this->assertSame( [], $this->repository->get_ids() );
	}

	/**
	 * @test
	 */
	public function test_method_chaining_works(): void {
		$result = $this->repository
			->where( 'key', 'value' )
			->by( 'another', 'filter' )
			->page( 1 )
			->per_page( 10 )
			->order_by( 'date' )
			->all();

		$this->assertSame( [], $result );
	}
}
