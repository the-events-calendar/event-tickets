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
	 * Get email subject.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	abstract public function get_subject(): string;

	/**
	 * Get email title.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	abstract public function get_title(): string;

	/**
	 * Get email heading.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	abstract public function get_heading(): string;

	/**
	 * Get email attachments.
	 *
	 * @since 5.5.9
	 *
	 * @return array
	 */
	abstract public function is_enabled(): bool;

	/**
	 * Get the post type data for the email.
	 *
	 * @since 5.5.9
	 *
	 * @return array
	 */
	abstract public function get_post_type_data(): array;

	/**
	 * Get the settings for the email.
	 *
	 * @since 5.5.9
	 *
	 * @return array
	 */
	abstract public function get_settings(): array;

	/**
	 * Get the email content.
	 *
	 * @since 5.5.9
	 *
	 * @param array $args The arguments.
	 *
	 * @return string
	 */
	abstract public function get_content( $args = [] ): string;

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
	 * Default content to show below email content.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_additional_content(): string {
		$additional_content = '';
		return $this->format_string( $additional_content );
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
}
