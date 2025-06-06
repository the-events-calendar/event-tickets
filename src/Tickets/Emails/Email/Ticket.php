<?php
/**
 * Class Ticket
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Email_Template;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets\Emails\JSON_LD\Reservation_Schema;

/**
 * Class Ticket
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */
class Ticket extends Email_Abstract implements Purchase_Confirmation_Email_Interface {

	/**
	 * Email ID.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_ticket';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $slug = 'ticket';

	/**
	 * Email template.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $template = 'ticket';

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
	 * @since 5.5.9
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
	 * @since 5.5.9
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
	 * Get email settings fields.
	 *
	 * @since 5.5.10
	 * @since 5.23.0 Added new classes for settings.
	 *
	 * @return array
	 */
	public function get_settings_fields(): array {
		$kb_link = sprintf(
			'<a href="https://evnt.is/event-tickets-emails" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$email_description = sprintf(
			// Translators: %1$s: Tickets Emails knowledgebase article link.
			esc_html_x( 'Ticket purchasers will receive an email including their ticket and additional info upon completion of purchase. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. %1$s.', 'about Ticket Email', 'event-tickets' ),
			$kb_link
		);

		return [
			'tec-settings-email-template-wrapper_start'   => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__header-block--horizontal">',
			],
			'tec-settings-email-template-header'          => [
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Ticket Email Settings', 'event-tickets' ) . '</h3>',
			],
			'info-box-description'                        => [
				'type' => 'html',
				'html' => '<p class="tec-settings-form__section-description">'
						. $email_description
						. '</p><br/>',
			],
			[
				'type' => 'html',
				'html' => '</div>',
			],
			'tec-settings-email-template-settings-wrapper-start' => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__content-section">',
			],
			'tec-settings-email-template-settings'        => [
				'type' => 'html',
				'html' => '<h3 class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . esc_html__( 'Settings', 'event-tickets' ) . '</h3>',
			],
			'tec-settings-email-template-settings-wrapper-end' => [
				'type' => 'html',
				'html' => '</div>',
			],
			$this->get_option_key( 'enabled' )            => [
				'type'            => 'toggle',
				'label'           => sprintf(
				// Translators: %s - Title of email.
					esc_html__( 'Enable %s', 'event-tickets' ),
					$this->get_title()
				),
				'default'         => true,
				'validation_type' => 'boolean',
			],
			$this->get_option_key( 'subject' )            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'heading' )            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $this->get_default_heading(),
				'placeholder'         => $this->get_default_heading(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'additional-content' ) => [
				'type'            => 'wysiwyg',
				'label'           => esc_html__( 'Additional content', 'event-tickets' ),
				'default'         => '',
				'size'            => 'large',
				'tooltip'         => esc_html__( 'Additional content will be displayed below the tickets in your email.', 'event-tickets' ),
				'validation_type' => 'html',
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
			],

		];
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

		$args['order'] = Preview_Data::get_order();
		$args['tickets'] = Preview_Data::get_tickets();
		$args['heading'] = $this->get_heading();

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
			'post_id'            => $this->get( 'post_id' ),
			'post'               => get_post( $this->get( 'post_id' ) ),
			'tickets'            => $this->get( 'tickets' ),
			'additional_content' => $this->get_additional_content(),
			'json_ld'            => Reservation_Schema::build_from_email( $this ),
		];

		return $defaults;
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

		if ( ! empty( $tickets[0]['purchaser_name'] ) ) {
			$placeholders['{purchaser_name}'] = $tickets[0]['purchaser_name'];
		}

		if ( ! empty( $tickets[0]['purchaser_email'] ) ) {
			$placeholders['{purchaser_email}'] = $tickets[0]['purchaser_email'];
		}

		$this->set_placeholders( $placeholders );

		return Dispatcher::from_email( $this )->send();
	}
}
