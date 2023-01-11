<?php

/**
 * Tickets Emails Settings class
 *
 * @since   5.5.6
 *
 * @package TEC\Tickets\Emails
 *
 */

namespace TEC\Tickets\Emails;

use TEC\Tickets\Commerce\Settings as CommerceSettings;
use Tribe__Template;
use Tribe__Tickets__Main;

class Settings {

	/**
	 * The option key for email sender's name.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_sender_name = 'tec-tickets-emails-sender-name';

	/**
	 * The option key for email sender's email.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_sender_email = 'tec-tickets-emails-sender-email';

	/**
	 * The option key for the email header image url.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_header_image_url = 'tec-tickets-emails-header-image-url';

	/**
	 * The option key for the email header image alignment.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_header_image_alignment = 'tec-tickets-emails-header-image-alignment';

	/**
	 * The option key for the email header background color.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_header_bg_color = 'tec-tickets-emails-header-bg-color';

	/**
	 * The option key for the email ticket background color.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_ticket_bg_color = 'tec-tickets-emails-ticket-bg-color';

	/**
	 * The option key for the email footer content.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_footer_content = 'tec-tickets-emails-footer-content';

	/**
	 * The option key for the email footer credit.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $option_footer_credit = 'tec-tickets-emails-footer-credit';

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since 5.5.6
	 *
	 * @return Tribe__Template
	 */
	public function get_template(): Tribe__Template {
		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/settings/emails' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Adds list of Templates to the Tickets Emails settings tab.
	 *
	 * @since 5.5.6
	 *
	 * @param  array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function add_template_list( array $fields ): array {

		$template = $this->get_template();

		// @todo Replace this with array of actual Message Template objects that do not yet exist.
		$templates = [
			[
				'title'     => 'Ticket Email',
				'enabled'   => true,
				'recipient' => 'Purchaser',
			],
			[
				'title'     => 'RSVP Email',
				'enabled'   => true,
				'recipient' => 'Attendee',
			],
			[
				'title'     => 'Order Notification',
				'enabled'   => false,
				'recipient' => 'Site Admin',
			],
			[
				'title'     => 'Order Failure',
				'enabled'   => true,
				'recipient' => 'Site Admin',
			],
		];

		$new_fields = [
			[
				'type' => 'html',
				'html' => $template->template( 'message-templates', [ 'templates' => $templates ], false ),
			],
		];

		/**
		 * Filter the Tickets Emails Tab Template List
		 *
		 * @since 5.5.6
		 *
		 * @param array  $new_fields  A settings array that includes the template list.
		 */
		$new_fields = apply_filters( 'tec_tickets_emails_settings_template_list', $new_fields );

		return array_merge( $fields, $new_fields );
	}

	/**
	 * Adds Sender Info fields to Tickets Emails settings.
	 *
	 * @since 5.5.6
	 *
	 * @param  [] $fields Current array of Tickets Emails settings fields.
	 *
	 * @return [] $fields Filtered array of Tickets Emails settings fields.
	 */
	public function sender_info_fields( array $fields ): array {
		$new_fields = [
			[
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Sender Information', 'event-tickets' ) . '</h3>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'If fields are empty, sender information will be from the site owner set in WordPress general settings.', 'event-tickets' ) . '</p>',
			],
			static::$option_sender_name  => [
				'type'                => 'text',
				'label'               => esc_html__( 'Sender Name', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => $this->get_sender_name(),
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
			static::$option_sender_email  => [
				'type'                => 'text',
				'label'               => esc_html__( 'Sender Email', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => $this->get_sender_email(),
				'validation_callback' => 'is_string',
				'validation_type'     => 'email',
			],
		];

		/**
		 * Filter the Tickets Emails Sender Info Fields
		 *
		 * @since 5.5.6
		 *
		 * @param array  $new_fields  A settings array that includes the sender info fields.
		 */
		$new_fields = apply_filters( 'tec_tickets_emails_settings_sender_info_fields', $new_fields );

		return array_merge( $fields, $new_fields );
	}

	/**
	 * Get sender name.
	 *
	 * @since 5.5.6
	 *
	 * @return string Sender's name.
	 */
	public function get_sender_name(): string {
		// Get name from settings.
		$name  = tribe_get_option( CommerceSettings::$option_confirmation_email_sender_name );
		if ( ! empty( $name ) ) {
			return $name;
		}
		// If not set, return WordPress User `nicename`.
		$current_user  = get_user_by( 'id', get_current_user_id() );
		return $current_user->user_nicename;
	}

	/**
	 * Get sender email.
	 *
	 * @since 5.5.6
	 *
	 * @return string Sender's email address.
	 */
	public function get_sender_email(): string {
		// Get email from settings.
		$email  = tribe_get_option( CommerceSettings::$option_confirmation_email_sender_email );
		if ( ! empty( $email ) ) {
			return $email;
		}
		// If not set, return WordPress User `email`.
		$current_user  = get_user_by( 'id', get_current_user_id() );
		return $current_user->user_email;
	}

	/**
	 * Adds Sender Info fields to Tickets Emails settings.
	 *
	 * @since 5.5.6
	 *
	 * @param  [] $fields Current array of Tickets Emails settings fields.
	 *
	 * @return [] $fields Filtered array of Tickets Emails settings fields.
	 */
	public function email_styling_fields( array $fields ): array {

		$new_fields = [
			[
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Email Styling', 'event-tickets' ) . '</h3>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Add a logo and customize link colors and footer information to personalize your communications.  If you\'d like more granular control over email styling, you can override the email templates in your theme.  Learn More', 'event-tickets' ) . '</p>',
			],
			static::$option_header_image_url  => [
				'type'                => 'image',
				'label'               => esc_html__( 'Header Image', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'url',
			],
			static::$option_header_image_alignment  => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Image Alignment', 'event-tickets' ),
				'default'         => 'left',
				'validation_type' => 'options',
				'options'         => [
					'left'   => esc_html__( 'Left', 'event-tickets' ),
					'center' => esc_html__( 'Center', 'event-tickets' ),
					'right'  => esc_html__( 'Right', 'event-tickets' ),
				],
			],
			static::$option_header_bg_color  => [
				'type'                => 'color',
				'label'               => esc_html__( 'Header/Footer Background', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '#ffffff',
				'validation_callback' => 'is_string',
				'validation_type'     => 'color',
			],
			static::$option_ticket_bg_color  => [
				'type'                => 'color',
				'label'               => esc_html__( 'Ticket Color', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '#ffffff',
				'validation_callback' => 'is_string',
				'validation_type'     => 'color',
			],
			static::$option_footer_content  => [
				'type'                => 'wysiwyg',
				'label'               => esc_html__( 'Footer Content', 'event-tickets' ),
				'tooltip'             => esc_html__( 'Add custom links and instructions to the bottom of your emails.', 'event-tickets' ),
				'default'             => '',
				'validation_type'     => 'html',
				'settings'            => [
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
				]
			],
			static::$option_footer_credit => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Footer Credit', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Include "Ticket powered by Event Tickets" in the footer', 'event-tickets' ),
				'default'         => true,
				'validation_type' => 'boolean',
			],
		];

		/**
		 * Filter the Tickets Emails Styling Fields
		 *
		 * @since 5.5.6
		 *
		 * @param array  $new_fields  A settings array that includes the styling fields.
		 */
		$new_fields = apply_filters( 'tec_tickets_emails_settings_email_styling_fields', $new_fields );

		return array_merge( $fields, $new_fields );
	}
}