<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Tickets_Commerce_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

class Event_Repository_Attendee_Filters_Test extends WPTestCase {
	use Tickets_Commerce_Ticket_Maker;
	use Attendee_Maker;

	private function given_an_event_without_attendees(): int {
		static $hash = 1;

		return tribe_events()->set_args( [
			'title'      => 'Event without attendees ' . $hash ++,
			'status'     => 'publish',
			'start_date' => '2202-02-02 02:02:02',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;
	}

	/**
	 * @return array{0: int, 1: int[]}
	 */
	private function given_an_event_with_attendees( int $count, int $user_id = 0 ): array {
		$id = $this->given_an_event_without_attendees();

		$ticket_id = $this->create_tc_ticket( $id );
		foreach ( range( 1, $count ) as $k ) {
			$attendee_ids[] = $this->create_attendee_for_ticket( $ticket_id, $id, [ 'user_id' => $user_id ?: 0 ] );
		}

		return [ $id, $attendee_ids ];
	}

	public function setUp(): void {
		parent::setUp();

		// Ensure the PayPal module is active.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees PayPal.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function test_filter_events_by_has_attendees(): void {
		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1 );
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 2 );

		// Baseline, no filters.
		$this->assertEqualSets(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1,
				$event_with_attendees_2
			],
			tribe_events()->get_ids()
		);

		// Get Events with Attendees.
		$this->assertEqualSets(
			[
				$event_with_attendees_1,
				$event_with_attendees_2
			],
			tribe_events()->where( 'has_attendees', true )->get_ids()
		);

		// Get Events without Attendees.
		$this->assertEqualSets(
			[
				$event_without_attendees_1,
				$event_without_attendees_2
			],
			tribe_events()->where( 'has_attendees', false )->get_ids()
		);
	}

	public function test_filter_events_by_attendee(): void {
		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1 );
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 2 );

		// By the only attendee ID.
		$this->assertEqualSets(
			[ $event_with_attendees_1 ],
			tribe_events()->where( 'attendee', $event_1_attendees[0] )->get_ids()
		);

		// By the first attendee ID.
		$this->assertEqualSets(
			[ $event_with_attendees_2 ],
			tribe_events()->where( 'attendee', $event_2_attendees[0] )->get_ids()
		);
		// By the second attendee ID.
		$this->assertEqualSets(
			[ $event_with_attendees_2 ],
			tribe_events()->where( 'attendee', $event_2_attendees[1] )->get_ids()
		);
		// By array of Attendee IDs.
		$this->assertEqualSets(
			[ $event_with_attendees_2 ],
			tribe_events()->where( 'attendee', $event_2_attendees )->get_ids()
		);

		// A post ID that will not map to an Attendee.
		$this->assertEqualSets(
			[],
			tribe_events()->where( 'attendee', PHP_INT_MAX )->get_ids()
		);
	}

	public function test_filter_events_by_attendee_not_in(): void {
		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1 );
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 2 );

		// By the only attendee ID.
		$this->assertEqualSets(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_2
			],
			tribe_events()->where( 'attendee__not_in', $event_1_attendees[0] )->get_ids()
		);

		// By the first attendee ID.
		$this->assertEqualSets(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1
			],
			tribe_events()->where( 'attendee__not_in', $event_2_attendees[0] )->get_ids()
		);
		// By the second attendee ID.
		$this->assertEqualSets(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1
			],
			tribe_events()->where( 'attendee__not_in', $event_2_attendees[1] )->get_ids()
		);
		// By array of Attendee IDs.
		$this->assertEqualSets(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1
			],
			tribe_events()->where( 'attendee__not_in', $event_2_attendees )->get_ids()
		);

		// A post ID that will not map to an Attendee.
		$this->assertEqualSets(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1,
				$event_with_attendees_2
			],
			tribe_events()->where( 'attendee__not_in', PHP_INT_MAX )->get_ids()
		);
	}

	public function test_filter_events_by_attendee_user(): void {
		$user_id_1 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_2 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_3 = static::factory()->user->create( [ 'role' => 'administrator' ] );

		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_1 );

		// Create the following attendees as another user.
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_2 );

		$this->assertEqualSets( [
			$event_with_attendees_1
		],
			tribe_events()->where( 'attendee_user', $user_id_1 )->get_ids()
		);

		$this->assertEqualSets( [
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user', $user_id_2 )->get_ids()
		);

		$this->assertEqualSets( [
			$event_with_attendees_1,
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user', [ $user_id_1, $user_id_2 ] )->get_ids()
		);

		$this->assertEqualSets( [
			$event_with_attendees_1,
		],
			tribe_events()->where( 'attendee_user', [ $user_id_1, $user_id_3 ] )->get_ids()
		);

		$this->assertEqualSets( [], tribe_events()->where( 'attendee_user', $user_id_3 )->get_ids() );
	}

	public function test_filter_events_by_attendee_user_not_in(): void {
		$this->markTestSkipped( 'attendee_user__not_in support not yet implemented' );

		$user_id_1 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_2 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_3 = static::factory()->user->create( [ 'role' => 'administrator' ] );

		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_1 );

		// Create the following attendees as another user.
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_2 );

		$this->assertEqualSets( [
			$event_without_attendees_1,
			$event_without_attendees_2,
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user__not_in', $user_id_1 )->get_ids()
		);

		$this->assertEqualSets( [
			$event_without_attendees_1,
			$event_without_attendees_2,
			$event_with_attendees_1
		],
			tribe_events()->where( 'attendee_user__not_in', $user_id_2 )->get_ids()
		);

		$this->assertEqualSets( [
			$event_without_attendees_1,
			$event_without_attendees_2
		],
			tribe_events()->where( 'attendee_user__not_in', [ $user_id_1, $user_id_2 ] )->get_ids()
		);

		$this->assertEqualSets( [
			$event_without_attendees_1,
			$event_without_attendees_2,
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user__not_in', [ $user_id_1, $user_id_3 ] )->get_ids()
		);
	}
}