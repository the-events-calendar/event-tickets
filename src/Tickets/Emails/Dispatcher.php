<?php
/**
 * Email Dispatcher class.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

/**
 * Class Dispatcher.
 *
 * @since   TBD
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
	 * Stores the all the headers used on the email.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected array $headers = [];

	/**
	 * Stores the all the attachments used on the email.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	protected array $attachments = [];

	/**
	 * Stores the contents to be dispatched.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $content;

	/**
	 * Stores where we will dispatch to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $to;

	/**
	 * Stores the subject to be dispatched.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $subject;

	/**
	 * Sets the Email instance that will be used for this Dispatcher.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email
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
	 * Includes a new header to be dispatched.
	 *
	 * @since TBD
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function add_header( string $key, string $value ): void {
		$this->headers[ $key ] = $value;
	}

	/**
	 * Includes a new attachment to be dispatched.
	 *
	 * @since TBD
	 *
	 * @param string $path
	 */
	public function add_attachment( string $path ): void {
		$this->attachments[] = $path;
	}

	/**
	 * Get dispatcher headers.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_headers(): array {
		/**
		 * Filter the headers for all dispatchers.
		 *
		 * @since TBD
		 *
		 * @param array          $headers The headers.
		 * @param string         $id      The email ID.
		 * @param Email_Abstract $this    The email object.
		 */
		$headers = apply_filters( 'tec_tickets_emails_dispatcher_headers', $this->headers, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Filter the headers for the particular email using this dispatcher.
		 *
		 * @since TBD
		 *
		 * @param array          $headers The headers.
		 * @param string         $id      The email ID.
		 * @param Email_Abstract $this    The email object.
		 */
		$headers = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_headers", $headers, $this );

		// Enforce as an array
		return (array) $headers;
	}

	/**
	 * Get the formatted headers for this dispatcher.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_headers_formatted(): array {
		$original_headers = $this->get_headers();
		$headers          = [];

		foreach ( $original_headers as $key => $value ) {
			$headers[] = "{$key}: {$value}";
		}

		return $headers;
	}

	/**
	 * Get email attachments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_attachments(): array {
		/**
		 * Filter the attachments.
		 *
		 * @since TBD
		 *
		 * @param array          $attachments The attachments.
		 * @param Email_Abstract $this        The email object.
		 */
		$attachments = apply_filters( 'tec_tickets_emails_dispatcher_attachments', $this->attachments, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Filter the attachments for the particular email.
		 *
		 * @since TBD
		 *
		 * @param array          $attachments The attachments.
		 * @param Email_Abstract $this        The email object.
		 */
		$attachments = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_attachments", $attachments, $this );

		return (array) $attachments;
	}

	/**
	 * Get email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The email recipient.
	 */
	public function get_to(): string {
		/**
		 * Allow filtering the email recipient globally.
		 *
		 * @since TBD
		 *
		 * @param string         $to   The email recipient.
		 * @param Email_Abstract $this The email object.
		 */
		$to = apply_filters( 'tec_tickets_emails_dispatcher_to', $this->to, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the email recipient for the particular email.
		 *
		 * @since TBD
		 *
		 * @param string         $to   The email recipient.
		 * @param Email_Abstract $this The email object.
		 */
		$to = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_to", $to, $this );

		return (string) $to;
	}

	/**
	 * Sets where we will dispatch to.
	 *
	 * @since TBD
	 *
	 * @param string $value
	 */
	public function set_to( string $value ): void {
		$this->to = $value;
	}

	/**
	 * Get the subject of the email.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_subject(): string {
		/**
		 * Allow filtering the email subject globally.
		 *
		 * @since TBD
		 *
		 * @param string         $subject  The email subject.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$subject = apply_filters( 'tec_tickets_emails_dispatcher_subject', $this->subject, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the email subject.
		 *
		 * @since TBD
		 *
		 * @param string         $subject  The email subject.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$subject = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_subject", $subject, $this );

		return (string) $subject;
	}

	/**
	 * Sets the subject for the dispatcher.
	 *
	 * @since TBD
	 *
	 * @param string $value
	 */
	public function set_subject( string $value ): void {
		$this->subject = $value;
	}

	/**
	 * Get the content of the dispatcher.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_content(): string {
		/**
		 * Allow filtering the email content globally.
		 *
		 * @since TBD
		 *
		 * @param string         $content  The email subject.
		 * @param Email_Abstract $this     The email object.
		 */
		$content = apply_filters( 'tec_tickets_emails_dispatcher_content', $this->content, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the email content.
		 *
		 * @since TBD
		 *
		 * @param string         $content  The email subject.
		 * @param Email_Abstract $this     The email object.
		 */
		$content = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_content", $content, $this );

		return (string) $content;
	}

	/**
	 * Sets the content for the dispatcher.
	 *
	 * @since TBD
	 *
	 * @param string $value
	 */
	public function set_content( string $value ): void {
		$this->content = $value;
	}

	/**
	 * Send an email.
	 *
	 * @since TBD
	 *
	 * @return bool success True if the email was sent.
	 */
	public function send(): bool {
		$email = $this->get_email();

		// We cannot send if there is no email instance attached to this dispatcher.
		if ( ! $email ) {
			return false;
		}

		return wp_mail(
			$this->get_to(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers_formatted(),
			$this->get_attachments()
		);
	}
}
