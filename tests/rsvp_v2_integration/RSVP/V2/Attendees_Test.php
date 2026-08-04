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

	public function get_rsvp_attendees_data_provider(): array {
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
		[ , $ticket_id, , $expected_attendees_ids ] = $fixture();

		$attendees = tribe( Attendees::class );

		$attendees_ids = $attendees->get_rsvp_attendees_by_id( null, $ticket_id );

		$this->assertEquals( $expected_attendees_ids, array_column( $attendees_ids, 'ID' ) );
	}

	/**
	 * @dataProvider get_rsvp_attendees_data_provider
	 */
	public function test_get_rsvp_attendees_by_id_with_post_id( Closure $fixture ): void {
		[ $post_id, , , $expected_attendees_ids ] = $fixture();

		$attendees = tribe( Attendees::class );

		$attendees_ids = $attendees->get_rsvp_attendees_by_id( null, $post_id );

		$this->assertEquals( $expected_attendees_ids, array_column( $attendees_ids, 'ID' ) );
	}

	/**
	 * @dataProvider get_rsvp_attendees_data_provider
	 */
	public function test_get_rsvp_attendees_by_id_with_order_id( Closure $fixture ): void {
		[ $post_id, , $order_ids ] = $fixture();

		$attendees = tribe( Attendees::class );

		foreach ( $order_ids as $order_id => $expected_attendees_ids ) {
			$attendees_ids = $attendees->get_rsvp_attendees_by_id( null, $order_id );
			$this->assertEquals( $expected_attendees_ids, array_column( $attendees_ids, 'ID' ) );
		}
	}

	/**
	 * Each fixture returns [ $args, $post_id, $user_id, $context ]; the flag is whether the
	 * RSVP-exclusion filter should be added for that combination.
	 */
	public function exclude_rsvp_tickets_data_provider(): array {
		return [
			'unchanged for non-matching context' => [
				function () {
					$post_id = static::factory()->post->create();
					$this->create_tc_rsvp_ticket( $post_id );

					return [ [ 'by' => [ 'event' => $post_id ] ], $post_id, null, 'other_context' ];
				},
				false,
			],

			'unchanged for null context' => [
				function () {
					$post_id = static::factory()->post->create();
					$this->create_tc_rsvp_ticket( $post_id );

					return [ [ 'by' => [ 'event' => $post_id ] ], $post_id, null, null ];
				},
				false,
			],

			'adds filter for tc provider' => [
				function () {
					$post_id = static::factory()->post->create();
					$this->create_tc_rsvp_ticket( $post_id );

					return [ [ 'by' => [ 'event' => $post_id ] ], $post_id, null, 'get_my_tickets_link_data' ];
				},
				true,
			],

			'preserves existing args' => [
				function () {
					$post_id = static::factory()->post->create();
					$this->create_tc_rsvp_ticket( $post_id );

					return [
						[ 'by' => [ 'event' => $post_id, 'status' => 'completed' ] ],
						$post_id,
						null,
						'get_my_tickets_link_data',
					];
				},
				true,
			],

			'adds filter with user id' => [
				function () {
					$post_id = static::factory()->post->create();
					$user_id = static::factory()->user->create();
					$this->create_tc_rsvp_ticket( $post_id );

					return [ [ 'by' => [ 'event' => $post_id ] ], $post_id, $user_id, 'get_my_tickets_link_data' ];
				},
				true,
			],

			'adds filter for post without provider' => [
				function () {
					// No ticket created, so no provider. An empty provider means TC.
					$post_id = static::factory()->post->create();

					return [ [ 'by' => [ 'event' => $post_id ] ], $post_id, null, 'get_my_tickets_link_data' ];
				},
				true,
			],
		];
	}

	/**
	 * @dataProvider exclude_rsvp_tickets_data_provider
	 */
	public function test_exclude_rsvp_tickets_from_tickets_view_data_link_count( Closure $fixture, bool $adds_filter ): void {
		[ $args, $post_id, $user_id, $context ] = $fixture();

		$attendees = tribe( Attendees::class );

		$result = $attendees->exclude_rsvp_tickets_from_tickets_view_data_link_count( $args, $post_id, $user_id, $context );

		if ( ! $adds_filter ) {
			$this->assertEquals( $args, $result );
			$this->assertArrayNotHasKey( 'meta_not_equals', $result['by'] );

			return;
		}

		$this->assertArrayHasKey( 'meta_not_equals', $result['by'] );
		$this->assertEquals( [ '_type', Constants::TC_RSVP_TYPE ], $result['by']['meta_not_equals'] );

		// The original `by` args are preserved alongside the added filter.
		foreach ( $args['by'] as $key => $value ) {
			$this->assertEquals( $value, $result['by'][ $key ] );
		}
	}

	public function test_get_rsvp_attendees_by_id_bails_when_attendees_already_filtered(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$this->create_order( [ $ticket_id => 3 ] );

		$attendees          = tribe( Attendees::class );
		$pre_filtered_value = [ [ 'ID' => 999, 'test' => 'value' ] ];

		$result = $attendees->get_rsvp_attendees_by_id( $pre_filtered_value, $post_id );

		$this->assertSame( $pre_filtered_value, $result );
	}

	/**
	 * Creates a TC RSVP attendee and returns an attendees-table row item pointing at it.
	 *
	 * @param string $rsvp_status The RSVP status meta to stamp ('yes' or 'no').
	 * @param string $id_key      Which row key carries the attendee ID ('attendee_id' or 'ID').
	 *
	 * @return array<string,mixed> The row item.
	 */
	private function make_rsvp_item( string $rsvp_status, string $id_key = 'attendee_id' ): array {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$attendee_id = 'no' === $rsvp_status
			? $this->create_not_going_tc_rsvp_attendees( 1, $ticket_id, $post_id )[0]
			: $this->create_going_tc_rsvp_attendees( 1, $ticket_id, $post_id )[0];

		return [
			'ticket_type' => Constants::TC_RSVP_TYPE,
			$id_key       => $attendee_id,
		];
	}

	/**
	 * Each fixture returns an attendees-table row item for which the status label should be
	 * returned unchanged (non-RSVP rows, or RSVP rows that carry no attendee ID).
	 */
	public function modify_status_display_unchanged_data_provider(): array {
		return [
			'non-rsvp item' => [
				function () {
					return [ 'ticket_type' => 'default', 'attendee_id' => 123 ];
				},
			],

			'rsvp item without attendee id' => [
				function () {
					return [ 'ticket_type' => Constants::TC_RSVP_TYPE ];
				},
			],
		];
	}

	/**
	 * @dataProvider modify_status_display_unchanged_data_provider
	 */
	public function test_modify_status_display_returns_label_unchanged( Closure $fixture ): void {
		$item      = $fixture();
		$attendees = tribe( Attendees::class );

		$this->assertSame( 'ORIGINAL', $attendees->modify_status_display( 'ORIGINAL', $item ) );
	}

	/**
	 * Each fixture returns an RSVP row item; the test asserts the rendered label, its CSS class
	 * and, where relevant, a label that must NOT appear.
	 */
	public function modify_status_display_label_data_provider(): array {
		return [
			'going label' => [
				function () {
					return $this->make_rsvp_item( 'yes' );
				},
				'Going',
				'tec-tickets__admin-table-attendees-order-status--going',
				'Not Going',
			],

			'not going label' => [
				function () {
					return $this->make_rsvp_item( 'no' );
				},
				'Not Going',
				'tec-tickets__admin-table-attendees-order-status--not-going',
				null,
			],

			'resolves attendee from id key' => [
				function () {
					return $this->make_rsvp_item( 'yes', 'ID' );
				},
				'Going',
				'tec-tickets__admin-table-attendees-order-status--going',
				'Not Going',
			],
		];
	}

	/**
	 * @dataProvider modify_status_display_label_data_provider
	 */
	public function test_modify_status_display_shows_label( Closure $fixture, string $expected_label, string $expected_class, ?string $not_expected ): void {
		$item      = $fixture();
		$attendees = tribe( Attendees::class );

		$output = $attendees->modify_status_display( 'ORIGINAL', $item );

		$this->assertStringContainsString( $expected_label, $output );
		$this->assertStringContainsString( $expected_class, $output );

		if ( null !== $not_expected ) {
			$this->assertStringNotContainsString( $not_expected, $output );
		}
	}

	/**
	 * Each fixture returns a row item; the expected value is what the check-in cell should render
	 * for it (content is hidden only for "not going" RSVP attendees).
	 */
	public function modify_checkin_display_data_provider(): array {
		return [
			'non-rsvp item keeps content' => [
				function () {
					return [ 'ticket_type' => 'default', 'attendee_id' => 123 ];
				},
				'CONTENT',
			],

			'going attendee keeps content' => [
				function () {
					return $this->make_rsvp_item( 'yes' );
				},
				'CONTENT',
			],

			'not going attendee hides content' => [
				function () {
					return $this->make_rsvp_item( 'no' );
				},
				'',
			],
		];
	}

	/**
	 * @dataProvider modify_checkin_display_data_provider
	 */
	public function test_modify_checkin_display( Closure $fixture, string $expected ): void {
		$item      = $fixture();
		$attendees = tribe( Attendees::class );

		$this->assertSame( $expected, $attendees->modify_checkin_display( 'CONTENT', $item ) );
	}

	/**
	 * Each fixture returns a row item; the flag is whether the check-in row action should survive
	 * (it is removed only for "not going" RSVP attendees). The delete action is always kept.
	 */
	public function modify_row_actions_data_provider(): array {
		return [
			'going attendee keeps checkin' => [
				function () {
					return $this->make_rsvp_item( 'yes' );
				},
				true,
			],

			'not going attendee loses checkin' => [
				function () {
					return $this->make_rsvp_item( 'no' );
				},
				false,
			],

			'non-rsvp item unchanged' => [
				function () {
					return [ 'ticket_type' => 'default', 'attendee_id' => 123 ];
				},
				true,
			],
		];
	}

	/**
	 * @dataProvider modify_row_actions_data_provider
	 */
	public function test_modify_row_actions( Closure $fixture, bool $keeps_checkin ): void {
		$item      = $fixture();
		$attendees = tribe( Attendees::class );
		$actions   = [
			'tickets_checkin' => '<a class="tickets_checkin">Check In</a>',
			'delete'          => '<a class="delete">Delete</a>',
		];

		$result = $attendees->modify_row_actions( $actions, $item );

		$this->assertArrayHasKey( 'delete', $result );

		if ( $keeps_checkin ) {
			$this->assertArrayHasKey( 'tickets_checkin', $result );
		} else {
			$this->assertArrayNotHasKey( 'tickets_checkin', $result );
		}
	}
}
