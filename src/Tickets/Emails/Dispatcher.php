<?php
/**
 * Email Dispatcher class.
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use TEC\Common\StellarWP\Shepherd\Tasks\Email;
use function TEC\Common\StellarWP\Shepherd\shepherd;

/**
 * Class Dispatcher.
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Emails
 */
class Dispatcher {

	/**
	 * Whether this dispatcher was used.
	 *
	 * @since 5.6.0
	 *
	 * @var bool
	 */
	protected bool $used = false;

	/**
	 * Stores the Email used in this Dispatcher.
	 *
	 * @since 5.6.0
	 *
	 * @var Email_Abstract|null
	 */
	protected ?Email_Abstract $email = null;

	/**
	 * Stores the all the headers used on the email.
	 *
	 * @since 5.6.0
	 *
	 * @var array<string,string>
	 */
	protected array $headers = [];

	/**
	 * Stores the all the attachments used on the email.
	 *
	 * @since 5.6.0
	 *
	 * @var array<string>
	 */
	protected array $attachments = [];

	/**
	 * Stores the contents to be dispatched.
	 *
	 * @since 5.6.0
	 *
	 * @var ?string
	 */
	protected ?string $content = null;

	/**
	 * Stores where we will dispatch to.
	 *
	 * @since 5.6.0
	 *
	 * @var ?string
	 */
	protected ?string $to = null;

	/**
	 * Stores the subject to be dispatched.
	 *
	 * @since 5.6.0
	 *
	 * @var ?string
	 */
	protected ?string $subject = null;

	/**
	 * Sets the Email instance that will be used for this Dispatcher, intentionally this is protected method.
	 * You can only generate a new Dispatcher from the Factory Method.
	 *
	 * @since 5.6.0
	 *
	 * @param Email_Abstract $email
	 */
	protected function set_email( Email_Abstract $email ): void {
		$this->email = $email;
	}

	/**
	 * Gets the email instance for this Dispatcher.
	 *
	 * @since 5.6.0
	 *
	 * @return Email_Abstract|null
	 */
	public function get_email(): ?Email_Abstract {
		return $this->email;
	}

	/**
	 * Prepares the current dispatcher for sending an email.
	 *
	 * @since 5.6.0
	 *
	 * @param Email_Abstract $email Email Instance used to prepare the dispatcher with.
	 */
	protected function prepare_dispatcher( Email_Abstract $email ): void {
		// Enforce text/html content type header.
		$this->add_header( 'Content-Type', 'text/html; charset=utf-8' );
		$this->set_email( $email );

		$from_email = $email->get_from_email();
		$from_name  = $email->get_from_name();

		// Add From name/email to headers if no headers set yet, and we have a valid From email address.
		if ( ! empty( $from_name ) && ! empty( $from_email ) && is_email( $from_email ) ) {
			$from_email = sanitize_email( $from_email );

			$this->add_header( 'From', sprintf(
				'%1$s <%2$s>',
				stripcslashes( $from_name ),
				$from_email
			) );

			$this->add_header( 'Reply-To', $from_email );
		}

		$this->set_to( $email->get_recipient() );
		$this->set_subject( $email->get_subject() );
		$this->set_content( $email->get_content() );
	}

	/**
	 * From a given email type instance generate a new Dispatcher and prepare it for usage.
	 *
	 * @since 5.6.0
	 *
	 * @param Email_Abstract $email
	 *
	 * @return Dispatcher
	 */
	public static function from_email( Email_Abstract $email ): Dispatcher {
		// Generate a new dispatcher every time.
		$dispatcher = tribe( static::class );

		// Prepare the dispatcher to send an email.
		$dispatcher->prepare_dispatcher( $email );

		/**
		 * Allows modifications of the Email Dispatcher to all Email Types.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param Dispatcher     $dispatcher Which dispatcher instance will be used for the email sent.
		 * @param Email_Abstract $email      Which instance of the email that will be attached to this dispatcher.
		 */
		$dispatcher = apply_filters( 'tec_tickets_emails_dispatcher', $dispatcher, $email );

		$email_slug = $email->slug;

		/**
		 * Allows modifications of the Email Dispatcher specific to this Email Type.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param Dispatcher     $dispatcher Which dispatcher instance will be used for the email sent.
		 * @param Email_Abstract $email      Which instance of the email that will be attached to this dispatcher.
		 */
		return apply_filters( "tec_tickets_emails_{$email_slug}_dispatcher", $dispatcher, $email );
	}

	/**
	 * Includes a new header to be dispatched.
	 *
	 * @since 5.6.0
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
	 * @since 5.6.0
	 *
	 * @param string $path
	 */
	public function add_attachment( string $path ): void {
		$this->attachments[] = $path;
	}

