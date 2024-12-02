<?php

namespace Tribe\Tickets\REST\V1\Endpoints;

use Tribe__Tickets__REST__V1__Endpoints__Ticket_Archive as Archive;
use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Clock_Mock;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Date_Utils as Dates;
use Prophecy\Prophecy\ObjectProphecy;

class Ticket_Archive_Test extends WPTestCase {
	use SnapshotAssertions;
	use With_Clock_Mock;
	use Ticket_Maker;

	/**
	 * @var \Tribe__REST__Messages_Interface
	 */
	protected $messages;

	/**
	 * @var \Tribe__Tickets__REST__V1__Post_Repository
	 */
	protected $repository;

	/**
	 * @var \Tribe__Tickets__REST__V1__Validator__Interface
	 */
	protected $validator;

	/**
	 * @return Archive
	 */
	private function make_instance() {
		$messages = $this->messages instanceof ObjectProphecy ? $this->messages->reveal() : $this->messages;
		$repository = $this->repository instanceof ObjectProphecy ? $this->repository->reveal() : $this->repository;
		$validator = $this->validator instanceof ObjectProphecy ? $this->validator->reveal() : $this->validator;

		return new Archive( $messages, $repository, $validator );
	}

	/**
	 * @test
	 */
	public function it_should_hide_password_protected_fields() {
		$request = new \WP_REST_Request( 'GET', '' );
		$request->set_param( 'per_page', 10 );
		$this->freeze_time( Dates::immutable( '2024-06-13 17:25:00' ) );
		$event_ids = [];
		foreach( range( 1, 5 ) as $i ) {
			$event_ids[] = tribe_events()->set_args(
				[
					'title'      => 'Test Event ' . $i,
					'status'     => 'publish',
					'start_date' => '2024-07-14 12:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				]
			)->create()->ID;
		}

		$ticket_ids = [];
		foreach( $event_ids as $event_id ) {
			$ticket_ids[] = $this->create_tc_ticket( $event_id );
		}

		$this->assertEquals( '2024-06-13 17:25:00', date( 'Y-m-d H:i:s' ) );

		wp_update_post( [
			'ID' => $event_ids[2],
			'post_password' => 'password',
		] );

		wp_update_post( [
			'ID' => $event_ids[4],
			'post_password' => 'password',
		] );

		$sut = $this->make_instance();
		$response = $sut->get( $request );

		$data = $response->get_data();
		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertCount( 5, $data['tickets'] );

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT );
		$json = str_replace(
			array_map( static fn( $id ) => '"id": ' . $id, $ticket_ids ),
			'"id": "{TICKET_ID}"',
			$json
		);
		$json = str_replace(
			array_map( static fn( $id ) => '"post_id": ' . $id, $event_ids ),
			'"post_id": "{EVENT_ID}"',
			$json
		);
		$json = str_replace(
			array_map( static fn( $id ) => '?id=' . $id, $event_ids ),
			'?id={EVENT_ID}',
			$json
		);
		$json = str_replace(
			array_map( static fn( $id ) => 'for ' . $id, $event_ids ),
			'for {EVENT_ID}',
			$json
		);
		$json = str_replace(
			array_map( static fn( $id ) => '&id=' . $id, $ticket_ids ),
			'&id={TICKET_ID}',
			$json
		);
		$json = str_replace(
			array_map( static fn( $id ) => '\/events\/' . $id, $event_ids ),
			'\/events\/{EVENT_ID}',
			$json
		);
		$json = str_replace(
			array_map( static fn( $id ) => '\/tickets\/' . $id, $ticket_ids ),
			'\/events\/{TICKET_ID}',
			$json
		);
		$this->assertMatchesJsonSnapshot( $json );
	}
}
