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
	 * @return bool Whether the email was sent successfully.
	 */
	public function send(): bool {
		$email = $this->get_email();

		// We cannot send if there is no email instance attached to this dispatcher.
		if ( ! $email ) {
			return false;
		}

		$to          = $email->get_recipient();
		$subject     = $email->get_subject();
		$message     = $email->get_content();
		$headers     = $email->get_headers();
		$attachments = $email->get_attachments();

		return wp_mail( $to, wp_specialchars_decode( $subject ), $message, $headers, $attachments );
	}
}
