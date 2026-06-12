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

	public function test_exclude_rsvp_tickets_from_tickets_view_data_link_count_returns_args_unchanged_when_context_is_not_get_my_tickets_link_data(): void {
		$post_id = static::factory()->post->create();
		$this->create_tc_rsvp_ticket( $post_id );

		$attendees = tribe( Attendees::class );
		$args      = [ 'by' => [ 'event' => $post_id ] ];

		$result = $attendees->exclude_rsvp_tickets_from_tickets_view_data_link_count( $args, $post_id, null, 'other_context' );

		$this->assertEquals( $args, $result );
		$this->assertArrayNotHasKey( 'meta_not_equals', $result['by'] );
	}

	public function test_exclude_rsvp_tickets_from_tickets_view_data_link_count_returns_args_unchanged_when_context_is_null(): void {
		$post_id = static::factory()->post->create();
		$this->create_tc_rsvp_ticket( $post_id );

		$attendees = tribe( Attendees::class );
		$args      = [ 'by' => [ 'event' => $post_id ] ];

		$result = $attendees->exclude_rsvp_tickets_from_tickets_view_data_link_count( $args, $post_id, null, null );

		$this->assertEquals( $args, $result );
		$this->assertArrayNotHasKey( 'meta_not_equals', $result['by'] );
	}

	public function test_exclude_rsvp_tickets_from_tickets_view_data_link_count_adds_meta_not_equals_filter_for_tc_provider(): void {
		$post_id = static::factory()->post->create();
		$this->create_tc_rsvp_ticket( $post_id );

		$attendees = tribe( Attendees::class );
		$args      = [ 'by' => [ 'event' => $post_id ] ];

		$result = $attendees->exclude_rsvp_tickets_from_tickets_view_data_link_count( $args, $post_id, null, 'get_my_tickets_link_data' );

		$this->assertArrayHasKey( 'meta_not_equals', $result['by'] );
		$this->assertEquals( [ '_type', Constants::TC_RSVP_TYPE ], $result['by']['meta_not_equals'] );
	}

	public function test_exclude_rsvp_tickets_from_tickets_view_data_link_count_preserves_existing_args(): void {
		$post_id = static::factory()->post->create();
		$this->create_tc_rsvp_ticket( $post_id );

		$attendees = tribe( Attendees::class );
		$args      = [
			'by' => [
				'event'  => $post_id,
				'status' => 'completed',
			],
		];

		$result = $attendees->exclude_rsvp_tickets_from_tickets_view_data_link_count( $args, $post_id, null, 'get_my_tickets_link_data' );

		$this->assertEquals( $post_id, $result['by']['event'] );
		$this->assertEquals( 'completed', $result['by']['status'] );
		$this->assertArrayHasKey( 'meta_not_equals', $result['by'] );
	}

	public function test_exclude_rsvp_tickets_from_tickets_view_data_link_count_works_with_user_id(): void {
		$post_id = static::factory()->post->create();
		$user_id = static::factory()->user->create();
		$this->create_tc_rsvp_ticket( $post_id );

		$attendees = tribe( Attendees::class );
		$args      = [ 'by' => [ 'event' => $post_id ] ];

		$result = $attendees->exclude_rsvp_tickets_from_tickets_view_data_link_count( $args, $post_id, $user_id, 'get_my_tickets_link_data' );

		$this->assertArrayHasKey( 'meta_not_equals', $result['by'] );
		$this->assertEquals( [ '_type', Constants::TC_RSVP_TYPE ], $result['by']['meta_not_equals'] );
	}

	public function test_exclude_rsvp_tickets_from_tickets_view_data_link_count_adds_filter_for_post_without_provider(): void {
		$post_id = static::factory()->post->create();
		// No ticket created, so no provider. An empty provider means TC.

		$attendees = tribe( Attendees::class );
		$args      = [ 'by' => [ 'event' => $post_id ] ];

		$result = $attendees->exclude_rsvp_tickets_from_tickets_view_data_link_count( $args, $post_id, null, 'get_my_tickets_link_data' );

		$this->assertArrayHasKey( 'meta_not_equals', $result['by'] );
		$this->assertEquals( [ '_type', Constants::TC_RSVP_TYPE ], $result['by']['meta_not_equals'] );
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

	public function test_modify_status_display_returns_label_unchanged_for_non_rsvp_item(): void {
		$attendees = tribe( Attendees::class );
		$item      = [ 'ticket_type' => 'default', 'attendee_id' => 123 ];

		$this->assertSame( 'ORIGINAL', $attendees->modify_status_display( 'ORIGINAL', $item ) );
	}

	public function test_modify_status_display_returns_label_unchanged_without_attendee_id(): void {
		$attendees = tribe( Attendees::class );
		$item      = [ 'ticket_type' => Constants::TC_RSVP_TYPE ];

		$this->assertSame( 'ORIGINAL', $attendees->modify_status_display( 'ORIGINAL', $item ) );
	}

	public function test_modify_status_display_shows_going_label(): void {
		$attendees = tribe( Attendees::class );
		$item      = $this->make_rsvp_item( 'yes' );

		$output = $attendees->modify_status_display( 'ORIGINAL', $item );

		$this->assertStringContainsString( 'Going', $output );
		$this->assertStringNotContainsString( 'Not Going', $output );
		$this->assertStringContainsString( 'tec-tickets__admin-table-attendees-order-status--going', $output );
	}

	public function test_modify_status_display_shows_not_going_label(): void {
		$attendees = tribe( Attendees::class );
		$item      = $this->make_rsvp_item( 'no' );

		$output = $attendees->modify_status_display( 'ORIGINAL', $item );

		$this->assertStringContainsString( 'Not Going', $output );
		$this->assertStringContainsString( 'tec-tickets__admin-table-attendees-order-status--not-going', $output );
	}

	public function test_modify_status_display_resolves_attendee_from_id_key(): void {
		$attendees = tribe( Attendees::class );
		$item      = $this->make_rsvp_item( 'yes', 'ID' );

		$output = $attendees->modify_status_display( 'ORIGINAL', $item );

		$this->assertStringContainsString( 'Going', $output );
	}

	public function test_modify_checkin_display_keeps_content_for_non_rsvp_item(): void {
		$attendees = tribe( Attendees::class );
		$item      = [ 'ticket_type' => 'default', 'attendee_id' => 123 ];

		$this->assertSame( 'CONTENT', $attendees->modify_checkin_display( 'CONTENT', $item ) );
	}

	public function test_modify_checkin_display_keeps_content_for_going_attendee(): void {
		$attendees = tribe( Attendees::class );
		$item      = $this->make_rsvp_item( 'yes' );

		$this->assertSame( 'CONTENT', $attendees->modify_checkin_display( 'CONTENT', $item ) );
	}

	public function test_modify_checkin_display_hides_content_for_not_going_attendee(): void {
		$attendees = tribe( Attendees::class );
		$item      = $this->make_rsvp_item( 'no' );

		$this->assertSame( '', $attendees->modify_checkin_display( 'CONTENT', $item ) );
	}

	public function test_modify_row_actions_keeps_checkin_for_going_attendee(): void {
		$attendees = tribe( Attendees::class );
		$item      = $this->make_rsvp_item( 'yes' );
		$actions   = [
			'tickets_checkin' => '<a class="tickets_checkin">Check In</a>',
			'delete'          => '<a class="delete">Delete</a>',
		];

		$result = $attendees->modify_row_actions( $actions, $item );

		$this->assertArrayHasKey( 'tickets_checkin', $result );
		$this->assertArrayHasKey( 'delete', $result );
	}

	public function test_modify_row_actions_removes_checkin_for_not_going_attendee(): void {
		$attendees = tribe( Attendees::class );
		$item      = $this->make_rsvp_item( 'no' );
		$actions   = [
			'tickets_checkin' => '<a class="tickets_checkin">Check In</a>',
			'delete'          => '<a class="delete">Delete</a>',
		];

		$result = $attendees->modify_row_actions( $actions, $item );

		$this->assertArrayNotHasKey( 'tickets_checkin', $result );
		$this->assertArrayHasKey( 'delete', $result );
	}

	public function test_modify_row_actions_leaves_actions_unchanged_for_non_rsvp_item(): void {
		$attendees = tribe( Attendees::class );
		$item      = [ 'ticket_type' => 'default', 'attendee_id' => 123 ];
		$actions   = [
			'tickets_checkin' => '<a class="tickets_checkin">Check In</a>',
			'delete'          => '<a class="delete">Delete</a>',
		];

		$this->assertSame( $actions, $attendees->modify_row_actions( $actions, $item ) );
	}
}