	/**
	 * Get dispatcher headers.
	 *
	 * @since 5.6.0
	 *
	 * @return array
	 */
	public function get_headers(): array {
		/**
		 * Allow filtering the Dispatcher Email "Headers" for all Email Types.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param array      $headers    The headers.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$headers = apply_filters( 'tec_tickets_emails_dispatcher_headers', $this->headers, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the Dispatcher Email "Headers" for a specific Email Type.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param array      $headers    The headers.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$headers = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_headers", $headers, $this );

		// Enforce as an array
		return (array) $headers;
	}

	/**
	 * Get the formatted headers for this dispatcher.
	 *
	 * @since 5.6.0
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
	 * @since 5.6.0
	 *
	 * @return array
	 */
	public function get_attachments(): array {
		/**
		 * Allow filtering the Dispatcher Email "Attachments" for all Email Types.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param array      $attachments The attachments.
		 * @param Dispatcher $dispatcher  The Dispatcher object for this specific email.
		 */
		$attachments = apply_filters( 'tec_tickets_emails_dispatcher_attachments', $this->attachments, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the Dispatcher Email "Attachments" for a specific Email Type.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param array      $attachments The attachments.
		 * @param Dispatcher $dispatcher  The Dispatcher object for this specific email.
		 */
		$attachments = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_attachments", $attachments, $this );

		return (array) $attachments;
	}

	/**
	 * Get email recipient.
	 *
	 * @since 5.6.0
	 *
	 * @return ?string The email recipient.
	 */
	public function get_to(): ?string {
		/**
		 * Allow filtering the Dispatcher Email "To" for all Email Types.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param string     $to         The email recipient.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$to = apply_filters( 'tec_tickets_emails_dispatcher_to', $this->to, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the Dispatcher Email "To" for a specific Email Type.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param string     $to         The email recipient.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$to = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_to", $to, $this );

		return null === $to ? $to : (string) $to;
	}

	/**
	 * Sets where we will dispatch to.
	 *
	 * @since 5.6.0
	 *
	 * @param string $value
	 */
	public function set_to( string $value ): void {
		$this->to = $value;
	}

	/**
	 * Get the subject of the email.
	 *
	 * @since 5.6.0
	 *
	 * @return ?string
	 */
	public function get_subject(): ?string {
		/**
		 * Allow filtering the Dispatcher Email "Subject" for all Email Types.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param string     $subject    The email subject.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$subject = apply_filters( 'tec_tickets_emails_dispatcher_subject', $this->subject, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the Dispatcher Email "Subject" for a specific Email Type.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param string     $subject    The email subject.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$subject = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_subject", $subject, $this );

		return null === $subject ? $subject : (string) $subject;
	}

	/**
	 * Sets the subject for the dispatcher.
	 *
	 * @since 5.6.0
	 *
	 * @param string $value
	 */
	public function set_subject( string $value ): void {
		$this->subject = $value;
	}

	/**
	 * Get the content of the dispatcher.
	 *
	 * @since 5.6.0
	 *
	 * @return ?string
	 */
	public function get_content(): ?string {
		/**
		 * Allow filtering the Dispatcher Email "Content" for all Email Types.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param string     $content    The email subject.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$content = apply_filters( 'tec_tickets_emails_dispatcher_content', $this->content, $this );

		$email_slug = $this->get_email()->slug;

		/**
		 * Allow filtering the Dispatcher Email "Content" for a specific Email Type.
		 *
		 * Filtering the dispatcher hooks will allow you to modify the values being used for this instance of the
		 * Dispatcher.
		 *
		 * Each new email send will generate a new Dispatcher to avoid sending the same email multiple times.
		 *
		 * @since 5.6.0
		 *
		 * @param string     $content    The email subject.
		 * @param Dispatcher $dispatcher The Dispatcher object for this specific email.
		 */
		$content = apply_filters( "tec_tickets_emails_dispatcher_{$email_slug}_content", $content, $this );

		return null === $content ? $content : (string) $content;
	}

	/**
	 * Sets the content for the dispatcher.
	 *
	 * @since 5.6.0
	 *
	 * @param string $value
	 */
	public function set_content( string $value ): void {
		$this->content = $value;
	}

	/**
	 * Determines if at the current state the dispatcher can send an email.
	 *
	 * @since 5.6.0
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
	 * @since 5.6.0
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
	 * @since 5.6.0
	 */
	public function dangerously_mark_as_not_used(): void {
		$this->used = false;
	}

	/**
	 * Send an email.
	 *
	 * @since 5.6.0
	 * @since 5.8.3 Decodes the subject before sending the email.
	 * @since 5.9.1 Removes slashes from the subject before sending the email.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public function send(): bool {
		if ( ! $this->can_send() ) {
			return false;
		}

		// Handle any encoded characters or slashes in the subject.
		$subject = wp_unslash( wp_specialchars_decode( $this->get_subject() ) );

		$actions_to_offload_to_shepherd = [
			'tec_tickets_commerce_order_status_flag_send_email_purchase_receipt',
			'tec_tickets_commerce_order_status_flag_send_email_completed_order',
		];

		if ( in_array( current_action(), $actions_to_offload_to_shepherd, true ) ) {
			shepherd()->dispatch(
				new Email(
					$this->get_to(),
					$subject,
					$this->get_content(),
					$this->get_headers_formatted(),
					$this->get_attachments()
				)
			);

			$sent = true;
		} else {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
			$sent = (bool) wp_mail(
				$this->get_to(),
				$subject,
				$this->get_content(),
				$this->get_headers_formatted(),
				$this->get_attachments()
			);
		}

		$this->used = true;

		return $sent;
	}
}
