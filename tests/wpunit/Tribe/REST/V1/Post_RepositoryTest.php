<?php

namespace Tribe\Tickets\REST\V1;

use Tribe\Tickets\Test\Testcases\Ticket_TestCase;
use Tribe__Tickets__REST__V1__Messages as Messages;
use Tribe__Tickets__REST__V1__Post_Repository as Post_Repository;

class Post_RepositoryTest extends Ticket_TestCase {

	/**
	 * @var \Tribe__REST__Messages_Interface
	 */
	protected $messages;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->messages = new Messages();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Post_Repository::class, $sut );
	}

	/**
	 * Should return an error if Event Tickets Plus is not loaded.
	 *
	 * @test
	 */
	public function should_return_error_if_etplus_not_loaded() {
		$sut = $this->make_instance();

		$data = $sut->get_attendee_data( 22131 );

		/** @var \WP_Error $data */
		$this->assertWPError( $data );
		$this->assertEquals( $this->messages->get_message( 'etplus-not-loaded' ), $data->get_error_message() );
	}

	/**
	 * @return Post_Repository
	 */
	private function make_instance() {
		return new Post_Repository( $this->messages );
	}
}