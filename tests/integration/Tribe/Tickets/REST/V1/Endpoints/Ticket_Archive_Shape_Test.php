<?php

namespace Tribe\Tickets\REST\V1\Endpoints;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Faker\Generator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__REST__V1__Endpoints__Ticket_Archive as Archive;
use WP_REST_Request;

class Ticket_Archive_Shape_Test extends WPTestCase {
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
	public function it_should_return_a_list_when_every_ticket_can_be_formatted(): void {
		$ticket_count = $this->faker->numberBetween( 2, 6 );
		[ $event_id ] = $this->create_event_with_tickets( $ticket_count );

		$data = $this->get_archive( $event_id, $ticket_count );

		$this->assertCount( $ticket_count, $data['tickets'] );
		$this->assertSame( range( 0, $ticket_count - 1 ), array_keys( $data['tickets'] ) );
		$this->assertStringContainsString( '"tickets":[', wp_json_encode( $data ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_list_when_a_filter_rekeys_the_tickets(): void {
		$ticket_count = $this->faker->numberBetween( 2, 6 );
		[ $event_id ] = $this->create_event_with_tickets( $ticket_count );

		add_filter(
			'tec_tickets_rest_api_archive_results',
			static function ( $tickets ) {
				return array_combine( range( 100, 99 + count( $tickets ) ), $tickets );
			}
		);

		$data = $this->get_archive( $event_id, $ticket_count );

		$this->assertSame( range( 0, $ticket_count - 1 ), array_keys( $data['tickets'] ) );
		$this->assertStringContainsString( '"tickets":[', wp_json_encode( $data ) );
	}

	/**
	 * @param int $ticket_count How many tickets to attach to the event.
	 *
	 * @return array{0: int, 1: array<int>} The event ID and the ticket IDs.
	 */
	private function create_event_with_tickets( int $ticket_count ): array {
		$event_id = tribe_events()->set_args(
			[
				'title'      => $this->faker->sentence( 3 ),
				'status'     => 'publish',
				'start_date' => '2030-07-14 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$tickets = [];
		foreach ( range( 1, $ticket_count ) as $ignored ) {
			$tickets[] = $this->create_tc_ticket( $event_id );
		}

		return [ $event_id, $tickets ];
	}

	/**
	 * @param int $event_id The event to fetch the tickets of.
	 * @param int $per_page How many tickets to request.
	 *
	 * @return array{rest_url: string, total: int, total_pages: int, tickets: array<int,array>} The archive response data.
	 */
	private function get_archive( int $event_id, int $per_page ): array {
		$request = new WP_REST_Request( 'GET', '' );
		$request->set_param( 'include_post', $event_id );
		/* $per_page covers every seeded ticket, so there is exactly one page to ask for. */
		$request->set_param( 'per_page', $per_page );
		$request->set_param( 'page', 1 );

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
