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
	public function get_title_follows_format( string $eventTitle ) {
		$event_id = $this->create_event_with_tickets_and_attendees( $eventTitle, 10 );

		$this->orders = new Orders();
		$expected = "Orders for: {$eventTitle} [#{$event_id}]";
		$actual = $this->orders->get_title( $event_id );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Data provider for different event titles.
	 */
	public function event_title_data_provider(): \Generator {
		yield 'Empty Title' => [ '' ];
		yield 'Empty Event ID' => [ 'Custom Event Title' ];
		yield 'Event Title with Emoji' => [ 'Event Title with Emoji 😃' ];
		yield 'Event Title with Chinese' => [ '活動標題中國' ];
	}

	/**
	 * Helper method to create an event with tickets and attendees.
	 *
	 * @param string $eventTitle
	 * @param int $numTickets
	 *
	 * @return int Event ID
	 */
	protected function create_event_with_tickets_and_attendees( string $eventTitle, int $numTickets ): int {
		$eventFactory = new Event();
		$event_id = $eventFactory->create();

		$eventFactory->update_object( $event_id, [
			'post_title' => $eventTitle,
		] );

		$ticket_a_id = $this->create_tc_ticket( $event_id, $numTickets );
		$this->create_many_attendees_for_ticket( $numTickets, $ticket_a_id, $event_id );

		return $event_id;
	}
}