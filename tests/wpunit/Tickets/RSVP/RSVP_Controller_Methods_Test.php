<?php

namespace TEC\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;

/**
 * Regression test for the `tribe_aggregator_record_activity_wakeup` wiring.
 *
 * `register_csv_importer_hooks()` used to hook `register_rsvp_activity` via
 * `$container->callback( RSVP_Importer::class, ... )`, which made DI52 try to
 * auto-wire a fresh `RSVP_Importer` (and, transitively, a `File_Reader` whose
 * `$file_path` constructor param has no type hint) on every wakeup, fataling
 * with `ContainerException: auto-wiring is not magic`. The importer is only
 * ever safe to construct with a real file reader from an in-progress import,
 * never lazily by the container.
 *
 * @since TBD
 */
class RSVP_Controller_Methods_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_wakeup_activity_without_fataling() {
		$this->assertNotFalse(
			has_action( 'tribe_aggregator_record_activity_wakeup' ),
			'The RSVP controller should have wired up the activity wakeup hook.'
		);

		// Instantiating fires __wakeup(), which fires the action under test alongside
		// every other plugin's activity registration - prior to the fix this fatals
		// with a DI52 ContainerException instead of returning.
		$activity = new \Tribe__Events__Aggregator__Record__Activity();

		$this->assertTrue( $activity->exists( 'tribe_rsvp_tickets' ), 'RSVP tickets should be a registered activity slug.' );
	}
}
