<?php

namespace Tribe\Tickets\Commerce\Reports;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Reports\Orders;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

class OrdersTest extends WPTestCase {

	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * Instance of the class being tested.
	 *
	 * @var Orders
	 */
	protected $orders;


	/**
	 * @test
	 * @dataProvider event_title_data_provider
	 *
	 * Test the get_title() method to ensure it follows the correct format.
	 *
	 * @param string $eventTitle
	 */
	public function get_title_follows_format( string $event_title, int $event_id ) {
		$event_id = $this->create_event_with_tickets_and_attendees( $event_title, 10 );

		$this->orders = new Orders();
		$expected = "Orders for: {$event_title} [#{$event_id}]";
		$actual = $this->orders->get_title( $event_id );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Data provider for different event titles.
	 */
	public function event_title_data_provider(): \Generator {
		yield 'Empty Title' => [ '', $this->create_event_with_tickets_and_attendees( '', 10 ) ];
		yield 'Event with an ID that does not exist' => [ 'Custom Title', 999 ];
		yield 'Event Title with Emoji' => [ 'Event Title with Emoji 😃', $this->create_event_with_tickets_and_attendees( 'Event Title with Emoji 😃', 10 ) ];
		yield 'Event Title with Chinese' => [ '活動標題中國', $this->create_event_with_tickets_and_attendees( '活動標題中國', 10 ) ];
	}

	/**
	 * Helper method to create an event with tickets and attendees.
	 *
	 * @param string $eventTitle
	 * @param int $numTickets
	 *
	 * @return int Event ID
	 */
	protected function create_event_with_tickets_and_attendees( string $event_title, int $num_tickets ): int {
		$eventFactory = new Event();
		$event_id = $eventFactory->create();

		$eventFactory->update_object( $event_id, [
			'post_title' => $event_title,
		] );

		$ticket_a_id = $this->create_tc_ticket( $event_id, $num_tickets );
		$this->create_many_attendees_for_ticket( $num_tickets, $ticket_a_id, $event_id );

		return $event_id;
	}
}