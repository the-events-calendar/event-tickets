<?php

class TEC_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	public function test_code(): void {
		$this->assertTrue( is_plugin_active( 'event-tickets/event-tickets.php' ) );
		$this->assertTrue( is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) );
		$this->assertTrue( tribe()->getVar( 'ct1_fully_activated' ) );

		// Create 3 non-ticked Events;
		foreach ( range( 1, 3 ) as $k ) {
			$event = tribe_events()->set_args( [
				'title'      => 'Event ' . $k,
				'status'     => 'publish',
				'start_date' => "2022-10-06 $k am",
				'duration'   => 2 * HOUR_IN_SECONDS,
			] )->create();

			if ( ! $event instanceof WP_Post ) {
				throw new RuntimeException( "Event $k not created" );
			}
		}

		$args = [
			'page'                => 1,
			'per_page'            => 10,
			'start_date'          => '2022-10-06 00:00:00',
			'end_date'            => '2024-10-06 23:59:59',
			'paged'               => 1,
			'posts_per_page'      => 10,
			'post_status'         => 'publish',
			'has_rsvp_or_tickets' => true,
		];

		$events = tribe_get_events( $args );

		$this->assertCount( 0, $events );
	}
}
