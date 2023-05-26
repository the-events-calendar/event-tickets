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
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $slug;

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
	 * Array holding all the dynamic values attached to the object.
	 *
	 * @since 5.5.10
	 *
	 * @var array<string, mixed> An array holding the dynamic values set to this model.
	 */
	protected $data = [];

	/**
	 * Handles the hooking of a given email to the correct actions in WP.
	 *
	 * @since 5.5.9
	 */
	public function hook() {
		$default_placeholders = [
			'{site_title}'   => $this->get_blogname(),
			'{site_address}' => wp_parse_url( home_url(), PHP_URL_HOST ),
			'{site_url}'     => wp_parse_url( home_url(), PHP_URL_HOST ),
		];

		$this->set_placeholders( $default_placeholders );
	}

	/**
	 * Get default email subject.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	abstract public function get_default_subject(): string;

	/**
	 * Get email "to".
	 *
	 * @since 5.5.11
	 *
	 * @return string
	 */
	abstract public function get_to(): string;

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
	 * @since 5.5.10
	 *
	 * @return string
	 */
	abstract public function get_default_heading(): string;

	/**
	 * Get the settings fields for the email.
	 *
	 * @since 5.5.10
	 *
	 * @return array
	 */
	abstract public function get_settings_fields(): array;

	/**
	 * Get default preview context.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email preview context.
	 */
	abstract public function get_default_preview_context( $args = [] ): array;

	/**
	 * Get the default template context.
	 *
	 * @since 5.5.11
	 *
	 * @return string The email template context.
	 */
	abstract public function get_default_template_context(): array;

	/**
	 * Get email content.
	 *
	 * @since 5.5.10
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email content.
	 */
	abstract public function get_content( $args ): string;

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
		 * @param array          $from_email The "from" email.
		 * @param string         $id         The email ID.
		 * @param Email_Abstract $this       The email object.
		 */
		$from_email = apply_filters( 'tec_tickets_emails_from_email', $from_email, $this->id, $this );

		/**
		 * Filter the from email for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $from_email The "from" email.
		 * @param string         $id         The email ID.
		 * @param Email_Abstract $this       The email object.
		 */
		$from_email = apply_filters( "tec_tickets_emails_{$this->slug}_from_email", $from_email, $this->id, $this );

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
		 * @param array          $from_email The "from" name.
		 * @param string         $id         The email ID.
		 * @param Email_Abstract $this       The email object.
		 */
		$from_name = apply_filters( 'tec_tickets_emails_from_name', $from_name, $this->id, $this );

		/**
		 * Filter the from name for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $from_email The "from" name.
		 * @param string         $id         The email ID.
		 * @param Email_Abstract $this       The email object.
		 */
		$from_name = apply_filters( "tec_tickets_emails_{$this->slug}_from_name", $from_name, $this->id, $this );

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
		 * @param array          $headers The headers.
		 * @param string         $id      The email ID.
		 * @param Email_Abstract $this    The email object.
		 */
		$headers = apply_filters( 'tec_tickets_emails_headers', $headers, $this->id, $this );

		/**
		 * Filter the headers for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $headers The headers.
		 * @param string         $id      The email ID.
		 * @param Email_Abstract $this    The email object.
		 */
		$headers = apply_filters( "tec_tickets_emails_{$this->slug}_headers", $headers, $this->id, $this );

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
		 * @param array          $attachments The attachments.
		 * @param string         $id          The email ID.
		 * @param Email_Abstract $this        The email object.
		 */
		$attachments = apply_filters( 'tec_tickets_emails_attachments', $attachments, $this->id, $this );

		/**
		 * Filter the attachments for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $attachments The attachments.
		 * @param string         $id          The email ID.
		 * @param Email_Abstract $this        The email object.
		 */
		$attachments = apply_filters( "tec_tickets_emails_{$this->slug}_attachments", $attachments, $this->id, $this );

		return $attachments;
	}

	/**
	 * Set email placeholders.
	 *
	 * @since 5.5.10
	 *
	 * @param array $placeholders the placeholders to set.
	 *
	 * @return string
	 */
	public function set_placeholders( array $placeholders = [] ): array {
		$this->placeholders = array_merge(
			$placeholders,
			$this->get_placeholders()
		);

		return $this->placeholders;
	}

	/**
	 * Get email placeholders.
	 *
	 * @since 5.5.9
	 *
	 * @return array
	 */
	public function get_placeholders(): array {
		/**
		 * Filter the placeholders.
		 *
		 * @since 5.5.9
		 *
		 * @param array          $placeholders The placeholders.
		 * @param string         $id           The email ID.
		 * @param Email_Abstract $this         The email object.
		 */
		$placeholders = apply_filters( 'tec_tickets_emails_placeholders', $this->placeholders, $this->id, $this );

		/**
		 * Filter the placeholders for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $placeholders The placeholders.
		 * @param string         $id           The email ID.
		 * @param Email_Abstract $this         The email object.
		 */
		$placeholders = apply_filters( "tec_tickets_emails_{$this->slug}_placeholders", $placeholders, $this->id, $this );

		return $placeholders;
	}

	/**
	 * Format email string.
	 *
	 * @param mixed $string Text to replace placeholders in.
	 * @return string
	 */
	public function format_string( $string ): string {
		$placeholders = $this->get_placeholders();
		$find         = array_keys( $placeholders );
		$replace      = array_values( $placeholders );

		/**
		 * Filter the formatted email string.
		 *
		 * @since 5.5.9
		 *
		 * @param string         $string The formatted string.
		 * @param string         $id     The email id.
		 * @param Email_Abstract $this   The email object.
		 */
		return apply_filters( 'tec_tickets_emails_format_string', str_replace( $find, $replace, $string ), $this->id, $this );
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
	 * @since 5.5.10
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
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get setting option key.
	 *
	 * @since 5.5.10
	 *
	 * @param string $option The option name.
	 *
	 * @return string
	 */
	public function get_option_key( $option ): string {
		return "tec-tickets-emails-{$this->slug}-{$option}";
	}

	/**
	 * Checks if this email is enabled.
	 *
	 * @since 5.5.10
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$option_key = $this->get_option_key( 'enabled' );
		return tribe_is_truthy( tribe_get_option( $option_key, true ) );
	}

	/**
	 * Get email recipient.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email recipient.
	 */
	public function get_recipient(): string {
		$recipient = $this->recipient;

		if ( empty( $recipient ) ) {
			$option_key = $this->get_option_key( 'recipient' );
			$recipient  = tribe_get_option( $option_key, $this->get_default_recipient() );
		}

		/**
		 * Allow filtering the email recipient globally.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $recipient  The email recipient.
		 * @param string         $id         The email id.
		 * @param string         $template   Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$recipient = apply_filters( 'tec_tickets_emails_recipient', $recipient, $this->id, $this->template, $this );

		/**
		 * Allow filtering the email recipient for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $recipient  The email recipient.
		 * @param string         $id         The email id.
		 * @param string         $template   Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$recipient = apply_filters( "tec_tickets_emails_{$this->slug}_recipient", $recipient, $this->id, $this->template, $this );

		return $recipient;
	}

	/**
	 * Get default recipient.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_recipient(): string {
		return '';
	}

	/**
	 * Get the subject of the email.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_subject(): string {
		$option_key = $this->get_option_key( 'subject' );
		$subject    = tribe_get_option( $option_key, $this->get_default_subject() );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the email subject globally.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $subject  The email subject.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$subject = apply_filters( 'tec_tickets_emails_subject', $subject, $this->id, $this->template, $this );

		/**
		 * Allow filtering the email subject.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $subject  The email subject.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$subject = apply_filters( "tec_tickets_emails_{$this->slug}_subject", $subject, $this->id, $this->template, $this );

		return $this->format_string( $subject );
	}

	/**
	 * Get email heading.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email heading.
	 */
	public function get_heading(): string {
		$option_key = $this->get_option_key( 'heading' );
		$heading    = tribe_get_option( $option_key, $this->get_default_heading() );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the email heading globally.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $heading  The email heading.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$heading = apply_filters( 'tec_tickets_emails_heading', $heading, $this->id, $this->template, $this );

		/**
		 * Allow filtering the email heading for Completed Order.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $heading  The email heading.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$heading = apply_filters( "tec_tickets_emails_{$this->slug}_heading", $heading, $this->id, $this->template, $this );

		return $this->format_string( $heading );
	}

	/**
	 * Get additional content.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email heading.
	 */
	public function get_additional_content(): string {
		$option_key = $this->get_option_key( 'add-content' );
		$content    = tribe_get_option( $option_key, $this->get_default_additional_content() );

		// Convert linebreaks into paragraphs.
		$content = wpautop( $content );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the email heading globally.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $content  The email heading.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$content = apply_filters( 'tec_tickets_emails_additional_content', $content, $this->id, $this->template, $this );

		/**
		 * Allow filtering the email heading for Completed Order.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $content  The email heading.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$content = apply_filters( "tec_tickets_emails_{$this->slug}_additional_content", $content, $this->id, $this->template, $this );

		return $this->format_string( $content );
	}

	/**
	 * Get and filter email settings.
	 *
	 * @since 5.5.10
	 *
	 * @return array
	 */
	public function get_settings(): array {

		$settings = $this->get_settings_fields();

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna

		/**
		 * Allow filtering the settings globally.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $settings The settings array.
		 * @param string         $id       Email ID.
		 * @param Email_Abstract $this     The email object.
		 */
		$settings = apply_filters( 'tec_tickets_emails_settings', $settings, $this->id, $this );

		/**
		 * Allow filtering the settings for this email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $settings The settings array.
		 * @param string         $id       Email ID.
		 * @param Email_Abstract $this     The email object.
		 */
		$settings = apply_filters( "tec_tickets_emails_{$this->slug}_settings", $settings, $this->id, $this );

		return $settings;
	}

	/**
	 * Get template context for email.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 * @return array $args The modified arguments
	 */
	public function get_template_context( $args = [] ): array {
		$defaults = $this->get_default_template_context();

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Allow filtering the template context globally.
		 *
		 * @since 5.5.11
		 *
		 * @param array          $args     The email arguments.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( 'tec_tickets_emails_template_args', $args, $this->id, $this->template, $this );

		/**
		 * Allow filtering the template context.
		 *
		* @since 5.5.11
		 *
		 * @param array          $args     The email arguments.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( "tec_tickets_emails_{$this->slug}_template_args", $args, $this->id, $this->template, $this );

		return $args;
	}

	/**
	 * Get template preview context for email.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 * @return array $args The modified arguments
	 */
	public function get_preview_context( $args = [] ): array {
		$defaults = $this->get_default_preview_context();

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Allow filtering the template preview context globally.
		 *
		 * @since 5.5.11
		 *
		 * @param array          $args     The email preview arguments.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( 'tec_tickets_emails_preview_args', $args, $this->id, $this->template, $this );

		/**
		 * Allow filtering the template context.
		 *
		* @since 5.5.11
		 *
		 * @param array          $args     The email arguments.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( "tec_tickets_emails_{$this->slug}_preview_args", $args, $this->id, $this->template, $this );

		return $args;
	}

	/**
	 * Set a value to a dynamic property.
	 *
	 * @since 5.5.10
	 *
	 * @param string $name  The name of the property.
	 * @param mixed  $value The value of the property.
	 */
	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	/**
	 * Getter to access dynamic properties.
	 *
	 * @since 5.5.10
	 *
	 * @param string $name The name of the property.
	 *
	 * @return mixed|null null if the value does not exists mixed otherwise the the value to the dynamic property.
	 */
	public function __get( $name ) {

		if ( array_key_exists( $name, $this->data ) ) {
			// Try to find a method on this instance, for example `get_subject()`.
			$method = 'get_' . strtolower( $name );

			if ( method_exists( $this, $method ) ) {
				return $this->{$method}();
			}

			return $this->data[ $name ];
		}

		return null;
	}

	/**
	 * Get the `post_type` data for this email.
	 *
	 * @since 5.5.10
	 *
	 * @return array
	 */
	public function get_post_type_data(): array {
		$data = [
			'slug'     => $this->slug,
			'title'    => $this->get_title(),
			'template' => $this->template,
			'to'       => $this->get_to(),
		];

		return $data;
	}
}
