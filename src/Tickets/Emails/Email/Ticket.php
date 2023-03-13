<?php
/**
 * Class Ticket
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Commerce\Settings;
use \TEC\Tickets\Emails\Email_Template;

/**
 * Class Ticket
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */
class Ticket extends \TEC\Tickets\Emails\Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public static $id = 'tec_tickets_emails_ticket';

	/**
	 * Email template.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $template = 'ticket';

	/**
	 * Email recipient.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $recipient = 'customer';

	/**
	 * Get email title.
	 *
	 * @since 5.5.9
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html__( 'Ticket Email', 'event-tickets' );
	}

	/**
	 * Get default recipient.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_default_recipient(): string {
		return '{attendee-email}';
	}

	/**
	 * Get default email heading.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return sprintf(
			// Translators: %s Lowercase singular of ticket.
			esc_html__( 'Here\'s your %s, {attendee-name}!', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		);
	}

	/**
	 * Get default email heading for multiple tickets.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_heading_multiple(): string {
		return sprintf(
			// Translators: %s Lowercase plural of tickets.
			esc_html__( 'Here are your %s, {attendee-name}!', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);
	}

	/**
	 * Get heading for multiple tickets.
	 * 
	 * @since TBD
	 * 
	 * @return string
	 */
	public function get_heading_multiple(): string {
		$option_key = $this->get_option_key( 'heading-multiple' );
		$heading = tribe_get_option( $option_key, $this->get_default_heading_multiple() );

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
		$template_name = static::$template;
		$heading = apply_filters( "tec_tickets_emails_{$template_name}_heading_multiple", $heading, self::$id, $this->template );

		/**
		 * Allow filtering the email heading globally.
		 *
		 * @since TBD
		 *
		 * @param string $heading  The email heading.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$heading = apply_filters( 'tec_tickets_emails_heading_multiple', $heading, self::$id, $this->template );

		return $this->format_string( $heading );
	}

	/**
	 * Get default email subject.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		$default_subject = sprintf(
			// Translators: %s - Lowercase singular of tickets.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		);

		// If they already had a subject set in Tickets Commerce, let's make it the default.
		return tribe_get_option( Settings::$option_confirmation_email_subject, $default_subject );
	}

	/**
	 * Get default email subject for multiple tickets.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_subject_multiple() {
		return sprintf(
			// Translators: %s - Lowercase plural of tickets.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);
	}

	/**
	 * Get subject for multiple tickets.
	 * 
	 * @since TBD
	 * 
	 * @return string
	 */
	public function get_subject_multiple(): string {
		$option_key = $this->get_option_key( 'subject-multiple' );
		$subject = tribe_get_option( $option_key, $this->get_default_subject_multiple() );

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
		$template_name = static::$template;
		$subject = apply_filters( "tec_tickets_emails_{$template_name}_subject_multiple", $subject, self::$id, $this->template );

		/**
		 * Allow filtering the email subject globally.
		 *
		 * @since TBD
		 *
		 * @param string $subject  The email heasubjectding.
		 * @param string $id       The email id.
		 * @param string $template Template name.
		 */
		$subject = apply_filters( 'tec_tickets_emails_subject_multiple', $subject, self::$id, $this->template );

		return $this->format_string( $subject );
	}

	/**
	 * Get email settings fields.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_settings_fields(): array {
		return [
			[
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			[
				'type' => 'html',
				'html' => '<h2>' . esc_html__( 'Ticket Email Settings', 'event-tickets' ) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Ticket purchasers will receive an email including their ticket and additional info upon completion of purchase. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {ticket_name} can be used to pull dynamic content from the ticket into your email. Learn more about customizing email templates in our Knowledgebase.' ) . '</p>',
			],
			$this->get_option_key( 'enabled' ) => [
				'type'                => 'toggle',
				'label'               => esc_html__( 'Enabled', 'event-tickets' ),
				'default'             => true,
				'validation_type'     => 'boolean',
			],
			$this->get_option_key( 'subject' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'subject-multiple' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject (multiple)', 'event-tickets' ),
				'default'             => $this->get_default_subject_multiple(),
				'placeholder'         => $this->get_default_subject_multiple(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'heading' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $this->get_default_heading(),
				'placeholder'         => $this->get_default_heading(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'heading-multiple' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading (multiple)', 'event-tickets' ),
				'default'             => $this->get_default_heading_multiple(),
				'placeholder'         => $this->get_default_heading_multiple(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'add-content' ) => [
				'type'                => 'wysiwyg',
				'label'               => esc_html__( 'Additional content', 'event-tickets' ),
				'default'             => '',
				'tooltip'             => esc_html__( 'Additional content will be displayed below the tickets in your email.', 'event-tickets' ),
				'validation_type'     => 'html',
				'settings'        => [
					'media_buttons' => false,
					'quicktags'     => false,
					'editor_height' => 200,
					'buttons'       => [
						'bold',
						'italic',
						'underline',
						'strikethrough',
						'alignleft',
						'aligncenter',
						'alignright',
					],
				],
			],
		];
	}
}
