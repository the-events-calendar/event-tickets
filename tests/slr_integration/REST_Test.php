<?php

namespace TEC\Tickets\Seating;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class QR_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Attendee_Maker;

	protected string $controller_class = REST::class;

	public function test_inject_attendee_data(): void {
		$non_asc_post = self::factory()->post->create();
		$ticket_1     = $this->create_tc_ticket( $non_asc_post, 10 );
		$attendee_1   = $this->create_attendee_for_ticket( $ticket_1, $non_asc_post );
		$asc_post     = self::factory()->post->create();
		update_post_meta( $asc_post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $asc_post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket_2 = $this->create_tc_ticket( $asc_post, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $ticket_2, Meta::META_KEY_ENABLED, true );
		[ $attendee_2, $attendee_3 ] = $this->create_many_attendees_for_ticket( 2, $ticket_2, $asc_post );
		update_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-23' );
		update_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		delete_post_meta( $attendee_3, Meta::META_KEY_ATTENDEE_SEAT_LABEL );
		$controller = $this->make_controller();

		// Attendee data is not an array.
		$this->assertEquals( 'foo', $controller->inject_attendee_data( 'foo' ) );

		// Attendee data is an empty array.
		$this->assertEquals( [], $controller->inject_attendee_data( [] ) );

		$good_attendee_1_data = [
			'id'        => $attendee_1,
			'ticket_id' => $ticket_1,
		];

		// User is has not manage access: data is not modified.
		wp_set_current_user( 0 );
		$this->assertEquals(
			[ 'attendees' => [ $good_attendee_1_data ] ],
			$controller->inject_attendee_data( [ 'attendees' => [ $good_attendee_1_data ] ] )
		);

		// Set to a user that could edit them.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		// Attendee data is not an array.
		$this->assertEquals(
			[ 'attendees' => 'not-an-array' ],
			$controller->inject_attendee_data( [ 'attendees' => 'not-an-array' ] )
		);

		// Attendee data is an array, but attendees are not.
		$this->assertEquals(
			[ 'attendees' => [ 'not-an-array', 'another-thing' ] ],
			$controller->inject_attendee_data( [ 'attendees' => [ 'not-an-array', 'another-thing' ] ] )
		);

		// Attendee data is an array, but attendee is missing id.
		$this->assertEquals(
			[ 'attendees' => [ [ 'ticket_id' => $ticket_2 ] ] ],
			$controller->inject_attendee_data( [ 'attendees' => [ [ 'ticket_id' => $ticket_2 ] ] ] )
		);

		// Attendee data is an array, but attendee is missing ticket_id.
		$this->assertEquals(
			[ 'attendees' => [ [ 'id' => $attendee_2 ] ] ],
			$controller->inject_attendee_data( [ 'attendees' => [ [ 'id' => $attendee_2 ] ] ] )
		);

		// Attendee data is correct.
		$this->assertEquals(
			[
				'attendees' => [
					[ 'id' => $attendee_2, 'ticket_id' => $ticket_2, 'asc_ticket' => true, 'seat_label' => 'A-23' ],
					[ 'id' => $attendee_3, 'ticket_id' => $ticket_2, 'asc_ticket' => true, 'seat_label' => '' ]
				]
			],
			$controller->inject_attendee_data( [
				'attendees' => [
					[ 'id' => $attendee_2, 'ticket_id' => $ticket_2 ],
					[ 'id' => $attendee_3, 'ticket_id' => $ticket_2 ]
				]
			] )
		);
	}
}
