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

}