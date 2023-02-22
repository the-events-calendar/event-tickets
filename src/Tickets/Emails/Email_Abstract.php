<?php
/**
 * Tickets Emails Email abstract class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

/**
 * Class Email_Abstract.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
abstract class Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Email title.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Email subject.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $placeholders = [];

	/**
	 * Handles the hooking of a given email to the correct actions in WP.
	 *
	 * @since TBD
	 */
	abstract public function hook();

	/**
	 * Get email subject.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_subject(): string;

	/**
	 * Get email heading.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_heading(): string;

	/**
	 * Get email attachments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract public function is_enabled(): bool;

	/**
	 * Get the post type data for the email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract public function get_post_type_data(): array;

	/**
	 * Get the settings for the email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract public function get_settings(): array;

	/**
	 * Get the "From" email.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_from_email(): string;

	/**
	 * Get the "From" name.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_from_name(): string;

	/**
	 * Get the email content.
	 *
	 * @since TBD
	 *
	 * @param $args The arguments.
	 *
	 * @return string
	 */
	abstract public function get_content( $args = [] ): string;

	/**
	 * Get email headers.
	 *
	 * @since TBD
	 *
	 * @param array $headers The email headers.
	 *
	 * @return string
	 */
	public function get_headers( $headers = [] ) {
		$from_email = $this->get_from_email();
		$from_name  = $this->get_from_name();

		// Enforce headers array.
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\r\n", $headers );
		}

		// Add From name/email to headers if no headers set yet and we have a valid From email address.
		if ( empty( $headers ) && ! empty( $from_name ) && ! empty( $from_email ) && is_email( $from_email ) ) {
			$from_email = filter_var( $from_email, FILTER_SANITIZE_EMAIL );

			$headers[] = sprintf(
				'From: %1$s <%2$s>',
				stripcslashes( $from_name ),
				$from_email
			);

			$headers[] = sprintf(
				'Reply-To: %s',
				$from_email
			);
		}

		// Enforce text/html content type header.
		if ( ! in_array( 'Content-type: text/html', $headers, true ) || ! in_array( 'Content-type: text/html; charset=utf-8', $headers, true ) ) {
			$headers[] = 'Content-type: text/html; charset=utf-8';
		}

		/**
		 * Filter the headers.
		 *
		 * @since TBD
		 *
		 * @param array $headers The headers.
		 * @param string $id The email ID.
		 */
		$headers = apply_filters( 'tec_tickets_emails_headers_' . $this->id, $headers, $this->id );

		return $headers;
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_additional_content() {
		return '';
	}

	/**
	 * Get email attachments.
	 *
	 * @since TBD
	 *
	 * @param array $attachments
	 *
	 * @return array
	 */
	public function get_attachments( $attachments = [] ) {

		/**
		 * Filter the attachments.
		 *
		 * @since TBD
		 *
		 * @param array $attachments The attachments.
		 * @param string $id The email ID.
		 */
		$attachments = apply_filters( 'tec_tickets_emails_attachments_' . $this->id, $attachments, $this->id );

		return $attachments;
	}
}
