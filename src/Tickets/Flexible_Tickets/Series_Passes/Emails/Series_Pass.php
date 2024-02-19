<?php
/**
 * Series Pass Email template.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;

use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Series_Pass.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */
class Series_Pass extends Email_Abstract {
	/**
	 * Email ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_series-pass';

	/**
	 * Email slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $slug = 'series-pass';

	/**
	 * Email template.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $template = 'series-pass';

	public function get_to(): string {
		return esc_html__( 'Attendee(s)', 'event-tickets' );
	}

	public function get_settings_fields(): array {
		$kb_link = sprintf(
			'<a href="https://evnt.is/event-tickets-emails" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$email_description = sprintf(
			// Translators: %1$s is the series pass singular uppercase, %2$s is the knowledge base link.
			esc_html_x( '%1$s purchasers will receive an email including their pass and additional info ' .
			            'upon completion of purchase. Customize the content of this specific email using the tools ' .
			            'below. You can also use email placeholders and customize email templates. %2$s.',
				'about Ticket Email',
				'event-tickets'
			),
			tec_tickets_get_series_pass_singular_uppercase(),
			$kb_link
		);

		return [
			[
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			[
				'type' => 'html',
				'html' => '<h2>' . esc_html(
						sprintf(
							// Translators: %s is the series pass singular uppercase.
							_x(
								'%s Email',
								'',
								'event-tickets'
							),
							tec_tickets_get_series_pass_singular_uppercase('series-pass-email-tab-title')
						)
					) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . $email_description . '</p>',
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
			$this->get_option_key( 'include-series-excerpt' ) => [
				'type'    => 'checkbox_bool',
				'label'   => esc_html_x( 'Series excerpt', 'Series pass email settings', 'event-tickets' ),
				'default' => true,
				'tooltip' => esc_html_x( 'Include the series\' excerpt content in Series email', 'Series pass email settings', 'event-tickets' ),
			],
			$this->get_option_key( 'show-events-in-email' ) => [
				'type'    => 'checkbox_bool',
				'label'   => esc_html_x( 'Events in Series', 'Series pass email settings', 'event-tickets' ),
				'default' => true,
				'tooltip' => esc_html_x( 'Show the next five upcoming Series events in email', 'Series pass email settings', 'event-tickets' ),
			]
		];
	}

	/**
	 * Get email title.
	 *
	 * @since 5.5.9
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html(
		// Translators: %s is the series pass singular uppercase.
			sprintf(
				_x(
					'%s Email',
					'Series Pass email title',
					'event-tickets'
				),
				tec_tickets_get_series_pass_singular_uppercase( 'series-pass-email-title' )
			)
		);
	}

	public function get_default_subject(): string {
		return sprintf(
		// Translators: %1$s is "Series Pass", %2$s is the series name placeholder.
			_x(
				'Your %1$s to %2$s',
				'Series Pass email subject',
				'event-tickets'
			),
			tec_tickets_get_series_pass_singular_uppercase( 'series-pass-email-subject' ),
			'{series_name}'
		);

		// @todo what about default subject set in Tickets Commerce?
	}

	public function get_default_heading(): string {
		return sprintf(
		// Translators: %s Lowercase plural of ticket.
			esc_html__( 'Here\'s your %s, {attendee_name}!', 'event-tickets' ),
			tec_tickets_get_series_pass_singular_lowercase()
		);
	}

	public function get_default_preview_context( $args = [] ): array {
		return [];
	}

	public function get_default_template_context(): array {
		return [];
	}
}