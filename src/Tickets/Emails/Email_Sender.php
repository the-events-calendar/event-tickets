<?php
/**
 * Tickets Emails Email Sender class.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

/**
 * Class Email_Sender.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */
class Email_Sender {

	/**
	 * Send an email.
	 *
	 * @since 5.5.9
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
