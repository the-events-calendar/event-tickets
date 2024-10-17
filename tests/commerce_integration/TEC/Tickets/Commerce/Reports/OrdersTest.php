<?php

namespace TEC\Tickets\Commerce\Reports;

use Codeception\TestCase\WPTestCase;
use Tribe\Events\Test\Factories\Event;

class OrdersTest extends WPTestCase {


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
	public function get_title_follows_format( string $event_title ) {
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
		yield 'Empty Title' => [ '' ];
		yield 'Event Title with Emoji' => [ 'Event Title with Emoji 😃' ];
		yield 'Event Title with Chinese' => [ '活動標題中國' ];
		yield 'Ordinary Event Title' => [ 'Event Title' ];
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


		return $event_id;
	}
}