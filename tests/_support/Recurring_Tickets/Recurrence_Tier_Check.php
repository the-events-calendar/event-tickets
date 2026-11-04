<?php
/**
 * Skips a test that needs the Recurrence tier on an ECP that cannot run it.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */

namespace TEC\Tickets\Tests\Recurring_Tickets;

use TEC\Events_Pro\Custom_Tables\V1\Updates\Events;

/**
 * Trait Recurrence_Tier_Check.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */
trait Recurrence_Tier_Check {
	/**
	 * Skips the test when ECP does not fire the actions the Recurrence tier needs, as the tier is then off.
	 *
	 * The same check as the tier's own, so these tests run again once ECP fires the actions.
	 *
	 * @return void
	 */
	protected function skip_without_recurrence_tier(): void {
		if (
			defined( Events::class . '::AFTER_DETACH_OCCURRENCE_ACTION' )
			&& defined( Events::class . '::AFTER_TRANSFER_OCCURRENCES_ACTION' )
		) {
			return;
		}

		$this->markTestSkipped( 'The installed ECP does not fire the SOFT-4830 actions, so the Recurrence tier is off.' );
	}
}
