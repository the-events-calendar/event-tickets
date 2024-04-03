<?php
/**
 * Tickets Emails Email abstract class.
 *
 * @since   5.5.9
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use WP_Post;
use WP_Error;

use TEC\Tickets\Emails\Admin\Emails_Tab;
use TEC\Tickets\Emails\Admin\Settings as Emails_Settings;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use Tribe__Utils__Array as Arr;

/**
 * Class Email_Abstract.
 *
 * @since   5.5.9
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
		$home_url  = home_url();
		$url_parts = wp_parse_url( $home_url );

		$default_placeholders = [
			'{site_title}'   => $this->get_blogname(),
			'{site_address}' => $url_parts['host'] . ( ! empty( $url_parts['path'] ) ? $url_parts['path'] : '' ),
			'{site_url}'     => $home_url,
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
	 * @return array<string,mixed> The email preview context.
	 */
	abstract public function get_default_preview_context( $args = [] ): array;

	/**
	 * Get the default template context.
	 *
	 * @since 5.5.11
	 *
	 * @return array The email template context.
	 */
	abstract public function get_default_template_context(): array;

	/**
	 * Get email content.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email content.
	 */
	public function get_content(): string {
		$is_preview = tribe_is_truthy( $this->get( 'is_preview', false ) );
		$args       = $this->get_template_context( $this->data );

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( $is_preview );

		return $this->format_string( $email_template->get_html( $this->template, $args ) );
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

		return wp_specialchars_decode( $from_name );
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
			$this->get_placeholders(),
			$placeholders
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
	 *
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
	 * @todo  This doesnt belong on the abstracts, it's more like a template helper.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_blogname(): string {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Default content to show below email content.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_additional_content(): string {
		return '';
	}

	/**
	 * Get edit URL.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_edit_url(): string {
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
	 * @return ?string The email recipient.
	 */
	public function get_recipient(): ?string {
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
		 * @param string         $recipient The email recipient.
		 * @param string         $id        The email id.
		 * @param string         $template  Template name.
		 * @param Email_Abstract $this      The email object.
		 */
		$recipient = apply_filters( 'tec_tickets_emails_recipient', $recipient, $this->id, $this->template, $this );

		/**
		 * Allow filtering the email recipient for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $recipient The email recipient.
		 * @param string         $id        The email id.
		 * @param string         $template  Template name.
		 * @param Email_Abstract $this      The email object.
		 */
		$recipient = apply_filters( "tec_tickets_emails_{$this->slug}_recipient", $recipient, $this->id, $this->template, $this );

		return $recipient;
	}

	/**
	 * Get the subject of the email.
	 *
	 * @since 5.5.10
	 *
	 * @return ?string
	 */
	public function get_subject(): ?string {
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
	 * Get email heading.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email heading.
	 */
	public function get_heading(): string {
		$option_key = $this->get_option_key( 'heading' );
		$heading    = tribe_get_option( $option_key, $this->get_default_heading() );
		$heading    = stripslashes( $heading );

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
		$option_key = $this->get_option_key( 'additional-content' );
		$content    = wp_unslash( tribe_get_option( $option_key, $this->get_default_additional_content() ) );

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
	 *
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
	 *
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
	 * @since 5.6.0
	 *
	 * @param string|array $name  The name of the property.
	 * @param mixed        $value The value of the property.
	 */
	public function set( $name, $value ) {
		$this->data = Arr::set( $this->data, $name, $value );
	}

	/**
	 * Getter to access dynamic properties.
	 *
	 * @since 5.5.10
	 *
	 * @param string|array $name The name of the property.
	 *
	 * @return mixed|null The value of the passed property. Null if the value does not exist.
	 */
	public function get( $name, $default = null ) {
		return Arr::get( $this->data, $name, $default );
	}
}
