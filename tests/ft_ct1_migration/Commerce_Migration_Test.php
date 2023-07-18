<?php

namespace TEC\Tickets\Tests\FT_CT1_Migration;

use TEC\Events\Custom_Tables\V1\Migration\State;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Single_Event_Migration_Strategy;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Multi_Rule_Event_Migration_Strategy;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Single_Rule_Event_Migration_Strategy;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\RSVP_Ticketed_Recurring_Event_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\Ticketed_Multi_Rule_Event_Migration_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\Ticketed_Single_Rule_Event_Migration_Strategy;
use TEC\Tickets\Flexible_Tickets\Series_Passes;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Test_Utils;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;

class Commerce_Migration_Test extends FT_CT1_Migration_Test_Case {
	use CT1_Fixtures;
	use CT1_Test_Utils;
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
	 * @before
	 */
	public function set_migration_phase(): void {
		$this->given_the_current_migration_phase_is( State::PHASE_MIGRATION_IN_PROGRESS );
	}

	/**
	 * It should migrate single event with Commerce Ticket
	 *
	 * @test
	 */
	public function should_migrate_single_event_with_commerce_ticket(): void {
		$single_event    = $this->given_a_non_migrated_single_event();
		$single_event_id = $single_event->ID;
		$ticket_id       = $this->create_tc_ticket( $single_event_id, 23 );

		$this->run_migration();

		$this->assert_migration_succeeded();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()        => 1,
			Single_Rule_Event_Migration_Strategy::get_slug()   => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()    => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug() => 0,
		] );
		$event_report = $this->get_migration_report_for_event( $single_event_id );
		$this->assertEquals( 'success', $event_report->status );
	}

	/**
	 * It should preview recurring event with 1 RRULE and Commerce ticket with no attendees
	 *
	 * @test
	 */
	public function should_preview_recurring_event_with_1_rrule_and_commerce_ticket_with_no_attendees(): void {
		$recurring_event    = $this->given_a_non_migrated_recurring_event();
		$recurring_event_id = $recurring_event->ID;
		$ticket_id          = $this->create_tc_ticket( $recurring_event_id, 23 );

		$this->run_migration( true );

		$this->assert_migration_succeeded();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()               => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()          => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()           => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug()        => 0,
			Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() => 1,
		] );
		$event_report = $this->get_migration_report_for_event( $recurring_event_id );
		$this->assertEquals( 'success', $event_report->status );
		$occurrences_count_after = Occurrence::where( 'post_id', '=', $recurring_event_id )
		                                     ->count();
		$this->assertEquals( 0, $occurrences_count_after );
		$this->assertCount( 1, $event_report->series );
		$series_id = $event_report->series[0]->ID;
		$this->assertEqualsCanonicalizing(
			[],
			tribe_tickets()->where( 'event', $series_id )->get_ids()
		);
		$this->assertEquals(
			[ Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() ],
			$event_report->strategies_applied
		);
		$this->assertEqualsCanonicalizing(
			[ $ticket_id ],
			$event_report->moved_tickets
		);
		$this->assertEquals(
			[ $ticket_id => 0 ],
			$event_report->moved_attendees
		);
		$this->assertEquals( [], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( [], tribe_attendees()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( 'default', Commerce::get_instance()->get_ticket( $series_id, $ticket_id )->type() );
	}

	/**
	 * It should preview recurring event with 1 RRULE many Commerce tickets with many attendees
	 *
	 * @test
	 */
	public function should_preview_recurring_event_with_1_rrule_many_commerce_tickets_with_many_attendees(): void {
		$recurring_event    = $this->given_a_non_migrated_recurring_event();
		$recurring_event_id = $recurring_event->ID;
		$ticket_1           = $this->create_tc_ticket( $recurring_event_id, 23 );
		$ticket_2           = $this->create_tc_ticket( $recurring_event_id, 89 );
		$ticket_3           = $this->create_tc_ticket( $recurring_event_id, 66 );
		$ticket_1_attendees = $this->create_many_attendees_for_ticket( 3, $ticket_1, $recurring_event_id );
		$ticket_2_attendees = $this->create_many_attendees_for_ticket( 5, $ticket_2, $recurring_event_id );
		$ticket_3_attendees = $this->create_many_attendees_for_ticket( 7, $ticket_3, $recurring_event_id );

		$this->run_migration( true );

		$this->assert_migration_succeeded();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()               => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()          => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()           => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug()        => 0,
			Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() => 1,
		] );
		$event_report = $this->get_migration_report_for_event( $recurring_event_id );
		$this->assertEquals( 'success', $event_report->status );
		$occurrences_count_after = Occurrence::where( 'post_id', '=', $recurring_event_id )
		                                     ->count();
		$this->assertEquals( 0, $occurrences_count_after );
		$this->assertCount( 1, $event_report->series );
		$series_id = $event_report->series[0]->ID;
		$this->assertEqualsCanonicalizing( [], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals(
			[ Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() ],
			$event_report->strategies_applied
		);
		$this->assertEqualsCanonicalizing(
			[ $ticket_1, $ticket_2, $ticket_3 ],
			$event_report->moved_tickets
		);
		$this->assertEquals(
			[ $ticket_1 => 3, $ticket_2 => 5, $ticket_3 => 7 ],
			$event_report->moved_attendees
		);
		$this->assertEquals( [], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		$this->assertEqualsCanonicalizing( [], tribe_attendees()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( 'default', Commerce::get_instance()->get_ticket( $series_id, $ticket_1 )->type() );
		$this->assertEquals( 'default', Commerce::get_instance()->get_ticket( $series_id, $ticket_2 )->type() );
		$this->assertEquals( 'default', Commerce::get_instance()->get_ticket( $series_id, $ticket_3 )->type() );
	}

	/**
	 * It should migrate recurring event with 1 RRULE and Commerce ticket with no attendees
	 *
	 * @test
	 */
	public function should_migrate_recurring_event_with_1_rrule_and_commerce_ticket_with_no_attendees(): void {
		$recurring_event          = $this->given_a_non_migrated_recurring_event();
		$occurrences_before       = $this->last_insertion_post_id_to_dates_map;
		$occurrences_count_before = count( $occurrences_before );
		$recurring_event_id       = $recurring_event->ID;
		$paypal_ticket_id         = $this->create_tc_ticket( $recurring_event_id, 23 );

		$this->run_migration( false );

		$this->assert_migration_succeeded();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()               => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()          => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()           => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug()        => 0,
			Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() => 1,
		] );
		$event_report = $this->get_migration_report_for_event( $recurring_event_id );
		$this->assertEquals( 'success', $event_report->status );
		$occurrences_count_after = Occurrence::where( 'post_id', '=', $recurring_event_id )
		                                     ->count();
		$this->assertEquals( $occurrences_count_before, $occurrences_count_after );
		$this->assertCount( 1, $event_report->series );
		$series_id = $event_report->series[0]->ID;
		$this->assertEqualsCanonicalizing(
			[ $paypal_ticket_id ],
			tribe_tickets()->where( 'event', $series_id )->get_ids()
		);
		$this->assertEquals(
			[ Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() ],
			$event_report->strategies_applied
		);
		$this->assertEqualsCanonicalizing(
			[ $paypal_ticket_id ],
			$event_report->moved_tickets
		);
		$this->assertEquals(
			[ $paypal_ticket_id => 0 ],
			$event_report->moved_attendees
		);
		$this->assertEquals( [ $paypal_ticket_id ], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( [], tribe_attendees()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( Series_Passes::TICKET_TYPE, Commerce::get_instance()->get_ticket( $series_id, $paypal_ticket_id )->type() );
	}

	/**
	 * It should migrate recurring event with 1 RRULE many Commerce tickets with many attendees
	 *
	 * @test
	 */
	public function should_migrate_recurring_event_with_1_rrule_many_commerce_tickets_with_many_attendees(): void {
		$recurring_event          = $this->given_a_non_migrated_recurring_event();
		$occurrences_before       = $this->last_insertion_post_id_to_dates_map;
		$occurrences_count_before = count( $occurrences_before );
		$recurring_event_id       = $recurring_event->ID;
		$ticket_1                 = $this->create_tc_ticket( $recurring_event_id, 23 );
		$ticket_2                 = $this->create_tc_ticket( $recurring_event_id, 89 );
		$ticket_3                 = $this->create_tc_ticket( $recurring_event_id, 66 );
		$ticket_1_attendees       = $this->create_many_attendees_for_ticket( 3, $ticket_1, $recurring_event_id );
		$ticket_2_attendees       = $this->create_many_attendees_for_ticket( 5, $ticket_2, $recurring_event_id );
		$ticket_3_attendees       = $this->create_many_attendees_for_ticket( 7, $ticket_3, $recurring_event_id );

		$this->run_migration( false );

		$this->assert_migration_succeeded();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()               => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()          => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()           => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug()        => 0,
			Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() => 1,
		] );
		$event_report = $this->get_migration_report_for_event( $recurring_event_id );
		$this->assertEquals( 'success', $event_report->status );
		$occurrences_count_after = Occurrence::where( 'post_id', '=', $recurring_event_id )
		                                     ->count();
		$this->assertEquals( $occurrences_count_before, $occurrences_count_after );
		$this->assertCount( 1, $event_report->series );
		$series_id = $event_report->series[0]->ID;
		$this->assertEqualsCanonicalizing(
			[ $ticket_1, $ticket_2, $ticket_3 ],
			tribe_tickets()->where( 'event', $series_id )->get_ids()
		);
		$this->assertEquals(
			[ Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() ],
			$event_report->strategies_applied
		);
		$this->assertEqualsCanonicalizing(
			[ $ticket_1, $ticket_2, $ticket_3 ],
			$event_report->moved_tickets
		);
		$this->assertEquals(
			[ $ticket_1 => 3, $ticket_2 => 5, $ticket_3 => 7 ],
			$event_report->moved_attendees
		);
		$this->assertEquals( [
			$ticket_1,
			$ticket_2,
			$ticket_3
		], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		$this->assertEqualsCanonicalizing(
			array_merge( $ticket_1_attendees, $ticket_2_attendees, $ticket_3_attendees ),
			tribe_attendees()->where( 'event', $series_id )->get_ids()
		);
		$this->assertEquals( Series_Passes::TICKET_TYPE, Commerce::get_instance()->get_ticket( $series_id, $ticket_1 )->type() );
		$this->assertEquals( Series_Passes::TICKET_TYPE, Commerce::get_instance()->get_ticket( $series_id, $ticket_2 )->type() );
		$this->assertEquals( Series_Passes::TICKET_TYPE, Commerce::get_instance()->get_ticket( $series_id, $ticket_3 )->type() );
	}

	/**
	 * It should preview recurring event with multiple rules, tickets and attendees
	 *
	 * @test
	 */
	public function should_preview_recurring_event_with_multiple_rules_tickets_and_attendees(): void {
		$recurring_event    = $this->given_a_non_migrated_multi_rule_recurring_event();
		$recurring_event_id = $recurring_event->ID;
		$ticket_1           = $this->create_tc_ticket( $recurring_event_id, 23 );
		$ticket_2           = $this->create_tc_ticket( $recurring_event_id, 89 );
		$ticket_3           = $this->create_tc_ticket( $recurring_event_id, 66 );
		$ticket_1_attendees = $this->create_many_attendees_for_ticket( 3, $ticket_1, $recurring_event_id );
		$ticket_2_attendees = $this->create_many_attendees_for_ticket( 5, $ticket_2, $recurring_event_id );
		$ticket_3_attendees = $this->create_many_attendees_for_ticket( 7, $ticket_3, $recurring_event_id );

		$this->run_migration( true );

		$this->assert_migration_succeeded();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()               => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()          => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()           => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug()        => 0,
			Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() => 0,
			Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug()  => 1,
		] );
		$event_report = $this->get_migration_report_for_event( $recurring_event_id );
		$this->assertEquals( 'success', $event_report->status );
		$occurrences_count_after = Occurrence::where( 'post_id', '=', $recurring_event_id )
		                                     ->count();
		$this->assertEquals( 0, $occurrences_count_after );
		$this->assertCount( 1, $event_report->series );
		$series_id = $event_report->series[0]->ID;
		$this->assertEqualsCanonicalizing( [], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		codecept_debug( $event_report->strategies_applied );
		$this->assertEquals(
			[ Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug() ],
			$event_report->strategies_applied
		);
		$this->assertEqualsCanonicalizing(
			[ $ticket_1, $ticket_2, $ticket_3 ],
			$event_report->moved_tickets
		);
		$this->assertEquals(
			[ $ticket_1 => 3, $ticket_2 => 5, $ticket_3 => 7 ],
			$event_report->moved_attendees
		);
		$this->assertEquals( [], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		$this->assertEqualsCanonicalizing( [], tribe_attendees()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( 'default', Commerce::get_instance()->get_ticket( $series_id, $ticket_1 )->type() );
		$this->assertEquals( 'default', Commerce::get_instance()->get_ticket( $series_id, $ticket_2 )->type() );
		$this->assertEquals( 'default', Commerce::get_instance()->get_ticket( $series_id, $ticket_3 )->type() );
	}

	/**
	 * It should migrate recurring event with multiple rules, tickets and attendees
	 *
	 * @test
	 */
	public function should_migrate_recurring_event_with_multiple_rules_tickets_and_attendees(): void {
		$recurring_event          = $this->given_a_non_migrated_multi_rule_recurring_event();
		$occurrences_before       = $this->last_insertion_post_id_to_dates_map;
		$occurrences_count_before = count( $occurrences_before );
		$recurring_event_id       = $recurring_event->ID;
		$ticket_1                 = $this->create_tc_ticket( $recurring_event_id, 23 );
		$ticket_2                 = $this->create_tc_ticket( $recurring_event_id, 89 );
		$ticket_3                 = $this->create_tc_ticket( $recurring_event_id, 66 );
		$ticket_1_attendees       = $this->create_many_attendees_for_ticket( 3, $ticket_1, $recurring_event_id );
		$ticket_2_attendees       = $this->create_many_attendees_for_ticket( 5, $ticket_2, $recurring_event_id );
		$ticket_3_attendees       = $this->create_many_attendees_for_ticket( 7, $ticket_3, $recurring_event_id );

		$this->run_migration( false );

		$this->assert_migration_succeeded();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()               => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()          => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()           => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug()        => 0,
			Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() => 0,
			Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug()  => 1,
		] );
		$event_report = $this->get_migration_report_for_event( $recurring_event_id );
		$this->assertEquals( 'success', $event_report->status );
		$occurrences_count_after = Occurrence::where( 'post_id', '=', $recurring_event_id )
		                                     ->count();
		$debug                   = iterator_to_array( Occurrence::where( 'post_id', '=', $recurring_event_id )
		                                                        ->all() );
		$this->assertEquals( $occurrences_count_before, $occurrences_count_after );
		$this->assertCount( 1, $event_report->series );
		$series_id = $event_report->series[0]->ID;
		$this->assertEqualsCanonicalizing(
			[ $ticket_1, $ticket_2, $ticket_3 ],
			tribe_tickets()->where( 'event', $series_id )->get_ids()
		);
		$this->assertEquals(
			[ Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug() ],
			$event_report->strategies_applied
		);
		$this->assertEqualsCanonicalizing(
			[ $ticket_1, $ticket_2, $ticket_3 ],
			$event_report->moved_tickets
		);
		$this->assertEquals(
			[ $ticket_1 => 3, $ticket_2 => 5, $ticket_3 => 7 ],
			$event_report->moved_attendees
		);
		$this->assertEquals( [
			$ticket_1,
			$ticket_2,
			$ticket_3
		], tribe_tickets()->where( 'event', $series_id )->get_ids() );
		$this->assertEqualsCanonicalizing(
			array_merge( $ticket_1_attendees, $ticket_2_attendees, $ticket_3_attendees ),
			tribe_attendees()->where( 'event', $series_id )->get_ids()
		);
		$this->assertEquals( Series_Passes::TICKET_TYPE, Commerce::get_instance()->get_ticket( $series_id, $ticket_1 )->type() );
		$this->assertEquals( Series_Passes::TICKET_TYPE, Commerce::get_instance()->get_ticket( $series_id, $ticket_2 )->type() );
		$this->assertEquals( Series_Passes::TICKET_TYPE, Commerce::get_instance()->get_ticket( $series_id, $ticket_3 )->type() );
	}
}
