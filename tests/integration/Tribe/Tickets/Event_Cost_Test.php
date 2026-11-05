<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__API as Events_API;

class Event_Cost_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @return array<string,array{0:string,1:string}> The cost the event is saved with, and the cost it keeps.
	 */
	public function event_cost_provider(): array {
		return [
			// The block editor sends the Event Price block's empty value when the event is saved.
			'no cost entered'      => [ '', '25' ],
			'a cost entered'       => [ '10', '10' ],
			'a free event entered' => [ 'Free', 'Free' ],
		];
	}

	/**
	 * @test
	 * @dataProvider event_cost_provider
	 */
	public function it_should_take_the_ticket_prices_only_when_no_cost_is_entered( string $saved_cost, string $expected ): void {
		$event = tribe_events()->set_args(
			[
				'title'      => 'Event with a ticket',
				'status'     => 'publish',
				'start_date' => '+1 week 10:00:00',
				'end_date'   => '+1 week 12:00:00',
			]
		)->create()->ID;
		$this->create_tc_ticket( $event, 25 );

		Events_API::update_event_cost( $event, [ $saved_cost ] );

		$this->assertSame( [ $expected ], get_post_meta( $event, '_EventCost' ) );
	}
}
