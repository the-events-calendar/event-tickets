<?php

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use TEC\Events\Custom_Tables\V1\Migration\Expected_Migration_Exception;
use TEC\Events\Custom_Tables\V1\Migration\Migration_Exception;
use TEC\Events\Custom_Tables\V1\Migration\Reports\Event_Report;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;

class RSVP_Ticketed_Recurring_Event_StrategyTest extends \Codeception\TestCase\WPTestCase {
	use CT1_Fixtures;
	use Ticket_Maker;

	/**
	 * It should throw if constructed on a non-event post
	 *
	 * @test
	 */
	public function should_throw_if_constructed_on_a_non_event_post(): void {
		$post_id = static::factory()->post->create();

		$this->expectException( Migration_Exception::class );

		new RSVP_Ticketed_Recurring_Event_Strategy( $post_id, true );
	}

	/**
	 * It should throw if trying to build on non-recurring event
	 *
	 * @test
	 */
	public function should_throw_if_trying_to_build_on_non_recurring_event(): void {
		$event = $this->given_a_non_migrated_single_event();

		$this->expectException( Migration_Exception::class );

		new RSVP_Ticketed_Recurring_Event_Strategy( $event->ID, true );
	}

	/**
	 * It should throw if recurring event has not RSVP tickets
	 *
	 * @test
	 */
	public function should_throw_if_recurring_event_has_not_rsvp_tickets(): void {
		$event = $this->given_a_non_migrated_recurring_event();

		$this->expectException( Migration_Exception::class );

		new RSVP_Ticketed_Recurring_Event_Strategy( $event->ID, true );
	}

	/**
	 * It should throw expected exception when trying to migrate recurring event with RSVP tickets
	 *
	 * @test
	 */
	public function should_throw_expected_exception_when_trying_to_migrate_recurring_event_with_rsvp_tickets(): void {
		$event = $this->given_a_non_migrated_recurring_event();
		$this->create_rsvp_ticket( $event->ID );

		$strategy = new RSVP_Ticketed_Recurring_Event_Strategy( $event->ID, true );
		
		$this->expectException( Expected_Migration_Exception::class );

		$strategy->apply( new Event_Report( $event ) );
	}
}
