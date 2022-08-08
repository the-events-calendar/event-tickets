<?php

/**
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 *
 */

namespace TEC\Tickets\Emails;

use tad_DI52_ServiceProvider;

class Settings extends tad_DI52_ServiceProvider {

	static $option_sender_name = 'tec-tickets-emails-sender-name';
	static $option_sender_email = 'tec-tickets-emails-sender-email';
	static $option_header_image_url = 'tec-tickets-emails-header-image-url';
	static $option_header_image_alignment = 'tec-tickets-emails-header-image-alignment';
	static $option_header_bg_color = 'tec-tickets-emails-header-bg-color';
	static $option_ticket_bg_color = 'tec-tickets-emails-ticket-bg-color';
	static $option_footer_content = 'tec-tickets-emails-footer-content';
	static $option_footer_credit = 'tec-tickets-emails-footer-credit';

	/**
	 * Register Tickets Emails Settings.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->register_hooks();
	}

	/**
	 * Tickets Emails Settings Hooks.
	 *
	 * @since TBD
	 */
	public function register_hooks() {
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'sender_info_fields' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'email_styling_fields' ] );
	}

	/**
	 * Adds Sender Info fields to Tickets Emails settings.
	 *
	 * @param  [] $fields Current array of Tickets Emails settings fields.
	 * 
	 * @return [] $fields Filtered array of Tickets Emails settings fields.
	 */
	public function sender_info_fields( $fields ) {

		$current_user = get_user_by( 'id', get_current_user_id() );

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
				'default'             => $current_user->user_nicename,
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
			static::$option_sender_email  => [
				'type'                => 'text',
				'label'               => esc_html__( 'Sender Email', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => $current_user->user_email,
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
		];

		$new_fields = apply_filters( 'tec_tickets_emails_settings_sender_info_fields', $new_fields );

		return array_merge( $fields, $new_fields );
	}

	/**
	 * Adds Sender Info fields to Tickets Emails settings.
	 *
	 * @param  [] $fields Current array of Tickets Emails settings fields.
	 * 
	 * @return [] $fields Filtered array of Tickets Emails settings fields.
	 */
	public function email_styling_fields( $fields ) {

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
				'type'                => 'text',
				'label'               => esc_html__( 'Header Image', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
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
				'type'                => 'text',
				'label'               => esc_html__( 'Header/Footer Background', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '#ffffff',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
			static::$option_ticket_bg_color  => [
				'type'                => 'text',
				'label'               => esc_html__( 'Ticket Color', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '#ffffff',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
			static::$option_footer_content  => [
				'type'                => 'wysiwyg',
				'label'               => esc_html__( 'Footer Content', 'event-tickets' ),
				'tooltip'             => esc_html__( 'Add custom links and instructions to the bottom of your emails.', 'event-tickets' ),
				'default'             => '',
				'validation_type'     => 'html',
			],
			static::$option_footer_credit => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Footer Credit', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Include "Ticket powered by Event Tickets Plus" in the footer', 'event-tickets' ),
				'default'         => true,
				'validation_type' => 'boolean',
			],
		];

		$new_fields = apply_filters( 'tec_tickets_emails_settings_email_styling_fields', $new_fields );

		return array_merge( $fields, $new_fields );
	}
}