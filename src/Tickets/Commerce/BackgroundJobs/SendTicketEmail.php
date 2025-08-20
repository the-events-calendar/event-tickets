<?php
/**
 * SendTicketEmail class.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\Commerce\BackgroundJobs;
 */

namespace TEC\Tickets\Commerce\BackgroundJobs;

use TEC\Tickets\Commerce\Communication\Email;
use TEC\Common\StellarWP\Shepherd\Abstracts\Task_Abstract;
use InvalidArgumentException;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found, StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Pigeon's email task.
 *
 * @since 5.26.0
 *
 * @package StellarWP\Pigeon\Tasks;
 */
class SendTicketEmail extends Task_Abstract {
	/**
	 * The email task's constructor.
	 *
	 * @since 5.26.0
	 *
	 * @param int $order_id The order ID.
	 * @param int $event_id The event ID.
	 *
	 * @throws InvalidArgumentException If the email task's arguments are invalid.
	 */
	public function __construct( int $order_id, int $event_id ) {
		parent::__construct( $order_id, $event_id );
	}

	/**
	 * Processes the email task.
	 *
	 * @since 5.26.0
	 */
	public function process(): void {
		tribe( Email::class )->send_tickets_email( ...$this->get_args() );

		/**
		 * Fires when the email task is processed.
		 *
		 * @since 5.26.0
		 *
		 * @param SendTicketEmail $task The email task that was processed.
		 */
		do_action( 'tec_tickets_shepherd_tickets_mail_email_processed', $this );
	}

	/**
	 * Validates the email task's arguments.
	 *
	 * @since 5.26.0
	 *
	 * @throws InvalidArgumentException If the email task's arguments are invalid.
	 */
	protected function validate_args(): void {
		$args = $this->get_args();

		if ( $args['0'] < 1 || $args['1'] < 1 ) {
			throw new InvalidArgumentException( __( 'SendTicketEmail task requires at least 2 unsigned integer arguments.', 'event-tickets' ) );
		}
	}

	/**
	 * Gets the email task's hook prefix.
	 *
	 * @since 5.26.0
	 *
	 * @return string The email task's hook prefix.
	 */
	public function get_task_prefix(): string {
		return 'tec_tic_mail_';
	}

	/**
	 * Gets the maximum number of retries.
	 *
	 * @since 5.26.0
	 *
	 * @return int The maximum number of retries.
	 */
	public function get_max_retries(): int {
		return 4;
	}
}
