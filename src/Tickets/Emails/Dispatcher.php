<?php
/**
 * Email Dispatcher class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

/**
 * Class Dispatcher.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
class Dispatcher {

	/**
	 * Stores the Email used in this Dispatcher.
	 *
	 * @since TBD
	 *
	 * @var Email_Abstract|null
	 */
	protected ?Email_Abstract $email;

	/**
	 * Sets the Email instance that will be used for this Dispatcher.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email
	 *
	 */
	public function set_email( Email_Abstract $email ): void {
		$this->email = $email;
	}

	/**
	 * Gets the email instance for this Dispatcher.
	 *
	 * @since TBD
	 *
	 * @return Email_Abstract|null
	 */
	public function get_email(): ?Email_Abstract {
		return $this->email;
	}

	/**
	 * Send an email.
	 *
	 * @since TBD
	 *
	 * @param string $to          Email to.
	 * @param string $subject     Email subject.
	 * @param string $message     Email message.
	 * @param string $headers     Email headers.
	 * @param array  $attachments Email attachments.
	 *
	 * @return bool success True if the email was sent.
	 */
	public function send( $to, $subject, $message, $headers, $attachments ): bool {
		// @todo @bordoni @moraleida: Update this to Pigeon when it's ready.
		return wp_mail( $to, wp_specialchars_decode( $subject ), $message, $headers, $attachments );
	}
}
