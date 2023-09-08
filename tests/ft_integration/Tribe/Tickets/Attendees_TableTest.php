<?php

namespace Tribe\Tickets;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Attendees_Table as Attendees_Table;

class Attendees_TableTest extends \Codeception\TestCase\WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use Attendee_Maker;

	private function get_attendee_data( array $attendees ): array {
		return array_reduce( $attendees, function ( array $carry, array $attendee ): array {
			foreach (
				[
					'ID',
					'purchaser_name',
					'purchaser_email',
					'ticket_name',
					'holder_name',
					'security_code'
				] as $key
			) {
				$value = $attendee[ $key ];

				if ( empty( $value ) ) {
					continue;
				}

				$carry[] = urlencode( $value );
				$carry[] = $value;
			}

			return $carry;
		}, [] );
	}

	public function display_provider(): Generator {
		yield 'no attendees for event' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );

				return [ $event_id, [ $event_id, $ticket_id ] ];
			}
		];

		yield 'single event with own attendees' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );
				$this->create_order( [ $ticket_id => 3 ] );

				return [ $event_id, [ $event_id, $ticket_id ] ];
			}
		];
	}

	/**
	 * @dataProvider display_provider
	 */
	public function test_display( Closure $fixture ): void {
		[ $post_id, $post_ids ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';

		ob_start();
		$table = new Attendees_Table();
		$table->prepare_items();
		$attendee_data = $this->get_attendee_data( $table->items );
		$table->display();
		$html = ob_get_clean();

		// Stabilize snapshots.
		$html = str_replace( $post_ids, 'POST_ID', $html );
		$html = str_replace( $attendee_data, 'ATTENDEE_DATA', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
