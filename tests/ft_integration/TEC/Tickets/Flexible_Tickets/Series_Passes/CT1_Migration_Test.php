<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Generator;
use Spatie\Snapshots\MatchesSnapshots;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Migration\State;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Null_Migration_Strategy;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Single_Event_Migration_Strategy;
use TEC\Events\Custom_Tables\V1\Migration\String_Dictionary;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Multi_Rule_Event_Migration_Strategy;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Single_Rule_Event_Migration_Strategy;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\RSVP_Ticketed_Recurring_Event_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\Ticketed_Multi_Rule_Event_Migration_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\Ticketed_Single_Rule_Event_Migration_Strategy;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use WP_Post;

class CT1_Migration_Test extends Controller_Test_Case {
	use CT1_Fixtures;
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Commerce_Ticket_Maker;
	use MatchesSnapshots;

	protected string $controller_class = CT1_Migration::class;

	private function given_a_non_migrated_multi_rule_recurring_event(): WP_Post {
		$recurrence = static function ( int $id ): array {
			return ( new Recurrence() )
				->with_start_date( get_post_meta( $id, '_EventStartDate', true ) )
				->with_end_date( get_post_meta( $id, '_EventEndDate', true ) )
				->with_weekly_recurrence()
				->with_end_after( 50 )
				->with_monthly_recurrence( 1, false, 23 )
				->with_end_after( 5 )
				->to_event_recurrence();
		};

		return $this->given_a_non_migrated_recurring_event( $recurrence );
	}

