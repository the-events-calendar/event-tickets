<?php
/**
 * Tickets Emails Handler.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use Tribe__Tickets__Main;
use WP_Error;

/**
 * Class Email_Handler.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */
class Email_Handler extends \TEC\Common\Contracts\Service_Provider {
	/**
	 * Registered emails.
	 *
	 * @since 5.5.9
	 *
	 * @var array<Email_Abstract>
	 */
	protected array $emails = [];

	/**
	 * Default emails registered by Event Tickets.
	 *
	 * @since 5.5.9
	 *
	 * @var array<string>
	 */
	protected array $default_emails = [
		Email\Ticket::class,
		Email\RSVP::class,
		Email\RSVP_Not_Going::class,
		Email\Purchase_Receipt::class,
		Email\Completed_Order::class,
	];

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.5.9
	 */
	public function register(): void {
		foreach ( $this->default_emails as $email_class ) {
			// Register as a singleton for internal ease of use.
			$this->container->singleton( $email_class, $email_class, [ 'hook' ] );

			// Create all the instance and save.
			$this->emails[] = $this->container->make( $email_class );
		}

		$this->container->singleton( static::class, $this );
	}

	/**
	 * Gets the registered emails.
	 *
	 * @since 5.5.9
	 *
	 * @return array<Email_Abstract>
	 */
	public function get_emails(): array {
		/**
		 * Filter the array of email classes that will be used.
		 *
		 * @since 5.5.9
		 *
		 * @param array $emails Array of email classes.
		 */
		return apply_filters( 'tec_tickets_emails_registered_emails', $this->emails );
	}

	/**
	 * Get email by ID.
	 *
	 * @since 5.5.9
	 *
	 * @param string $id ID of email.
	 *
	 * @return Email_Abstract|boolean Email object or false if it does not exist.
	 */
	public function get_email_by_id( $id ) {
		$emails = $this->get_emails();

		foreach ( $emails as $email ) {
			if ( $email->get_id() === $id ) {
				return $email;
			}
		}
		return false;
	}
}
