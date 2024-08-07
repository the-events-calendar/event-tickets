<?php

namespace TEC\Tickets\Seating;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class QR_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Attendee_Maker;

	protected string $controller_class = QR::class;

	public function test_inject_qr_data(): void {
		$non_asc_post = self::factory()->post->create();
		$ticket_1     = $this->create_tc_ticket( $non_asc_post, 10 );
		$attendee_1   = $this->create_attendee_for_ticket( $ticket_1, $non_asc_post );
		$asc_post     = self::factory()->post->create();
		update_post_meta( $asc_post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $asc_post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket_2   = $this->create_tc_ticket( $asc_post, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $ticket_2, Meta::META_KEY_ENABLED, true );
		[$attendee_2, $attendee_3] = $this->create_many_attendees_for_ticket(2, $ticket_2, $asc_post );
		update_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-23' );
		update_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		delete_post_meta( $attendee_3, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-24' );

		$controller = $this->make_controller();

		// Attendee data is not an array.
		$this->assertEquals( 'foo', $controller->inject_qr_data( 'foo', $attendee_1 ) );

		// Attendee data is an empty array.
		$this->assertEquals( [], $controller->inject_qr_data( [], $attendee_1 ) );

		$good_attendee_1_data = [
			'ticket_id' => $ticket_1,
		];

		// Attendee data is correct, but attendee ID is not numeric.
		$this->assertEquals( $good_attendee_1_data, $controller->inject_qr_data( $good_attendee_1_data, 'foo' ) );

		// Attendee data is correct, but attendee ID is a float (for reasons).
		$this->assertEquals( $good_attendee_1_data, $controller->inject_qr_data( $good_attendee_1_data, 1.0 ) );

		// Attendee data is correct, but the Attendee is for a non-ASC ticket.
		$this->assertEquals( $good_attendee_1_data, $controller->inject_qr_data( $good_attendee_1_data, $attendee_1 ) );

		$good_attendee_2_data = [
			'ticket_id' => $ticket_2,
		];

		// Attendee for an ASC ticket with assigned seating.
		$this->assertEquals( array_merge(
			$good_attendee_2_data,
			[
				'asc_ticket' => true,
				'seat_label' => 'A-23',
			]
		), $controller->inject_qr_data( $good_attendee_2_data, $attendee_2 ) );

		// Attendee for an ASC ticket with no assigned seating.
		$this->assertEquals( array_merge(
			$good_attendee_2_data,
			[
				'asc_ticket' => true,
				'seat_label' => '',
			]
		), $controller->inject_qr_data( $good_attendee_2_data, $attendee_3 ) );
	}
}