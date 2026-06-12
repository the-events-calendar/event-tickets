<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Attendee_Maker;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as TC_Attendee_Maker;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Tickets_View as Tickets_View;

class Attendees_Test extends WPTestCase {
	use Ticket_Maker;
	use TC_Ticket_Maker;
	use Attendee_Maker;
	use TC_Attendee_Maker;
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
	 * @test
	 */
	public function it_should_not_conflate_rsvp_and_ticket_counts_in_my_tickets_link_data(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$user_id = static::factory()->user->create();

		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'user_id' => $user_id ] );
		$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'user_id' => $user_id ] );

		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$this->create_tc_rsvp_attendee( $rsvp_ticket_id, $post_id, [
			'meta_input' => [ Attendee::$user_relation_meta_key => $user_id ],
		] );

		$view = new Tickets_View();

		$this->assertSame(
			1,
			$view->count_rsvp_attendees( $post_id, $user_id ),
			'RSVP count should only include the RSVP attendee, not the real ticket attendees'
		);
		$this->assertSame(
			2,
			$view->count_ticket_attendees( $post_id, $user_id, 'get_my_tickets_link_data' ),
			'Ticket count should include real ticket attendees even in the my-tickets-link-data context'
		);

		$data = $view->get_my_tickets_link_data( $post_id, $user_id );

		$this->assertSame( 3, $data['total_count'] );
		$this->assertStringContainsString( '1 RSVP', $data['message'] );
		$this->assertStringContainsString( '2 Tickets', $data['message'] );
		$this->assertSame( 'View all', $data['link_label'] );
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

	public function test_deleting_the_last_rsvp_attendee_voids_the_order(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$order     = $this->create_order( [ $ticket_id => 1 ] );

		$attendee_ids = tec_tc_attendees()->by( 'post_parent', $order->ID )->order_by( 'ID', 'ASC' )->get_ids();

		$this->assertCount( 1, $attendee_ids );

		tribe( Attendee::class )->delete( $attendee_ids[0] );

		$this->assertSame( 'tec-tc-voided', get_post_status( $order->ID ) );
	}

	public function test_deleting_one_of_several_rsvp_attendees_does_not_void_the_order(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$order     = $this->create_order( [ $ticket_id => 3 ] );

		$attendee_ids = tec_tc_attendees()->by( 'post_parent', $order->ID )->order_by( 'ID', 'ASC' )->get_ids();

		$this->assertCount( 3, $attendee_ids );

		tribe( Attendee::class )->delete( $attendee_ids[0] );

		$this->assertSame( 'tec-tc-completed', get_post_status( $order->ID ) );
		$this->assertCount( 2, tec_tc_attendees()->by( 'post_parent', $order->ID )->get_ids() );
	}

	public function test_deleting_a_regular_ticket_attendee_does_not_void_its_order(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$order     = $this->create_order( [ $ticket_id => 1 ] );

		$attendee_ids = tec_tc_attendees()->by( 'post_parent', $order->ID )->get_ids();

		$this->assertCount( 1, $attendee_ids );

		tribe( Attendee::class )->delete( $attendee_ids[0] );

		$this->assertSame( 'tec-tc-completed', get_post_status( $order->ID ) );
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
