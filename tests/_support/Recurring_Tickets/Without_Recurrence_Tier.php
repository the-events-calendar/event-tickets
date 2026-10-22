<?php

namespace TEC\Tickets\Tests\Recurring_Tickets;

use TEC\Tickets\Recurring_Tickets\Recurrence_Controller;

/**
 * Runs a test with the Core tier alone, as on a site without ECP: no ticket type is set and no row is synced.
 *
 * For tests of the Core tier that build their rows by hand.
 */
trait Without_Recurrence_Tier {
	/**
	 * Whether this test unregistered the Recurrence tier.
	 *
	 * @var bool
	 */
	private bool $recurrence_tier_unregistered = false;

	/**
	 * Whether this test class backed WordPress hooks up.
	 *
	 * @var bool
	 */
	private static bool $hooks_backed_up = false;

	/**
	 * @before
	 */
	public function unregister_recurrence_tier(): void {
		if ( ! Recurrence_Controller::is_registered() ) {
			return;
		}

		/*
		 * This runs before `set_up()`, which backs WordPress hooks up once, at the first test, and every `tear_down()`
		 * restores that backup: take it now, with the tier's hooks in, or every later test runs without them.
		 * The test case forwards `_backup_hooks()` to WordPress' own test case.
		 */
		if ( ! self::$hooks_backed_up ) {
			$this->_backup_hooks();
			self::$hooks_backed_up = true;
		}

		tribe( Recurrence_Controller::class )->unregister();
		tribe()->setVar( Recurrence_Controller::class . '_registered', false );
		$this->recurrence_tier_unregistered = true;
	}

	/**
	 * @after
	 */
	public function register_recurrence_tier(): void {
		if ( $this->recurrence_tier_unregistered ) {
			tribe( Recurrence_Controller::class )->register();
			$this->recurrence_tier_unregistered = false;
		}
	}
}
