<?php
/**
 * Tests for the Attendee_Repository_Disabled null-object class.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\Repositories;

use Codeception\TestCase\WPTestCase;

/**
 * Class Attendee_Repository_Disabled_Test
 *
 * @since TBD
 */
class Attendee_Repository_Disabled_Test extends WPTestCase {

	/**
	 * The repository instance.
	 *
	 * @var Attendee_Repository_Disabled
	 */
	private $repository;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->repository = new Attendee_Repository_Disabled();
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

	/**
	 * @test
	 */
	public function test_implements_attendee_privacy_handler_interface(): void {
		$this->assertInstanceOf(
			\TEC\Tickets\RSVP\Contracts\Attendee_Privacy_Handler::class,
			$this->repository
		);
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_returns_empty_array(): void {
		$result = $this->repository->get_attendees_by_email( 'test@example.com', 1, 10 );

		$this->assertSame( [ 'posts' => [], 'has_more' => false ], $result );
	}

	/**
	 * @test
	 */
	public function test_delete_attendee_returns_failure(): void {
		$result = $this->repository->delete_attendee( 123 );

		$this->assertSame( [ 'success' => false, 'event_id' => null ], $result );
	}

	/**
	 * @test
	 */
	public function test_get_ticket_id_returns_zero(): void {
		$this->assertSame( 0, $this->repository->get_ticket_id( 123 ) );
	}
}