	/**
	 * It should not alter the strategy to migrate a single event
	 *
	 * @test
	 */
	public function should_not_alter_the_strategy_to_migrate_a_single_event(): void {
		$single_event = $this->given_a_non_migrated_single_event();

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy( new Null_Migration_Strategy(), $single_event->ID, false );

		$this->assertInstanceOf( Null_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should not alter the strategy to migrate a recurring event with one rule
	 *
	 * @test
	 */
	public function should_not_alter_the_strategy_to_migrate_a_recurring_event_with_one_rule(): void {
		$recurring_event = $this->given_a_non_migrated_recurring_event();

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$recurring_event->ID,
			false
		);

		$this->assertInstanceOf( Null_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should not alter the strategy to migrate a recurring event with multiple rules
	 *
	 * @test
	 */
	public function should_not_alter_the_strategy_to_migrate_a_recurring_event_with_multiple_rules(): void {
		$multi_rule_recurring_event = $this->given_a_non_migrated_multi_rule_recurring_event();

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$multi_rule_recurring_event->ID,
			false
		);

		$this->assertInstanceOf( Null_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should not alter the strategy to migrate a single event with RSVP tickets
	 *
	 * @test
	 */
	public function should_not_alter_the_strategy_to_migrate_a_single_event_with_rsvp_tickets(): void {
		$single_event = $this->given_a_non_migrated_single_event();
		$this->create_rsvp_ticket( $single_event->ID );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy( new Null_Migration_Strategy(), $single_event->ID, false );

		$this->assertInstanceOf( Null_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should alter the strategy to migrate a recurring event with one rule and RSVP tickets
	 *
	 * @test
	 */
	public function should_alter_the_strategy_to_migrate_a_recurring_event_with_one_rule_and_rsvp_tickets(): void {
		$recurring_event = $this->given_a_non_migrated_recurring_event();
		$this->create_rsvp_ticket( $recurring_event->ID );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$recurring_event->ID,
			false
		);

		$this->assertInstanceOf( RSVP_Ticketed_Recurring_Event_Strategy::class, $strategy );
	}

	/**
	 * It should alter the strategy to migrate a recurring event with multiple rules and RSVP tickets
	 *
	 * @test
	 */
	public function should_alter_the_strategy_to_migrate_a_recurring_event_with_multiple_rules_and_rsvp_tickets(): void {
		$multi_rule_recurring_event = $this->given_a_non_migrated_multi_rule_recurring_event();
		$this->create_rsvp_ticket( $multi_rule_recurring_event->ID );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$multi_rule_recurring_event->ID,
			false
		);

		$this->assertInstanceOf( RSVP_Ticketed_Recurring_Event_Strategy::class, $strategy );
	}

	private function given_paypal_tickets_are_active(): void {
		add_filter( 'tribe_tickets_get_modules', static function ( array $modules ): array {
			$modules[ PayPal::class ] = 'PayPal';

			return $modules;
		} );
	}

	/**
	 * It should not alter the strategy to migrate a single event with PayPal tickets
	 *
	 * @test
	 */
	public function should_not_alter_the_strategy_to_migrate_a_single_event_with_pay_pal_tickets(): void {
		$this->given_paypal_tickets_are_active();
		$single_event    = $this->given_a_non_migrated_single_event();
		$single_event_id = $single_event->ID;
		$this->create_paypal_ticket( $single_event_id, 23 );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$single_event_id,
			false
		);

		$this->assertInstanceOf( Null_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should alter the strategy to migrate a recurring event with PayPal tickets
	 *
	 * @test
	 */
	public function should_alter_the_strategy_to_migrate_a_recurring_event_with_pay_pal_tickets(): void {
		$this->given_paypal_tickets_are_active();
		$recurring_event    = $this->given_a_non_migrated_recurring_event();
		$recurring_event_id = $recurring_event->ID;
		$this->create_paypal_ticket( $recurring_event_id, 23 );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$recurring_event_id,
			false
		);

		$this->assertInstanceOf( Ticketed_Single_Rule_Event_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should alter the migration strategy of a recurring event with multiple rules and paypal tickets
	 *
	 * @test
	 */
	public function should_alter_the_migration_strategy_of_a_recurring_event_with_multiple_rules_and_paypal_tickets(): void {
		$this->given_paypal_tickets_are_active();
		$recurring_event    = $this->given_a_non_migrated_multi_rule_recurring_event();
		$recurring_event_id = $recurring_event->ID;
		$this->create_paypal_ticket( $recurring_event_id, 23 );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$recurring_event_id,
			false
		);

		$this->assertInstanceOf( Ticketed_Multi_Rule_Event_Migration_Strategy::class, $strategy );
	}

	private function given_commerce_tickets_are_active(): void {
		add_filter( 'tribe_tickets_get_modules', static function ( array $modules ): array {
			$modules[ Commerce::class ] = 'Tickets Commerce';

			return $modules;
		} );
	}

	/**
	 * It should not alter the strategy to migrate a Single Event with Commerce tickets
	 *
	 * @test
	 */
	public function should_not_alter_the_strategy_to_migrate_a_single_event_with_commerce_tickets(): void {
		$this->given_commerce_tickets_are_active();
		$single_event = $this->given_a_non_migrated_single_event();
		$this->create_tc_ticket( $single_event->JID, 23 );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$single_event->ID,
			false
		);

		$this->assertInstanceOf( Null_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should alter the strategy to migrate a recurring event with commerce tickets
	 *
	 * @test
	 */
	public function should_alter_the_strategy_to_migrate_a_recurring_event_with_commerce_tickets(): void {
		$this->given_commerce_tickets_are_active();
		$recurring_event = $this->given_a_non_migrated_recurring_event();
		$this->create_tc_ticket( $recurring_event->ID, 23 );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$recurring_event->ID,
			false
		);

		$this->assertInstanceOf( Ticketed_Single_Rule_Event_Migration_Strategy::class, $strategy );
	}

	/**
	 * It should alter the strategy to migrate a recurring event with multiple rules and commerce tickets
	 *
	 * @test
	 */
	public function should_alter_the_strategy_to_migrate_a_recurring_event_with_multiple_rules_and_commerce_tickets(): void {
		$this->given_commerce_tickets_are_active();
		$recurring_event = $this->given_a_non_migrated_multi_rule_recurring_event();
		$this->create_tc_ticket( $recurring_event->ID, 23 );

		$controller = $this->make_controller();
		$strategy   = $controller->alter_migration_strategy(
			new Null_Migration_Strategy(),
			$recurring_event->ID,
			false
		);

		$this->assertInstanceOf( Ticketed_Multi_Rule_Event_Migration_Strategy::class, $strategy );
	}

	public function report_headers_provider(): Generator {
		yield 'no headers' => [ [] ];
		yield 'TEC and ECP headers' => [
			[
				[
					'key'   => Multi_Rule_Event_Migration_Strategy::get_slug(),
					'label' => 'Multi Rule Event Migration Strategy'
				],
				[
					'key'   => Single_Rule_Event_Migration_Strategy::get_slug(),
					'label' => 'Single Rule Event Migration Strategy'
				],
				[
					'key'   => Single_Event_Migration_Strategy::get_slug(),
					'label' => 'Single Migration Strategy'
				],
			]
		];
	}

	/**
	 * It should correctly filter report headers in preview
	 *
	 * @test
	 * @dataProvider report_headers_provider
	 */
	public function should_correctly_filter_report_headers_in_preview( array $headers ): void {
		$controller = $this->make_controller();
		$controller->register();
		tribe( State::class )->set( 'phase', State::PHASE_MIGRATION_PROMPT );
		$headers = $controller->filter_report_headers( $headers, tribe( String_Dictionary::class )->reinit() );

		$this->assertMatchesSnapshot( $headers );
	}

	/**
	 * It should correctly filter report headers in execution
	 *
	 * @test
	 * @dataProvider report_headers_provider
	 */
	public function should_correctly_filter_report_headers_in_execution( array $headers ): void {
		$controller = $this->make_controller();
		$controller->register();
		tribe( State::class )->set( 'phase', State::PHASE_MIGRATION_COMPLETE );
		$headers = $controller->filter_report_headers( $headers, tribe( String_Dictionary::class )->reinit() );

		$this->assertMatchesSnapshot( $headers );
	}
}