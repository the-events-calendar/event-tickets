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
	 * Determined if this dispatcher was used.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected bool $used = false;

	/**
	 * Stores the Email used in this Dispatcher.
	 *
	 * @since TBD
	 *
	 * @var Email_Abstract|null
	 */
	protected ?Email_Abstract $email = null;

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
	 * @var ?string
	 */
	protected ?string $content = null;

	/**
	 * Stores where we will dispatch to.
	 *
	 * @since TBD
	 *
	 * @var ?string
	 */
	protected ?string $to = null;

	/**
	 * Stores the subject to be dispatched.
	 *
	 * @since TBD
	 *
	 * @var ?string
	 */
	protected ?string $subject = null;

	/**
	 * Sets the Email instance that will be used for this Dispatcher, intentionally this is protected method.
	 * You can only generate a new Dispatcher from the Factory Method.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email
	 */
	protected function set_email( Email_Abstract $email ): void {
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
	 * Prepares the dispatcher for this Email.
	 *
	 * @since TBD
	 *
	 * @param Dispatcher $dispatcher
	 *
	 * @return Dispatcher
	 */
	protected function prepare_dispatcher( Dispatcher $dispatcher ): Dispatcher {
		// Enforce text/html content type header.
		$dispatcher->add_header( 'Content-Type', 'text/html; charset=utf-8' );

		$email = $dispatcher->get_email();

		if ( ! $email ) {
			return $dispatcher;
		}

		$from_email = $email->get_from_email();
		$from_name  = $email->get_from_name();

		// Add From name/email to headers if no headers set yet, and we have a valid From email address.
		if ( ! empty( $from_name ) && ! empty( $from_email ) && is_email( $from_email ) ) {
			$from_email = sanitize_email( $from_email );

			$dispatcher->add_header( 'From', sprintf(
				'%1$s <%2$s>',
				stripcslashes( $from_name ),
				$from_email
			) );

			$dispatcher->add_header( 'Reply-To', $from_email );
		}

		$dispatcher->set_to( $email->get_recipient() );
		$dispatcher->set_subject( $email->get_subject() );
		$dispatcher->set_content( $email->get_content() );

		return $dispatcher;
	}

	/**
	 * From a given email type instance generate a new Dispatcher and prepare it for usage.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email
	 *
	 * @return Dispatcher
	 */
	public static function from_email( Email_Abstract $email ): Dispatcher {
		// Generate a new dispatcher every time.
		$dispatcher = tribe( static::class );

		// Associate this email to the dispatcher.
		$dispatcher->set_email( $email );

		// Prepare the dispatcher to send an email.
		$dispatcher = $dispatcher->prepare_dispatcher( $dispatcher );

		/**
		 * Allows modifications of the Email Dispatcher to all Email Types.
		 *
		 * @since TBD
		 *
		 * @param Dispatcher     $dispatcher Which dispatcher instance will be used for the email sent.
		 * @param Email_Abstract $email      Which instance of the email that will be attached to this dispatcher.
		 */
		$dispatcher = apply_filters( 'tec_tickets_emails_dispatcher', $dispatcher, $email );

		$email_slug = $email->slug;

		/**
		 * Allows modifications of the Email Dispatcher specific to this Email Type.
		 *
		 * @since TBD
		 *
		 * @param Dispatcher     $dispatcher Which dispatcher instance will be used for the email sent.
		 * @param Email_Abstract $email      Which instance of the email that will be attached to this dispatcher.
		 */
		return apply_filters( "tec_tickets_emails_{$email_slug}_dispatcher", $dispatcher, $email );
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
	 * @return ?string The email recipient.
	 */
	public function get_to(): ?string {
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

		return null === $to ? $to : (string) $to;
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
	 * @return ?string
	 */
	public function get_subject(): ?string {
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

		return null === $subject ? $subject : (string) $subject;
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
	 * @return ?string
	 */
	public function get_content(): ?string {
		/**
		 * Allow filtering the email content globally.
		 *
		 * @since TBD
		 *
		 * @param string         $content The email subject.
		 * @param Email_Abstract $this    The email object.
		 */
		$content = apply_filters( 'tec_tickets_emails_dispatcher_content', $this->content, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the email content.
		 *
		 * @since TBD
		 *
		 * @param string         $content The email subject.
		 * @param Email_Abstract $this    The email object.
		 */
		$content = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_content", $content, $this );

		return null === $content ? $content : (string) $content;
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
	 * Determines if at the current state the dispatcher can send an email.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	protected function can_send(): bool {
		if ( $this->was_used() ) {
			return false;
		}

		$email = $this->get_email();

		// We cannot send if there is no email instance attached to this dispatcher.
		if ( ! $email ) {
			return false;
		}

		$subject = $this->get_subject();
		if ( null === $subject ) {
			return false;
		}

		$to = $this->get_to();
		if ( null === $to ) {
			return false;
		}

		$content = $this->get_content();
		if ( null === $content ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine if this dispatcher was used.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	protected function was_used(): bool {
		return $this->used;
	}

	/**
	 * Please don't use this method unless you know why you are marking the dispatcher as not used.
	 *
	 * Once a dispatcher was used it should not be re-used, since there is no way to determine for certain that
	 * the wp_mail was actually sent at this moment.
	 *
	 * If you need to make another email using a similar dispatcher please make use of the Email associated with
	 * this dispatcher by `Dispatcher::from_email( $dispatcher->get_email() )->send()` which will generate a new dispatcher
	 * and attempt to send the email.
	 *
	 * @since TBD
	 */
	public function dangerously_mark_as_not_used(): void {
		$this->used = false;
	}

	/**
	 * Send an email.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public function send(): bool {
		if ( ! $this->can_send() ) {
			return false;
		}

		$sent = (bool) wp_mail(
			$this->get_to(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers_formatted(),
			$this->get_attachments()
		);

		$this->used = true;

		return $sent;
	}
}
