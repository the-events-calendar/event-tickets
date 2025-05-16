<?php

namespace Tribe\Tickets\Admin;

use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Admin__Move_Tickets;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

class Move_Tickets_TemplateTest extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Attendee_Maker;

	public function get_move_tickets_data_provider(): Generator {
		yield 'no attendees' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [],
				'multiple_providers' => false,
			]
		];

		yield 'event has multiple providers' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [ 1, 2 ],
				'multiple_providers' => true,
			]
		];

		yield 'event has single provider' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [ 1 ],
				'multiple_providers' => false,
			]
		];

		yield 'event has single provider with multiple attendees' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [ 1, 2 ],
				'multiple_providers' => false,
			]
		];
	}

	/**
	 * @dataProvider get_move_tickets_data_provider
	 * @covers Tribe__Tickets__Admin__Move_Tickets::dialog
	 */
	public function test_move_tickets_html( array $template_vars ): void {
		ob_start();
		extract( $template_vars );
		include EVENT_TICKETS_DIR . '/src/admin-views/move-tickets.php';
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * Test that the posts returned by get_possible_matches() are sorted alphabetically by title.
	 *
	 * @covers Tribe__Tickets__Admin__Move_Tickets::get_possible_matches
	 */
	public function test_posts_are_sorted_alphabetically_by_title(): void {
		// Create posts with deliberately out-of-order titles
		$post_z = $this->factory->post->create([
			'post_type' => 'post',
			'post_title' => 'Zebra Event',
		]);

		$post_a = $this->factory->post->create([
			'post_type' => 'post',
			'post_title' => 'Aardvark Event',
		]);

		$post_m = $this->factory->post->create([
			'post_type' => 'post',
			'post_title' => 'Moose Event',
		]);

		// The post IDs should be in ascending order (post_z, post_a, post_m)
		// But the titles should sort as: Aardvark, Moose, Zebra

		// Use reflection to access the protected method
		$move_tickets = new Tribe__Tickets__Admin__Move_Tickets();
		$reflection = new \ReflectionClass($move_tickets);
		$method = $reflection->getMethod('get_possible_matches');
		$method->setAccessible(true);
		
		$posts = $method->invoke($move_tickets, [
			'post_type' => 'post',
			'search_terms' => 'Event',
		]);

		// Get the keys (post IDs) in the order they appear in the returned array
		$post_ids_in_order = array_keys($posts);

		// The first post should be "Aardvark Event" (post_a)
		$this->assertEquals($post_a, $post_ids_in_order[0], 'First post should be "Aardvark Event"');

		// The second post should be "Moose Event" (post_m)
		$this->assertEquals($post_m, $post_ids_in_order[1], 'Second post should be "Moose Event"');

		// The third post should be "Zebra Event" (post_z)
		$this->assertEquals($post_z, $post_ids_in_order[2], 'Third post should be "Zebra Event"');

		// Also check that the values (titles) are in alphabetical order
		$titles_in_order = array_values($posts);
		$this->assertStringContainsString('Aardvark', $titles_in_order[0], 'First title should contain "Aardvark"');
		$this->assertStringContainsString('Moose', $titles_in_order[1], 'Second title should contain "Moose"');
		$this->assertStringContainsString('Zebra', $titles_in_order[2], 'Third title should contain "Zebra"');
	}
}