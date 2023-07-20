<?php


namespace TEC\Tickets\Tests\FT_CT1_Migration;

use TEC\Events\Custom_Tables\V1\Migration\State;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Single_Event_Migration_Strategy;
use TEC\Events\Custom_Tables\V1\Migration\String_Dictionary;
use TEC\Events\Custom_Tables\V1\Traits\With_String_Dictionary;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Multi_Rule_Event_Migration_Strategy;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Single_Rule_Event_Migration_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\RSVP_Ticketed_Recurring_Event_Strategy;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Test_Utils;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;

class RSVP_Migration_Test extends FT_CT1_Migration_Test_Case {
	use CT1_Fixtures;
	use CT1_Test_Utils;
	use RSVP_Ticket_Maker;
	use Commerce_Ticket_Maker;
	use With_String_Dictionary;

	public function dry_run_provider(): array {
		return [
			'Preview' => [ true ],
			'Execute' => [ false ],
		];
	}

	/**
	 * @before
	 */
	public function set_migration_phase(): void {
		$this->given_the_current_migration_phase_is( State::PHASE_MIGRATION_IN_PROGRESS );
	}

	/**
	 * It should migrate Single Event with RSVP ticket
	 *
	 * @test
	 * @dataProvider dry_run_provider
	 */
	public function should_migrate_single_event_with_rsvp_ticket( bool $dry_run ): void {
		$single_event    = $this->given_a_non_migrated_single_event();
		$single_event_id = $single_event->ID;
		$rsvp_ticket_id  = $this->create_rsvp_ticket( $single_event_id );

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
	 * It should not allow migration of a recurring event with RSVP ticket
	 *
	 * @test
	 * @dataProvider dry_run_provider
	 */
	public function should_not_allow_migration_of_a_recurring_event_with_rsvp_ticket( bool $dry_run ): void {
		$recurring_event    = $this->given_a_non_migrated_recurring_event();
		$recurring_event_id = $recurring_event->ID;
		$rsvp_ticket_id     = $this->create_rsvp_ticket( $recurring_event_id );

		$this->run_migration();

		$this->assert_migration_failed();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()        => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()   => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()    => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug() => 1,
		] );
		$report   = $this->get_migration_report_for_event( $recurring_event_id );
		$format = tribe( String_Dictionary::class )->get( 'migration-error-recurring-with-rsvp-tickets' );
		$expected = sprintf( $format, $this->get_event_link_markup( $recurring_event_id ) );
		$this->assertEquals( $expected, $report->error );
	}

	/**
	 * It should not allow migration of a recurring event with multiple rules and RSVP tickets
	 *
	 * @test
	 * @dataProvider dry_run_provider
	 */
	public function should_not_allow_migration_of_a_recurring_event_with_multiple_rules_and_rsvp_tickets( bool $dry_run ): void {
		$recurrence         = function ( $id ): array {
			return ( new Recurrence )
				->with_start_date( get_post_meta( $id, '_EventStartDate', true ) )
				->with_end_date( get_post_meta( $id, '_EventEndDate', true ) )
				->with_daily_recurrence( 3 )
				->with_end_after( 10 )
				->with_weekly_recurrence()
				->with_end_after( 5 )
				->to_event_recurrence();
		};
		$recurring_event    = $this->given_a_non_migrated_recurring_event( $recurrence );
		$recurring_event_id = $recurring_event->ID;
		$rsvp_ticket_id     = $this->create_rsvp_ticket( $recurring_event_id );

		$this->run_migration();

		$this->assert_migration_failed();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()        => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()   => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()    => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug() => 1,
		] );
		$report = $this->get_migration_report_for_event( $recurring_event_id );
		$format = tribe( String_Dictionary::class )->get( 'migration-error-recurring-with-rsvp-tickets' );
		$expected = sprintf( $format, $this->get_event_link_markup( $recurring_event_id ) );
		$this->assertEquals( $expected, $report->error );
	}

	/**
	 * It should not allow migration of recurring Event with RSVP and Tickets
	 *
	 * @test
	 * @dataProvider dry_run_provider
	 */
	public function should_not_allow_migration_of_recurring_event_with_rsvp_and_tickets( bool $dry_run ): void {
		$recurring_event    = $this->given_a_non_migrated_recurring_event();
		$recurring_event_id = $recurring_event->ID;
		$rsvp_ticket_id     = $this->create_rsvp_ticket( $recurring_event_id );
		$commerce_ticket_id = $this->create_tc_ticket( $recurring_event_id, 23 );

		$this->run_migration();

		$this->assert_migration_failed();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()        => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()   => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()    => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug() => 1,
		] );
		$report = $this->get_migration_report_for_event( $recurring_event_id );
		$format = tribe( String_Dictionary::class )->get( 'migration-error-recurring-with-rsvp-tickets' );
		$expected = sprintf( $format, $this->get_event_link_markup( $recurring_event_id ) );
		$this->assertEquals( $expected, $report->error );
	}

	/**
	 * It should not allow migration of recurring Event with multiple rules, RSVP and Tickets
	 *
	 * @test
	 * @dataProvider dry_run_provider
	 */
	public function should_not_allow_migration_of_recurring_event_with_multiple_rules_rsvp_and_tickets( bool $dry_run ): void {
		$recurrence         = function ( $id ): array {
			return ( new Recurrence )
				->with_start_date( get_post_meta( $id, '_EventStartDate', true ) )
				->with_end_date( get_post_meta( $id, '_EventEndDate', true ) )
				->with_daily_recurrence( 3 )
				->with_end_after( 10 )
				->with_weekly_recurrence()
				->with_end_after( 5 )
				->to_event_recurrence();
		};
		$recurring_event    = $this->given_a_non_migrated_recurring_event( $recurrence );
		$recurring_event_id = $recurring_event->ID;
		$rsvp_ticket_id     = $this->create_rsvp_ticket( $recurring_event_id );
		$commerce_ticket_id = $this->create_tc_ticket( $recurring_event_id, 23 );

		$this->run_migration();

		$this->assert_migration_failed();
		$this->assert_migration_strategy_count( [
			Single_Event_Migration_Strategy::get_slug()        => 0,
			Single_Rule_Event_Migration_Strategy::get_slug()   => 0,
			Multi_Rule_Event_Migration_Strategy::get_slug()    => 0,
			RSVP_Ticketed_Recurring_Event_Strategy::get_slug() => 1,
		] );
		$report = $this->get_migration_report_for_event( $recurring_event_id );
		$format = tribe( String_Dictionary::class )->get( 'migration-error-recurring-with-rsvp-tickets' );
		$expected = sprintf( $format, $this->get_event_link_markup( $recurring_event_id ) );
		$this->assertEquals( $expected, $report->error );
	}
}