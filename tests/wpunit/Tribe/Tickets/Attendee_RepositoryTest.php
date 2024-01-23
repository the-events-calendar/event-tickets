<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

class Attendee_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	use Commerce_Ticket_Maker;
	use Attendee_Maker;

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
}
