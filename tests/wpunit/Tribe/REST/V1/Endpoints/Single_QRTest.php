<?php

namespace Tribe\Tickets\REST\V1\Endpoints;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Tribe\Tickets\Test\Factories\QR;

require_once codecept_data_dir( 'classes/Dummy_Endpoint.php' );

class Single_QRTest extends \Codeception\TestCase\WPRestApiTestCase {
	/**
	 * @var \Tribe__REST__Messages_Interface
	 */
	protected $messages;

	/**
	 * @var \Tribe__Tickets__REST__Interfaces__Post_Repository
	 */
	protected $repository;

	/**
	 * @var \Tribe__Validator__Interface
	 */
	protected $validator;


	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->qr = new QR();
		$this->messages = new \Tribe__Tickets__REST__V1__Messages();

	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( 'Dummy_Endpoint', $sut );

	}

	/**
	 * @return Endpoint
	 */
	private function make_instance() {
		$messages = $this->messages instanceof ObjectProphecy ? $this->messages->reveal() : $this->messages;

		return new \Dummy_Endpoint( $this->messages );
	}

	/**
	 * @test
	 * it should return a WP_Error if user cannot access requested ticket
	 */
	public function it_should_return_a_wp_error_if_user_cannot_access_requested_ticket() {
		$request = new \WP_REST_Request( 'GET', '' );
		$request->set_param( 'id', $this->factory()->qr->create( [ 'post_status' => 'draft' ] ) );

		$sut = $this->make_instance();
		$response = $sut->get_error( $request );

		$this->assertErrorResponse( 'ticket-not-accessible', $response, 403 );
	}

	/**
	 * @test
	 * it should return ticket data if ticket accessible
	 */
	public function it_should_return_ticket_data_if_ticket_accessible() {
		$request = new \WP_REST_Request( 'GET', '' );
		$id = $this->factory()->qr->create();
		$request->set_param( 'id', $id );

		$this->repository = $this->prophesize( \Tribe__Tickets__REST__V1__Post_Repository::class );
		$this->repository->get_qr_data( $id, Argument::type( 'string' ) )->willReturn( [ 'some' => 'data' ] );

		$sut = $this->make_instance();
		$response = $sut->get( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( [ 'some' => 'data' ], $response->get_data() );
	}

	/**
	 * Test swaggerize_args
	 */
	public function test_swaggerize_args() {
		$sut = $this->make_instance();

		$this->assertEmpty( $sut->swaggerize_args( [] ) );
		$args = [
			'id' => [
				'in'                => 'path',
				'swagger_type'      => 'integer',
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_ticket_id' ]
			],
		];

		$expected = [
			[
				'name'        => 'id',
				'in'          => 'path',
				'type'        => 'integer',
				'description' => 'No description',
				'required'    => true,
				'default'     => 'foo',
			],
		];

		$this->assertEqualSets( $expected, $sut->swaggerize_args( $args, [ 'description' => 'No description', 'default' => 'foo' ] ) );
	}

}
