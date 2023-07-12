<?php


namespace TEC\Tickets\Tests\FT_CT1_Migration;

use TEC\Events\Custom_Tables\V1\Migration\State;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Test_Utils;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class RSVP_Migration_Test extends FT_CT1_Migration_Test_Case {
	use CT1_Fixtures;
	use CT1_Test_Utils;
	use RSVP_Ticket_Maker;

	/**
	 * It should migrate Single Event with RSVP ticket
	 *
	 * @test
	 */
	public function should_migrate_single_event_with_rsvp_ticket(): void {
		$this->given_the_current_migration_phase_is( State::PHASE_MIGRATION_IN_PROGRESS );
		$single_event    = $this->given_a_non_migrated_single_event();
		$single_event_id = $single_event->ID;
		$rsvp_ticket_id  = $this->create_rsvp_ticket( $single_event_id );

		$this->run_migration();

		$this->assert_migration_success();
		$this->assert_migration_strategy_count( [
			'single'                => 1,
			'recurring-single-rule' => 0,
			'recurring-multi-rule'  => 0
		] );
		$event_report = $this->get_migration_report_for_event( $single_event_id );
		$this->assertEquals( 'success', $event_report->status );
	}

	ou@
}