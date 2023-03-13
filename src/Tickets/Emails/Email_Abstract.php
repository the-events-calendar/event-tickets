<?php
/**
 * Tickets Emails Email abstract class.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use TEC\Tickets\Emails\Admin\Emails_Tab;
use WP_Post;
use TEC\Tickets\Emails\Admin\Settings as Emails_Settings;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;

/**
 * Class Email_Abstract.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */
abstract class Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Email template filename.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Email recipient.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $recipient;

	/**
	 * Email title.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Email version number.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Email subject.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @since 5.5.9
	 *
	 * @var array
	 */
	protected $placeholders = [];

	/**
	 * Handles the hooking of a given email to the correct actions in WP.
	 *
	 * @since 5.5.9
	 */
	public function hook() {
		$this->placeholders = array_merge(
			[
				'{site_title}'   => $this->get_blogname(),
				'{site_address}' => wp_parse_url( home_url(), PHP_URL_HOST ),
				'{site_url}'     => wp_parse_url( home_url(), PHP_URL_HOST ),
			],
			$this->get_placeholders()
		);
	}

	/**
	 * Get default email subject.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_default_subject(): string;

	/**
	 * Get default recipient.
	 * 
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_default_recipient(): string;

	/**
	 * Get email title.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	abstract public function get_title(): string;

	/**
	 * Get default email heading.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_default_heading(): string;

	/**
	 * Get the settings fields for the email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract public function get_settings_fields(): array;

	/**
	 * Is customer email.
	 * 
	 * @since TBD
	 * 
	 * @return string
	 */
	public function is_customer_email(): bool {
		return in_array( $this->recipient, [ 'customer', 'purchaser' ] );
	}

	/**
	 * Get the "From" email.
	 *
	 * @since 5.5.9
	 *
	 * @return string The "from" email.
	 */
	public function get_from_email(): string {
		$from_email = tribe_get_option( Emails_Settings::$option_sender_email, tribe( Emails_Settings::class )->get_default_sender_email() );

		/**
		 * Filter the from email.
		 *
		 * @since 5.5.9
		 *
		 * @param array $from_email The "from" email.
		 * @param string $id The email ID.
		 */
		$from_email = apply_filters( 'tec_tickets_emails_from_email', $from_email, $this->id );

		return $from_email;
	}

	/**
	 * Get the "From" name.
	 *
	 * @since 5.5.9
	 *
	 * @return string The "from" name.
	 */
	public function get_from_name(): string {
		$from_name = tribe_get_option( Emails_Settings::$option_sender_name, tribe( Emails_Settings::class )->get_default_sender_name() );

		/**
		 * Filter the from name.
		 *
		 * @since 5.5.9
		 *
		 * @param array $from_email The "from" name.
		 * @param string $id The email ID.
		 */
		$from_name = apply_filters( 'tec_tickets_emails_from_name', $from_name, $this->id );

		return $from_name;
	}

	/**
	 * Get email headers.
	 *
	 * @since 5.5.9
	 *
	 * @param array $headers The email headers.
	 *
	 * @return string
	 */
	public function get_headers( $headers = [] ): array {
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
		 * @since 5.5.9
		 *
		 * @param array $headers The headers.
		 * @param string $id The email ID.
		 */
		$headers = apply_filters( 'tec_tickets_emails_headers', $headers, $this->id );

		return $headers;
	}

	/**
	 * Get email attachments.
	 *
	 * @since 5.5.9
	 *
	 * @param array $attachments The attachments.
	 *
	 * @return array
	 */
	public function get_attachments( $attachments = [] ): array {

		/**
		 * Filter the attachments.
		 *
		 * @since 5.5.9
		 *
		 * @param array $attachments The attachments.
		 * @param string $id The email ID.
		 */
		$attachments = apply_filters( 'tec_tickets_emails_attachments', $attachments, $this->id );

		return $attachments;
	}

	/**
	 * Get email placeholders.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_placeholders(): array {
		/**
		 * Filter the placeholders.
		 *
		 * @since 5.5.9
		 *
		 * @param array $placeholders The placeholders.
		 * @param string $id The email ID.
		 */
		$placeholders = apply_filters( 'tec_tickets_emails_placeholders', $this->placeholders, $this->id );

		return $placeholders;
	}

	/**
	 * Format email string.
	 *
	 * @param mixed $string Text to replace placeholders in.
	 * @return string
	 */
	public function format_string( $string ): string {
		$find    = array_keys( $this->placeholders );
		$replace = array_values( $this->placeholders );

		/**
		 * Filter the formatted email string.
		 *
		 * @since 5.5.9
		 *
		 * @param string $string The formatted string.
		 * @param string $id The email id.
		 */
		return apply_filters( 'tec_tickets_emails_format_string', str_replace( $find, $replace, $string ), $this->id );
	}

	/**
	 * Get WordPress blog name.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_blogname(): string {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Default default content to show below email content.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_additional_content(): string {
		return '';
	}

	/**
	 * Get post object of email.
	 *
	 * @since 5.5.9
	 *
	 * @return WP_Post|null;
	 */
	public function get_post() {
		return get_page_by_path( $this->id, OBJECT, Email_Handler::POSTTYPE );
	}

	/**
	 * Get edit URL.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_edit_url() {
		// Force the `emails` tab.
		$args = [
			'tab'     => Emails_Tab::$slug,
			'section' => $this->id,
		];

		// Use the settings page get_url to build the URL.
		return tribe( Plugin_Settings::class )->get_url( $args );
	}

	/**
	 * Get ID.
	 * 
	 * @since TBD
	 * 
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get setting option key.
	 * 
	 * @since TBD
	 * 
	 * @return string
	 */
	public function get_option_key( $option ): string {
		$template_name = $this->template;
		return "tec-tickets-emails-{$template_name}-{$option}";
	}

	/**
	 * Checks if this email is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$option_key = $this->get_option_key( 'emabled' );
		return tribe_is_truthy( tribe_get_option( $option_key, true ) );
	}

	/**
	 * Get email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The email recipient.
	 */
	public function get_recipient(): string {
		$option_key = $this->get_option_key( 'recipient' );
		$recipient = tribe_get_option( $option_key, $this->get_default_recipient() );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the email recipient for Completed Order.
		 *
		 * @since TBD
		 *
		 * @param string $recipient  The email recipient.
		 * @param string $id         The email id.
		 * @param string $template   Template name.
		 */
		$template_name = $this->template;
		$recipient = apply_filters( "tec_tickets_emails_{$template_name}_recipient", $recipient, self::$id, $this->template );

		/**
		 * Allow filtering the email recipient globally.
		 *
		 * @since TBD
		 *
		 * @param string $recipient  The email recipient.
		 * @param string $id         The email id.
		 * @param string $template   Template name.
		 */
		$recipient = apply_filters( 'tec_tickets_emails_recipient', $recipient, self::$id, $this->template );

		return $this->format_string( $recipient );
	}

	/**
	 * Get the subject of the email.
	 * 
	 * @since TBD
	 * 
	 * @return string
	 */
	public function get_subject(): string {
		$option_key = $this->get_option_key( 'subject' );
		$subject = tribe_get_option( $option_key, $this->get_default_subject() );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the email subject.
		 *
		 * @since TBD
		 *
		 * @param string $subject  The email subject.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$template_name = $this->template;
		$subject = apply_filters( "tec_tickets_emails_{$template_name}_subject", $subject, self::$id, $this->template );

		/**
		 * Allow filtering the email subject globally.
		 *
		 * @since TBD
		 *
		 * @param string $subject  The email subject.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$subject = apply_filters( 'tec_tickets_emails_subject', $subject, self::$id, $this->template );

		return $this->format_string( $subject );
	}

	/**
	 * Get email heading.
	 *
	 * @since TBD
	 *
	 * @return string The email heading.
	 */
	public function get_heading(): string {
		$option_key = $this->get_option_key( 'heading' );
		$heading = tribe_get_option( $option_key, $this->get_default_heading() );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the email heading for Completed Order.
		 *
		 * @since TBD
		 *
		 * @param string $heading  The email heading.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$template_name = $this->template;
		$heading = apply_filters( "tec_tickets_emails_{$template_name}_heading", $heading, self::$id, $this->template );

		/**
		 * Allow filtering the email heading globally.
		 *
		 * @since TBD
		 *
		 * @param string $heading  The email heading.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$heading = apply_filters( 'tec_tickets_emails_heading', $heading, self::$id, $this->template );

		return $this->format_string( $heading );
	}

	/**
	 * Get email content.
	 *
	 * @since TBD
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email content.
	 */
	public function get_content( $args = [] ): string {
		// @todo: Parse args, etc.
		$context = ! empty( $args['context'] ) ? $args['context'] : [];

		// @todo: We need to grab the proper information that's going to be sent as context.

		$email_template = tribe( Email_Template::class );

		// @todo @juanfra @codingmusician: we may want to inverse these parameters.
		return $email_template->get_html( $context, $this->template );
	}

	/**
	 * Get additional content.
	 *
	 * @since TBD
	 *
	 * @return string The email heading.
	 */
	public function get_additional_content(): string {
		$option_key = $this->get_option_key( 'add-content' );
		$content = tribe_get_option( $option_key, $this->get_default_additional_content() );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the email heading for Completed Order.
		 *
		 * @since TBD
		 *
		 * @param string $content  The email heading.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$template_name = $this->template;
		$content = apply_filters( "tec_tickets_emails_{$template_name}_additional_content", $content, self::$id, $this->template );

		/**
		 * Allow filtering the email heading globally.
		 *
		 * @since TBD
		 *
		 * @param string $content  The email heading.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$content = apply_filters( 'tec_tickets_emails_additional_content', $content, self::$id, $this->template );

		return $this->format_string( $content );
	}

	/**
	 * Get and filter email settings.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_settings(): array {

		$settings = $this->get_settings_fields();

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the settings for this email.
		 *
		 * @since TBD
		 *
		 * @param array  $settings  The settings array.
		 * @param string $id        Email ID.
		 */
		$template_name = $this->template;
		$settings = apply_filters( "tec_tickets_emails_{$template_name}_settings", $settings, self::$id );

		/**
		 * Allow filtering the settings for this email.
		 *
		 * @since TBD
		 *
		 * @param array  $settings  The settings array.
		 * @param string $id        Email ID.
		 */
		return apply_filters( 'tec_tickets_emails_settings', $settings, self::$id );
	}

	/**
	 * Get the `post_type` data for this email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_post_type_data(): array {
		$data = [
			'slug'      => self::$id,
			'title'     => $this->get_title(),
			'template'  => $this->template,
			'recipient' => $this->recipient,
		];

		return $data;
	}
}
