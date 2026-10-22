<?php
/**
 * Syncs a large recurring event in the background.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Tasks
 */

namespace TEC\Tickets\Recurring_Tickets\Tasks;

use InvalidArgumentException;
use TEC\Common\StellarWP\Shepherd\Abstracts\Task_Abstract;
use TEC\Tickets\Recurring_Tickets\Sync;

/**
 * Runs Sync for one event, for an event with more template-date pairs than Sync writes in a request.
 *
 * Shepherd keeps one pending task per event: its arguments are the event alone.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Tasks
 */
class Sync_Task extends Task_Abstract {
	/**
	 * Sync_Task constructor.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event's post ID.
	 */
	public function __construct( int $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- It takes one event ID, typed.
		parent::__construct( $post_id );
	}

	/**
	 * Syncs the event.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function process(): void {
		tribe( Sync::class )->sync_event_inline( ...$this->get_args() );
	}

	/**
	 * Returns the prefix of the task's argument hash.
	 *
	 * @since TBD
	 *
	 * @return string The prefix, at most 15 characters.
	 */
	public function get_task_prefix(): string {
		return 'tec_tic_rt_sync';
	}

	/**
	 * Returns how many times a failed sync is tried again.
	 *
	 * @since TBD
	 *
	 * @return int The number of retries.
	 */
	public function get_max_retries(): int {
		return 2;
	}

	/**
	 * Refuses a task without an event.
	 *
	 * @since TBD
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the post ID is not positive.
	 */
	protected function validate_args(): void {
		if ( (int) ( $this->get_args()[0] ?? 0 ) < 1 ) {
			throw new InvalidArgumentException( 'A Recurring Event Tickets sync task needs the ID of an event.' );
		}
	}
}
