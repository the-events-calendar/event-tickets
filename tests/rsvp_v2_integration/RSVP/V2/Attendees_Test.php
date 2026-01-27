<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Attendee_Maker;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;

class Attendees_Test extends WPTestCase {
	use Ticket_Maker;
	use TC_Ticket_Maker;
	use Attendee_Maker;
	use Order_Maker;

	public static function get_rsvp_attendees_data_provider(): array {
		return [
			'no attendees' => [
				function () {
					$post_id = static::factory()->post->create();

					return [ $post_id, null, [], [] ];
				},
			],

			'one going attendee' => [
				function () {
					$post_id      = static::factory()->post->create();
					$ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
					$order_id     = $this->create_order( [ $ticket_id => 1 ] )->ID;
					$repository   = tribe( 'tickets.attendee-repository.rsvp' );
					$attendee_ids = $repository->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();

					return [ $post_id, $ticket_id, [ $order_id => $attendee_ids ], $attendee_ids ];
				},
			],

			'three going attendees' => [
				function () {
					$post_id      = static::factory()->post->create();
					$ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
					$order_id     = $this->create_order( [ $ticket_id => 3 ] )->ID;
					$repository   = tribe( 'tickets.attendee-repository.rsvp' );
					$attendee_ids = $repository->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();

					return [ $post_id, $ticket_id, [ $order_id => $attendee_ids ], $attendee_ids ];
				},
			],

			'two going, two not going attendees' => [
				function () {
					$post_id                 = static::factory()->post->create();
					$ticket_id               = $this->create_tc_rsvp_ticket( $post_id );
					$not_going_order_id      = $this->create_order( [ $ticket_id => 2 ] )->ID;
					$not_going_attendees_ids = tribe( 'tickets.attendee-repository.rsvp' )
						->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();
					foreach ( $not_going_attendees_ids as $not_going_attendees_id ) {
						update_post_meta( $not_going_attendees_id, Constants::RSVP_STATUS_META_KEY, 'no' );
					}
					$going_order_id = $this->create_order( [ $ticket_id => 2 ] )->ID;
					// This will include going and not going attendees.
					$attendees_ids       = tribe( 'tickets.attendee-repository.rsvp' )
						->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();
					$going_attendees_ids = array_values( array_diff( $attendees_ids, $not_going_attendees_ids ) );

					return [
						$post_id,
						$ticket_id,
						[ $going_order_id => $going_attendees_ids, $not_going_order_id => $not_going_attendees_ids ],
						$attendees_ids,
					];
				},
			],

			'three not going attendees' => [
				function () {
					$post_id       = static::factory()->post->create();
					$ticket_id     = $this->create_tc_rsvp_ticket( $post_id );
					$order_id      = $this->create_order( [ $ticket_id => 3 ] )->ID;
					$attendees_ids = tribe( 'tickets.attendee-repository.rsvp' )
						->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();
					foreach ( $attendees_ids as $not_going_attendees_id ) {
						update_post_meta( $not_going_attendees_id, Constants::RSVP_STATUS_META_KEY, 'no' );
					}

					return [
						$post_id,
						$ticket_id,
						[ $order_id => $attendees_ids ],
						$attendees_ids,
					];
				},
			]
		];
	}

	/**
	 * @dataProvider get_rsvp_attendees_data_provider
	 */
	public function test_get_rsvp_attendees_by_id_with_ticket_id( Closure $fixture ): void {
		[ , $ticket_id, , $expected_attendees_ids ] = Closure::bind( $fixture, $this, self::class )();

		$attendees = tribe( Attendees::class );

		$attendees_ids = $attendees->get_rsvp_attendees_by_id( null, $ticket_id );

		$this->assertEquals( $expected_attendees_ids, array_column( $attendees_ids, 'ID' ) );
	}

	/**
	 * @dataProvider get_rsvp_attendees_data_provider
	 */
	public function test_get_rsvp_attendees_by_id_with_post_id( Closure $fixture ): void {
		[ $post_id, , , $expected_attendees_ids ] = Closure::bind( $fixture, $this, self::class )();

		$attendees = tribe( Attendees::class );

		$attendees_ids = $attendees->get_rsvp_attendees_by_id( null, $post_id );

		$this->assertEquals( $expected_attendees_ids, array_column( $attendees_ids, 'ID' ) );
	}

	/**
	 * @dataProvider get_rsvp_attendees_data_provider
	 */
	public function test_get_rsvp_attendees_by_id_with_order_id( Closure $fixture ): void {
		[ $post_id, , $order_ids ] = Closure::bind( $fixture, $this, self::class )();

		$attendees = tribe( Attendees::class );

		foreach ( $order_ids as $order_id => $expected_attendees_ids ) {
			$attendees_ids = $attendees->get_rsvp_attendees_by_id( null, $order_id );
			$this->assertEquals( $expected_attendees_ids, array_column( $attendees_ids, 'ID' ) );
		}
	}
}
