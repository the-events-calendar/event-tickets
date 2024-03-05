<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

class Attendee_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	use Commerce_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * Low-level registration of the Commerce provider. There is no need for a full-blown registration
	 * at this stage: having the module as active and as a valid provider is enough.
	 *
	 * @before
	 */
	public function activate_commerce_tickets(): void {
		add_filter( 'tribe_tickets_get_modules', static function ( array $modules ): array {
			$modules[ Commerce::class ] = 'Commerce';

			return $modules;
		} );
		// Regenerate the Tickets Data API to pick up the filtered providers.
		tribe()->singleton( 'tickets.data_api', new \Tribe__Tickets__Data_API() );
	}

	/**
	 * It should allow filtering attendees by event
	 *
	 * @test
	 */
	public function should_allow_filtering_attendees_by_post(): void {
		$post_1   = static::factory()->post->create();
		$post_2   = static::factory()->post->create();
		$post_3   = static::factory()->post->create();
		$ticket_1 = $this->create_tc_ticket( $post_1, 23 );
		$ticket_2 = $this->create_tc_ticket( $post_2, 23 );
		$ticket_3 = $this->create_tc_ticket( $post_3, 23 );
		[ $attendee_1, $attendee_2 ] = $this->create_many_attendees_for_ticket( 2, $ticket_1, $post_1 );
		[ $attendee_3, $attendee_4 ] = $this->create_many_attendees_for_ticket( 2, $ticket_2, $post_2 );
		[ $attendee_5, $attendee_6 ] = $this->create_many_attendees_for_ticket( 2, $ticket_3, $post_3 );

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $attendee_3, $attendee_4, $attendee_5, $attendee_6 ],
			tribe_attendees()->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2 ],
			tribe_attendees()->where( 'event', $post_1 )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event', $post_2 )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_5, $attendee_6 ],
			tribe_attendees()->where( 'event', $post_3 )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event', [ $post_1, $post_2 ] )->get_ids()
		);
	}

	/**
	 * It should allow filtering the post IDs used to fetch Attendees
	 *
	 * @test
	 */
	public function should_allow_filtering_the_post_i_ds_used_to_fetch_attendees(): void {
		$post_1   = static::factory()->post->create();
		$post_2   = static::factory()->post->create();
		$ticket_1 = $this->create_tc_ticket( $post_1, 23 );
		$ticket_2 = $this->create_tc_ticket( $post_2, 23 );
		[ $attendee_1, $attendee_2 ] = $this->create_many_attendees_for_ticket( 2, $ticket_1, $post_1 );
		[ $attendee_3, $attendee_4 ] = $this->create_many_attendees_for_ticket( 2, $ticket_2, $post_2 );

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2 ],
			tribe_attendees()->where( 'event', $post_1 )->get_ids()
		);

		add_filter( 'tec_tickets_attendees_filter_by_event', function ( $id ) use ( $post_2 ): array {
			return array_values( array_unique( [ ...(array) $id, $post_2 ] ) );
		} );

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event', $post_1 )->get_ids()
		);
	}

	/**
	 * It should allow filtering attendees by event not in set
	 *
	 * @test
	 */
	public function should_allow_filtering_attendees_by_event_not_in_set(): void {
		$post_1   = static::factory()->post->create();
		$post_2   = static::factory()->post->create();
		$post_3   = static::factory()->post->create();
		$ticket_1 = $this->create_tc_ticket( $post_1, 23 );
		$ticket_2 = $this->create_tc_ticket( $post_2, 23 );
		$ticket_3 = $this->create_tc_ticket( $post_3, 23 );
		[ $attendee_1, $attendee_2 ] = $this->create_many_attendees_for_ticket( 2, $ticket_1, $post_1 );
		[ $attendee_3, $attendee_4 ] = $this->create_many_attendees_for_ticket( 2, $ticket_2, $post_2 );
		[ $attendee_5, $attendee_6 ] = $this->create_many_attendees_for_ticket( 2, $ticket_3, $post_3 );

		$this->assertEqualSets(
			[],
			tribe_attendees()->where( 'event__not_in', [ $post_1, $post_2, $post_3 ] )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_3, $attendee_4, $attendee_5, $attendee_6 ],
			tribe_attendees()->where( 'event__not_in', $post_1 )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_5, $attendee_6 ],
			tribe_attendees()->where( 'event__not_in', [ $post_1, $post_2 ] )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event__not_in', [ $post_3 ] )->get_ids()
		);
	}

	/**
	 * It should allow filtering attendees by event not in set
	 *
	 * @test
	 */
	public function should_allow_filtering_attendees_by_event_not_in_set_with_multiple_events(): void {
		$post_1   = static::factory()->post->create();
		$post_2   = static::factory()->post->create();
		$post_3   = static::factory()->post->create();
		$ticket_1 = $this->create_tc_ticket( $post_1, 23 );
		$ticket_2 = $this->create_tc_ticket( $post_2, 23 );
		$ticket_3 = $this->create_tc_ticket( $post_3, 23 );
		[ $attendee_1, $attendee_2 ] = $this->create_many_attendees_for_ticket( 2, $ticket_1, $post_1 );
		[ $attendee_3, $attendee_4 ] = $this->create_many_attendees_for_ticket( 2, $ticket_2, $post_2 );
		[ $attendee_5, $attendee_6 ] = $this->create_many_attendees_for_ticket( 2, $ticket_3, $post_3 );

		add_filter( 'tec_tickets_attendees_filter_by_event_not_in', function ( $id ) use ( $post_2 ): array {
			return array_values( array_unique( [ ...(array) $id, $post_2 ] ) );
		} );

		$this->assertEqualSets(
			[],
			tribe_attendees()->where( 'event__not_in', [ $post_1, $post_2, $post_3 ] )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_5, $attendee_6 ],
			tribe_attendees()->where( 'event__not_in', $post_1 )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_5, $attendee_6 ],
			tribe_attendees()->where( 'event__not_in', [ $post_1, $post_2 ] )->get_ids()
		);

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2 ],
			tribe_attendees()->where( 'event__not_in', [ $post_3 ] )->get_ids()
		);
	}

	/**
	 * It should allow filtering attendees by ticket type
	 *
	 * @test
	 */
	public function should_allow_filtering_attendees_by_ticket_type(): void {
		// Create a first post with `default`, `vip-pass` and no ticket type tickets for it.
		$post_1          = static::factory()->post->create();
		$post_1_ticket_1 = $this->create_tc_ticket( $post_1, 23 );
		$this->assertEquals( 'default', get_post_meta( $post_1_ticket_1, '_type', true ) );
		$post_1_ticket_2 = $this->create_tc_ticket( $post_1, 23 );
		update_post_meta( $post_1_ticket_2, '_type', 'vip-pass' );
		$this->assertEquals( 'vip-pass', get_post_meta( $post_1_ticket_2, '_type', true ) );
		// Create a Ticket that will have no type, it should coalesce to `default`.
		$post_1_ticket_3 = $this->create_tc_ticket( $post_1, 23 );
		delete_post_meta( $post_1_ticket_3, '_type' );
		// Create a second post with `default`, `vip-pass` and no ticket type tickets for it.
		$post_2          = static::factory()->post->create();
		$post_2_ticket_1 = $this->create_tc_ticket( $post_2, 23 );
		$this->assertEquals( 'default', get_post_meta( $post_2_ticket_1, '_type', true ) );
		$post_2_ticket_2 = $this->create_tc_ticket( $post_2, 23 );
		update_post_meta( $post_2_ticket_2, '_type', 'vip-pass' );
		// Create a Ticket that will have no type, it should coalesce to `default`.
		$post_2_ticket_3 = $this->create_tc_ticket( $post_2, 23 );
		delete_post_meta( $post_2_ticket_3, '_type' );
		$this->assertEquals( 'vip-pass', get_post_meta( $post_2_ticket_2, '_type', true ) );
		// Register 2 Attendees on the `default` ticket for post 1.
		$post_1_ticket_1_attendee_1 = $this->create_attendee_for_ticket( $post_1_ticket_1, $post_1 );
		$post_1_ticket_1_attendee_2 = $this->create_attendee_for_ticket( $post_1_ticket_1, $post_1 );
		// Register 2 Attendees on the `vip-pass` ticket for post 1.
		$post_1_ticket_2_attendee_1 = $this->create_attendee_for_ticket( $post_1_ticket_2, $post_1 );
		$post_1_ticket_2_attendee_2 = $this->create_attendee_for_ticket( $post_1_ticket_2, $post_1 );
		// Register 2 Attendees on the no type ticket for post 1.
		$post_1_ticket_3_attendee_1 = $this->create_attendee_for_ticket( $post_1_ticket_3, $post_1 );
		$post_1_ticket_3_attendee_2 = $this->create_attendee_for_ticket( $post_1_ticket_3, $post_1 );
		// Register 2 Attendees on the `default` ticket for post 2.
		$post_2_ticket_1_attendee_1 = $this->create_attendee_for_ticket( $post_2_ticket_1, $post_2 );
		$post_2_ticket_1_attendee_2 = $this->create_attendee_for_ticket( $post_2_ticket_1, $post_2 );
		// Register 2 Attendees on the `vip-pass` ticket for post 2.
		$post_2_ticket_2_attendee_1 = $this->create_attendee_for_ticket( $post_2_ticket_2, $post_2 );
		$post_2_ticket_2_attendee_2 = $this->create_attendee_for_ticket( $post_2_ticket_2, $post_2 );
		// Register 2 Attendees on the no type ticket for post 2.
		$post_2_ticket_3_attendee_1 = $this->create_attendee_for_ticket( $post_2_ticket_3, $post_2 );
		$post_2_ticket_3_attendee_2 = $this->create_attendee_for_ticket( $post_2_ticket_3, $post_2 );

		// Baseline checks.
		$this->assertEquals( [], tribe_attendees()->where( 'ticket_type', [] )->get_ids() );
		$this->assertEquals( [], tribe_attendees()->where( 'ticket_type__not_in', [] )->get_ids() );
		$this->assertEqualSets(
			[
				$post_1_ticket_1_attendee_1,
				$post_1_ticket_1_attendee_2,
				$post_1_ticket_2_attendee_1,
				$post_1_ticket_2_attendee_2,
				$post_1_ticket_3_attendee_1,
				$post_1_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_1 )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_1_ticket_1_attendee_1,
				$post_1_ticket_1_attendee_2,
				$post_1_ticket_2_attendee_1,
				$post_1_ticket_2_attendee_2,
				$post_1_ticket_3_attendee_1,
				$post_1_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_1 )->where( 'ticket_type', [ 'default', 'vip-pass' ] )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_2_ticket_1_attendee_1,
				$post_2_ticket_1_attendee_2,
				$post_2_ticket_2_attendee_1,
				$post_2_ticket_2_attendee_2,
				$post_2_ticket_3_attendee_1,
				$post_2_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_2 )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_2_ticket_1_attendee_1,
				$post_2_ticket_1_attendee_2,
				$post_2_ticket_2_attendee_1,
				$post_2_ticket_2_attendee_2,
				$post_2_ticket_3_attendee_1,
				$post_2_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_2 )->where( 'ticket_type', [ 'default', 'vip-pass' ] )->get_ids()
		);

		// Only `default` tickets.
		$this->assertEqualSets(
			[
				$post_1_ticket_1_attendee_1,
				$post_1_ticket_1_attendee_2,
				$post_1_ticket_3_attendee_1,
				$post_1_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_1 )->where( 'ticket_type', 'default' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_1_ticket_1_attendee_1,
				$post_1_ticket_1_attendee_2,
				$post_1_ticket_3_attendee_1,
				$post_1_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_1 )->where( 'ticket_type__not_in', 'vip-pass' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_2_ticket_1_attendee_1,
				$post_2_ticket_1_attendee_2,
				$post_2_ticket_3_attendee_1,
				$post_2_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_2 )->where( 'ticket_type', 'default' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_2_ticket_1_attendee_1,
				$post_2_ticket_1_attendee_2,
				$post_2_ticket_3_attendee_1,
				$post_2_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_2 )->where( 'ticket_type__not_in', 'vip-pass' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_1_ticket_1_attendee_1,
				$post_1_ticket_1_attendee_2,
				$post_1_ticket_3_attendee_1,
				$post_1_ticket_3_attendee_2,
				$post_2_ticket_1_attendee_1,
				$post_2_ticket_1_attendee_2,
				$post_2_ticket_3_attendee_1,
				$post_2_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'ticket_type', 'default' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_1_ticket_1_attendee_1,
				$post_1_ticket_1_attendee_2,
				$post_1_ticket_3_attendee_1,
				$post_1_ticket_3_attendee_2,
				$post_2_ticket_1_attendee_1,
				$post_2_ticket_1_attendee_2,
				$post_2_ticket_3_attendee_1,
				$post_2_ticket_3_attendee_2,
			],
			tribe_attendees()->where( 'ticket_type__not_in', 'vip-pass' )->get_ids()
		);

		// Only `vip-pass` tickets.
		$this->assertEqualSets(
			[
				$post_1_ticket_2_attendee_1,
				$post_1_ticket_2_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_1 )->where( 'ticket_type', 'vip-pass' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_1_ticket_2_attendee_1,
				$post_1_ticket_2_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_1 )->where( 'ticket_type__not_in', 'default' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_2_ticket_2_attendee_1,
				$post_2_ticket_2_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_2 )->where( 'ticket_type', 'vip-pass' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_2_ticket_2_attendee_1,
				$post_2_ticket_2_attendee_2,
			],
			tribe_attendees()->where( 'event', $post_2 )->where( 'ticket_type__not_in', 'default' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_1_ticket_2_attendee_1,
				$post_1_ticket_2_attendee_2,
				$post_2_ticket_2_attendee_1,
				$post_2_ticket_2_attendee_2,
			],
			tribe_attendees()->where( 'ticket_type', 'vip-pass' )->get_ids()
		);
		$this->assertEqualSets(
			[
				$post_1_ticket_2_attendee_1,
				$post_1_ticket_2_attendee_2,
				$post_2_ticket_2_attendee_1,
				$post_2_ticket_2_attendee_2,
			],
			tribe_attendees()->where( 'ticket_type__not_in', 'default' )->get_ids()
		);
	}
}
