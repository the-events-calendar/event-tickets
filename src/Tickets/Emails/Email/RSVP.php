<?php
/**
 * Class RSVP
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Commerce\Settings as Settings;
use \TEC\Tickets\Emails\Email_Template;

/**
 * Class RSVP
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Emails
 */
class RSVP extends \TEC\Tickets\Emails\Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_rsvp';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $slug = 'rsvp';

	/**
	 * Email template.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $template = 'rsvp';

	/**
	 * Get email title.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html__( 'RSVP Email', 'event-tickets' );
	}

	/**
	 * Get email to.
	 *
	 * @since 5.5.11
	 *
	 * @return string The email "to".
	 */
	public function get_to(): string {
		return esc_html__( 'Attendee(s)', 'event-tickets' );
	}

	/**
	 * Get default email heading.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return sprintf(
			// Translators: %s Lowercase singular of ticket.
			esc_html__( 'Here\'s your %s, {attendee_name}!', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		);
	}

	/**
	 * Get default email heading for plural rsvps.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_heading_plural(): string {
		return sprintf(
			// Translators: %s Lowercase plural of tickets.
			esc_html__( 'Here are your %s, {attendee_name}!', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);
	}

	/**
	 * Get heading for plural tickets.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_heading_plural(): string {
		$option_key = $this->get_option_key( 'heading-plural' );
		$heading    = tribe_get_option( $option_key, $this->get_default_heading_plural() );

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
		$heading = apply_filters( 'tec_tickets_emails_heading_plural', $heading, $this->id, $this->template, $this );

		/**
		 * Allow filtering the email heading.
		 *
		 * @since 5.5.10
		 *
		 * @param string         $heading  The email heading.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$heading = apply_filters( "tec_tickets_emails_{$this->slug}_heading_plural", $heading, $this->id, $this->template, $this );

		return $this->format_string( $heading );
	}

	/**
	 * Get default email subject.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		$default_subject = sprintf(
			// Translators: %s - Lowercase singular of ticket.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		);

		// If they already had a subject set in Tickets Commerce, let's make it the default.
		return tribe_get_option( Settings::$option_confirmation_email_subject, $default_subject );
	}

	/**
	 * Get default email subject for plural rsvps.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_subject_plural() {
		return sprintf(
			// Translators: %s - Lowercase plural of tickets.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);
	}

	/**
	 * Get subject for plural rsvps.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_subject_plural(): string {
		$option_key = $this->get_option_key( 'subject-plural' );
		$subject    = tribe_get_option( $option_key, $this->get_default_subject_plural() );

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
		$subject = apply_filters( 'tec_tickets_emails_subject_plural', $subject, $this->id, $this->template, $this );

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
		$subject = apply_filters( "tec_tickets_emails_{$this->slug}_subject_plural", $subject, $this->id, $this->template, $this );

		return $this->format_string( $subject );
	}

	/**
	 * Get email settings fields.
	 *
	 * @since 5.5.10
	 *
	 * @return array
	 */
	public function get_settings_fields(): array {
		$settings = [
			[
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			[
				'type' => 'html',
				'html' => '<h2>' . esc_html__( 'RSVP Email Settings', 'event-tickets' ) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Registrants will receive an email including their RSVP info upon registration. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {rsvp_name} can be used to pull dynamic content from the RSVP into your email. Learn more about customizing email templates in our Knowledgebase.' ) . '</p>',
			],
			$this->get_option_key( 'enabled' ) => [
				'type'                => 'toggle',
				'label'               => esc_html__( 'Enabled', 'event-tickets' ),
				'default'             => true,
				'validation_type'     => 'boolean',
			],
			$this->get_option_key( 'use-ticket-email' ) => [
				'type'                => 'toggle',
				'label'               => esc_html__( 'Use Ticket Email', 'event-tickets' ),
				'placeholder'         => esc_html__( 'Use the ticket email settings and template.', 'event-tickets' ),
				'default'             => true,
				'validation_type'     => 'boolean',
			],
		];

		// If using the ticket email settings, no need to show the remaining settings.
		if ( tribe_is_truthy( tribe_get_option( $this->get_option_key( 'use-ticket-email' ), true ) ) ) {
			return $settings;
		}

		$add_settings = [
			$this->get_option_key( 'subject' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'subject-plural' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject (plural)', 'event-tickets' ),
				'default'             => $this->get_default_subject_plural(),
				'placeholder'         => $this->get_default_subject_plural(),
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
			$this->get_option_key( 'heading-plural' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading (plural)', 'event-tickets' ),
				'default'             => $this->get_default_heading_plural(),
				'placeholder'         => $this->get_default_heading_plural(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'add-content' ) => [
				'type'                => 'wysiwyg',
				'label'               => esc_html__( 'Additional content', 'event-tickets' ),
				'default'             => '',
				'tooltip'             => esc_html__( 'Additional content will be displayed below the RSVP information in your email.', 'event-tickets' ),
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

		return array_merge( $settings, $add_settings );
	}

	/**
	 * Get default preview context for email.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 * @return array $args The modified arguments
	 */
	public function get_default_preview_context( $args = [] ): array {
		$defaults = tribe( Email_Template::class )->get_preview_context( $args );

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Get default template context for email.
	 *
	 * @since 5.5.11
	 *
	 * @return array $args The default arguments
	 */
	public function get_default_template_context(): array {
		$defaults = [
			'email'              => $this,
			'title'              => $this->get_title(),
			'heading'            => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'tickets'            => $this->__get( 'tickets' ),
			'post_id'            => $this->__get( 'post_id' ),
		];

		return $defaults;
	}

	/**
	 * Get email content.
	 *
	 * @since 5.5.10
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email content.
	 */
	public function get_content( $args = [] ): string {
		// @todo: Parse args, etc.
		$is_preview = ! empty( $args['is_preview'] ) ? tribe_is_truthy( $args['is_preview'] ) : false;
		$args       = $this->get_template_context( $args );

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( $is_preview );

		return $email_template->get_html( $this->template, $args );
	}

	/**
	 * Send the email.
	 *
	 * @since 5.5.11
	 *
	 * @return bool Whether the email was sent or not.
	 */
	public function send() {
		$recipient = $this->get_recipient();

		// Bail if there is no email address to send to.
		if ( empty( $recipient ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		$tickets = $this->__get( 'tickets' );
		$post_id = $this->__get( 'post_id' );

		// Bail if there's no tickets or post ID.
		if ( empty( $tickets ) || empty( $post_id ) ) {
			return false;
		}

		$placeholders = [
			'{attendee_name}'  => $tickets[0]['holder_name'],
			'{attendee_email}' => $tickets[0]['holder_email'],
		];

		$this->set_placeholders( $placeholders );

		$subject     = $this->get_subject();
		$content     = $this->get_content();
		$headers     = $this->get_headers();
		$attachments = $this->get_attachments();

		return tribe( \TEC\Tickets\Emails\Email_Sender::class )->send( $recipient, $subject, $content, $headers, $attachments );
	}
}
