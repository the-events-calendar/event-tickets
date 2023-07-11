<?php

use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Test_Utils;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class RSVP_Migration_Test extends FT_CT1_Migration_Test_Case {
	use RSVP_Ticket_Maker;
	use Migration_Runner;
	use CT1_Fixtures;
	use CT1_Test_Utils;

	/**
	 * ${CARET}
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $migrated;

	/**
	 * It should migrate Single Event with RSVP ticket
	 *
	 * @test
	 */
	public function should_migrate_single_event_with_rsvp_ticket(): void {
		$single_event    = $this->given_a_non_migrated_single_event();
		$single_event_id = $single_event->ID;
		$rsvp_ticket_id  = $this->create_rsvp_ticket( $single_event_id );

		$this->run_migration();

		$this->assert_migration_success();
		$this->assert_migration_migrated( [ 'single' => 1, 'recurring' => 0 ] );
		$this->assert_migration_report_matches_snapshot( $this->get_migration_report() );
	}
}