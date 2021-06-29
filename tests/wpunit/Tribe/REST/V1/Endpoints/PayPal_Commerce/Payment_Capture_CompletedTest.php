<?php

namespace Tribe\Tickets\REST\V1\Endpoints\PayPal_Commerce;

use Tribe__Documentation__Swagger__Provider_Interface as Documentation_Provider;
use Tribe__Tickets__REST__V1__Endpoints__Swagger_Documentation as Doc;
use Tribe__Tickets__REST__V1__Messages;
use Tribe__Tickets__REST__V1__Post_Repository;
use Tribe__Tickets__REST__V1__Validator__Base;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Payment_Capture_CompletedTest extends \Codeception\TestCase\WPTestCase {

	protected $version = '1.0.0';

	protected $event_file = 'PAYMENT.CAPTURE.COMPLETED.json';

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here

		// @todo Set up Tickets Commerce.
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

		$this->assertInstanceOf( Payment_Capture_Completed::class, $sut );
	}

	/**
	 * @test
	 * it should reject invalid event data
	 */
	public function it_should_reject_invalid_event_data() {
		// @todo Set up test event/ticket.
		// @todo Create temporary order.

		// Set invalid Event JSON.
		$event_json = json_encode( [
			'invalid' => 'event data',
		] );

		$sut = $this->make_instance();

		$request = new WP_REST_Request();
		$request->set_body( $event_json );

		$response = $sut->create( $request );

		// Confirm the response is as we expect.
		$this->assertInstanceOf( WP_Error::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertEquals( 403, $data['status'] );

		// Now confirm the payment was NOT marked completed.

		// @todo Confirm payment is NOT complete.
	}

	/**
	 * @test
	 * it should mark payment as completed
	 */
	public function it_should_mark_payment_as_completed() {
		// @todo Set up test event/ticket.
		// @todo Create temporary order.

		// Get Event JSON.
		$event_json = file_get_contents( codecept_data_dir( $this->event_file ) );

		$sut = $this->make_instance();

		$request = new WP_REST_Request();
		$request->set_body( $event_json );

		$response = $sut->create( $request );

		// Confirm the response is as we expect.
		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );

		// Now confirm the payment was marked completed.

		// @todo Confirm payment is complete.
	}

	/**
	 * @return Payment_Capture_Completed
	 */
	private function make_instance() {
		$messages = new Tribe__Tickets__REST__V1__Messages();

		return new Payment_Capture_Completed(
			$messages,
			new Tribe__Tickets__REST__V1__Post_Repository( $messages ),
			new Tribe__Tickets__REST__V1__Validator__Base()
		);
	}

}