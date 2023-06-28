<?php
/**
 * Class RSVP
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Commerce\Settings as Settings;
use TEC\Tickets\Emails\Admin\Emails_Tab;
use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Email_Template;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\JSON_LD\Reservation_Schema;

/**
 * Class RSVP
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Emails
 */
class RSVP extends Email_Abstract {

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
			// Translators: %s Lowercase plural of ticket.
			esc_html__( 'Here\'s your %s, {attendee_name}!', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);
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
			// Translators: %s - Lowercase plural of ticket.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);

		// If they already had a subject set in Tickets Commerce, let's make it the default.
		return tribe_get_option( Settings::$option_confirmation_email_subject, $default_subject );
	}

	/**
	 * @inheritDoc
	 */
	public function get_subject(): ?string {
		// Send ticket subject if using ticket settings.
		if ( $this->is_using_ticket_email_settings() ) {
			return tribe( Ticket::class )->get_subject();
		}

		return parent::get_subject();
	}

	/**
	 * Get email settings fields.
	 *
	 * @since 5.5.10
	 *
	 * @return array
	 */
	public function get_settings_fields(): array {
		$kb_link = sprintf(
			'<a href="https://evnt.is/event-tickets-emails" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$email_description = sprintf(
			// Translators: %1$s: RSVP Emails knowledgebase article link.
			esc_html_x( 'Registrants will receive an email including their RSVP info upon registration. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. %1$s.', 'about RSVP email', 'event-tickets' ),
			$kb_link
		);

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
				'html' => '<p>' . $email_description . '</p>',
			],
			$this->get_option_key( 'enabled' ) => [
				'type'                => 'toggle',
				'label'               => sprintf(
					// Translators: %s - Title of email.
					esc_html__( 'Enable %s', 'event-tickets' ),
					$this->get_title()
				),
				'default'             => true,
				'validation_type'     => 'boolean',
			],
			$this->get_option_key( 'use-ticket-email' ) => [
				'type'                => 'toggle',
				'label'               => esc_html__( 'Use Ticket Email', 'event-tickets' ),
				'tooltip'             => esc_html__( 'Use the ticket email settings and template.', 'event-tickets' ),
				'default'             => true,
				'validation_type'     => 'boolean',
				'attributes'          => [
					'id' => $this->get_option_key( 'use-ticket-email' ),
				],
			],
		];

		$add_settings = [
			$this->get_option_key( 'subject' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
				'size'                => 'large',
				'validation_type'     => 'textarea',
				'fieldset_attributes' => [
					'data-depends'                  => '#' . $this->get_option_key( 'use-ticket-email' ),
					'data-condition-is-not-checked' => true,
				],
				'class'               => 'tribe-dependent',
			],
			$this->get_option_key( 'heading' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $this->get_default_heading(),
				'placeholder'         => $this->get_default_heading(),
				'size'                => 'large',
				'validation_type'     => 'textarea',
				'fieldset_attributes' => [
					'data-depends'                  => '#' . $this->get_option_key( 'use-ticket-email' ),
					'data-condition-is-not-checked' => true,
				],
				'class'               => 'tribe-dependent',
			],
			$this->get_option_key( 'additional-content' ) => [
				'type'                => 'wysiwyg',
				'label'               => esc_html__( 'Additional content', 'event-tickets' ),
				'default'             => '',
				'size'                => 'large',
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
						'link',
					],
				],
				'fieldset_attributes' => [
					'data-depends'                  => '#' . $this->get_option_key( 'use-ticket-email' ),
					'data-condition-is-not-checked' => true,
				],
				'class'               => 'tribe-dependent',
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
			'tickets'            => $this->get( 'tickets' ),
			'post_id'            => $this->get( 'post_id' ),
			'post'               => get_post( $this->get( 'post_id' ) ),
			'json_ld'            => Reservation_Schema::build_from_email( $this ),
		];

		if ( $this->is_using_ticket_email_settings() ) {
			$ticket = tribe( Ticket::class );
			$defaults['heading']            = $ticket->get_heading();
			$defaults['additional_content'] = $ticket->get_additional_content();
		}

		return $defaults;
	}

	/**
	 * Check if following the ticket email settings.
	 *
	 * @since 5.6.0
	 *
	 * @return bool
	 */
	public function is_using_ticket_email_settings(): bool {
		// If using the ticket email settings, no need to show the remaining settings.
		return tribe_is_truthy( tribe_get_option( $this->get_option_key( 'use-ticket-email' ), true ) );
	}

	/**
	 * Filters the hidden dependent fields while saving `using_ticket_email` option from being saved.
	 *
	 * @since 5.6.0
	 *
	 * @param array $fields The fields to be saved.
	 * @param string $admin_page The admin page being saved.
	 *
	 * @return array The fields to be saved.
	 */
	public function filter_rsvp_fields_before_saving( array $fields, $admin_page ): array {
		$emails_slug = Emails_Tab::$slug;

		if ( \Tribe\Tickets\Admin\Settings::$settings_page_id !== $admin_page
		     || ! isset( $fields[ $emails_slug ] )
		     || tribe_get_request_var( 'section', false ) !== 'tec_tickets_emails_rsvp'
		     || empty( $_POST )
		) {
			return $fields;
		}

		// If not updating the ticket email settings, no need to filter the fields.
		if ( ! tribe_is_truthy( tribe_get_request_var( 'tec-tickets-emails-rsvp-use-ticket-email' ) ) ) {
			return $fields;
		}

		// filters the hidden fields and toggle type fields.
		$fields[ $emails_slug ] = array_filter(
			$fields[ $emails_slug ],
			function ( $field, $key ) {
				return isset( $_POST[ $key ] ) || $field['type'] === 'toggle';
			},
			ARRAY_FILTER_USE_BOTH
		);

		return $fields;
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

		$tickets = $this->get( 'tickets' );
		$post_id = $this->get( 'post_id' );

		// Bail if there's no tickets or post ID.
		if ( empty( $tickets ) || empty( $post_id ) ) {
			return false;
		}

		$placeholders = [
			'{attendee_name}'  => $tickets[0]['holder_name'],
			'{attendee_email}' => $tickets[0]['holder_email'],
		];

		$this->set_placeholders( $placeholders );

		return Dispatcher::from_email( $this )->send();
	}
}
