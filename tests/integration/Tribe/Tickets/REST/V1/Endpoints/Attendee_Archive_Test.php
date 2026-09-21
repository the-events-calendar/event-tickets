<?php

namespace Tribe\Tickets\REST\V1\Endpoints;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Faker\Generator;
use TEC\Tickets\Commerce\Attendee;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive as Archive;
use WP_REST_Request;

class Attendee_Archive_Test extends WPTestCase {
	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * @var Generator
	 */
	protected $faker;

	public function setUp(): void {
		parent::setUp();

		$this->faker = Factory::create();

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_list_when_every_attendee_can_be_formatted(): void {
		$attendee_count = $this->faker->numberBetween( 2, 8 );
		[ $event_id ]   = $this->create_event_with_attendees( $attendee_count );

		$data = $this->get_archive( $event_id, $attendee_count );

		$this->assertCount( $attendee_count, $data['attendees'] );
		$this->assertSame( range( 0, $attendee_count - 1 ), array_keys( $data['attendees'] ) );
		$this->assertStringContainsString( '"attendees":[', wp_json_encode( $data ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_list_when_an_attendee_cannot_be_formatted(): void {
		/* At least three, so the unformattable attendee sits between two good ones. */
		$attendee_count           = $this->faker->numberBetween( 3, 8 );
		[ $event_id, $attendees ] = $this->create_event_with_attendees( $attendee_count );

		/* Severing the ticket relation leaves the provider unable to hydrate the attendee. */
		delete_post_meta( $attendees[ intdiv( $attendee_count, 2 ) ], Attendee::$ticket_relation_meta_key );

		$data     = $this->get_archive( $event_id, $attendee_count );
		$expected = $attendee_count - 1;

		$this->assertCount( $expected, $data['attendees'] );
		$this->assertSame(
			range( 0, $expected - 1 ),
			array_keys( $data['attendees'] ),
			'A skipped attendee must not leave a gap in the keys.'
		);
		$this->assertStringContainsString( '"attendees":[', wp_json_encode( $data ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_list_when_a_filter_rekeys_the_attendees(): void {
		$attendee_count = $this->faker->numberBetween( 2, 8 );
		[ $event_id ]   = $this->create_event_with_attendees( $attendee_count );

		add_filter(
			'tec_tickets_rest_attendee_archive_data',
			static function ( $data ) {
				$data['attendees'] = array_combine(
					range( 100, 99 + count( $data['attendees'] ) ),
					$data['attendees']
				);

				return $data;
			}
		);

		$data = $this->get_archive( $event_id, $attendee_count );

		$this->assertSame( range( 0, $attendee_count - 1 ), array_keys( $data['attendees'] ) );
		$this->assertStringContainsString( '"attendees":[', wp_json_encode( $data ) );
	}

	/**
	 * @param int $attendee_count How many attendees to attach to the ticket.
	 *
	 * @return array{0: int, 1: array<int>} The event ID and the attendee IDs.
	 */
	private function create_event_with_attendees( int $attendee_count ): array {
		$event_id = tribe_events()->set_args(
			[
				'title'      => $this->faker->sentence( 3 ),
				'status'     => 'publish',
				'start_date' => '2030-07-14 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket_id = $this->create_tc_ticket( $event_id );

		return [ $event_id, $this->create_many_attendees_for_ticket( $attendee_count, $ticket_id, $event_id ) ];
	}

	/**
	 * @param int $event_id The event to fetch the attendees of.
	 * @param int $per_page How many attendees to request.
	 *
	 * @return array{rest_url: string, total: int, total_pages: int, attendees: array<int,array>} The archive response data.
	 */
	private function get_archive( int $event_id, int $per_page ): array {
		$request = new WP_REST_Request( 'GET', '' );
		$request->set_param( 'post_id', $event_id );
		/* $per_page covers every seeded attendee, so there is exactly one page to ask for. */
		$request->set_param( 'per_page', $per_page );
		$request->set_param( 'page', 1 );
		/*
		 * The attendees are created within the same second, so the default date sort ties and the
		 * skipped one can land last, where no gap forms and the test would pass with the bug present.
		 */
		$request->set_param( 'orderby', 'id' );
		$request->set_param( 'order', 'ASC' );

		return $this->make_instance()->get( $request )->get_data();
	}

	/**
	 * The sibling tests in this directory pass Prophecy doubles through the same factory; these
	 * exercise the real repository end to end, so they take the container's services instead.
	 *
	 * @return Archive The endpoint under test.
	 */
	private function make_instance(): Archive {
		return new Archive(
			tribe( 'tickets.rest-v1.messages' ),
			tribe( 'tickets.rest-v1.repository' ),
			tribe( 'tickets.rest-v1.validator' )
		);
	}
}
