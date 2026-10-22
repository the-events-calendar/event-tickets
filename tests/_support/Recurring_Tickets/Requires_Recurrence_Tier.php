<?php
/**
 * Skips every test of a class that needs the Recurrence tier on an ECP that cannot run it.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */

namespace TEC\Tickets\Tests\Recurring_Tickets;

/**
 * Trait Requires_Recurrence_Tier.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */
trait Requires_Recurrence_Tier {
	use Recurrence_Tier_Check;

	/**
	 * Skips the test before the class's own set-up, which may need the tier too.
	 *
	 * PHPUnit runs a trait's `@before` methods ahead of the class's own.
	 *
	 * @before
	 *
	 * @return void
	 */
	public function require_recurrence_tier(): void {
		$this->skip_without_recurrence_tier();
	}
}
