<?php
/**
 * Series Pass Email template.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;

use TEC\Tickets\Commerce\Gateways\Manual\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets\Emails\Email\Purchase_Confirmation_Email_Interface;
use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Series_Pass.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */
class Series_Pass extends Email_Abstract implements Purchase_Confirmation_Email_Interface {
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

	/**
	 * Returns the settings fields for the email.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The settings fields for the email.
	 */
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
							tec_tickets_get_series_pass_singular_uppercase( 'series-pass-email-tab-title' )
						)
					) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . $email_description . '</p>',
			],
			$this->get_option_key( 'enabled' )                => [
				'type'            => 'toggle',
				'label'           => sprintf(
				// Translators: %s - Title of email.
					esc_html__( 'Enable %s', 'event-tickets' ),
					$this->get_title()
				),
				'default'         => true,
				'validation_type' => 'boolean',
			],
			$this->get_option_key( 'subject' )                => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'heading' )                => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $this->get_default_heading(),
				'placeholder'         => $this->get_default_heading(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'additional-content' )     => [
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
			$this->get_option_key( 'show-events-in-email' )   => [
				'type'    => 'checkbox_bool',
				'label'   => esc_html_x( 'Events in Series', 'Series pass email settings', 'event-tickets' ),
				'default' => true,
				'tooltip' => esc_html_x( 'Show the next five upcoming Series events in email', 'Series pass email settings', 'event-tickets' ),
			]
		];
	}

	/*
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

	/**
	 * Get default subject.
	 *
	 * @since TBD
	 *
	 * @return string The default subject.
	 */
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

	/**
	 * Get default heading.
	 *
	 * @since TBD
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading(): string {
		return sprintf(
		// Translators: %s Lowercase plural of ticket.
			esc_html__( 'Here\'s your %s, {attendee_name}!', 'event-tickets' ),
			tec_tickets_get_series_pass_singular_lowercase()
		);
	}

	/**
	 * Returns the default preview context.
	 *
	 * @since TBD
	 *
	 * @param $args
	 *
	 * @return array<string,mixed> The default preview context.
	 */
	public function get_default_preview_context( $args = [] ): array {
		$total_value = Value::create( '100' );

		$order = new \WP_Post( (object) [
			'ID'               => - 23,
			'gateway_order_id' => 'test_cd7d068a5ef24c02',
			'items'            => [
				[
					'ticket_title' => __( 'Adult all access', 'event-tickets' ),
					'ticket_id'    => - 24,
					'quantity'     => 2,
					'extra'        => [
						'optout' => true,
						'iac'    => 'none',
					],
					'price'        => 50.0,
					'sub_total'    => 50.0,
					'event_id'     => - 96,
				]
			],
			'total'            => $total_value,
			'total_value'      => $total_value,
			'purchaser'        => [
				'first_name' => __( 'John', 'event-tickets' ),
				'name'       => __( 'John Doe', 'event-tickets' ),
				'email'      => 'john@doe.com',
			],
			'purchaser_name'   => __( 'John Doe', 'event-tickets' ),
			'purchaser_email'  => 'john@doe.com',
			'gateway'          => Gateway::get_key(),
			'status'           => __( 'Completed', 'event-tickets' ),
			'status_slug'      => 'completed',
			'tickets'          => Preview_Data::get_tickets(),
			'post_author'      => 1,
			'post_date'        => '2023-04-17 17:06:56',
			'post_date_gmt'    => '2023-04-17 22:06:56',
			'purchase_time'    => '2023-04-17 17:06:56',
			'purchase_date'    => '2023-04-17 17:06:56',
			'post_title'       => __( 'Preview Order', 'event-tickets' ),
			'post_status'      => 'tec-tc-completed',
			'post_name'        => 'preview-order-test_cd7d068a5ef24c02',
			'post_type'        => Order::POSTTYPE,
			'filter'           => 'raw',
			'provider'         => Module::class,
			'gateway_payload'  => [
				'tec-tc-completed' => [],
			],
		] );

		$header_image_url = plugins_url(
			'src/resources/images/series-pass-example-header-image.png',
			EVENT_TICKETS_MAIN_PLUGIN_FILE
		);
		$thumbnail_url = plugins_url(
			'src/resources/images/series-pass-example-series-thumbnail.png',
			EVENT_TICKETS_MAIN_PLUGIN_FILE
		);

		$post = ( new Mock_Series_Post() )->get_post();

		return [
			'is_preview'             => true,
			'post'                   => $post,
			'post_id'                => $post->ID,
			'order'                  => $order,
			'tickets'                => Preview_Data::get_tickets(),
			'header_image_alignment' => 'center',
			'header_image_url'       => $header_image_url,
			'thumbnail'              => [
				'url'   => $thumbnail_url,
				'alt'   => esc_attr_x( 'Jaws film poster', 'Series pass email thumbnail alternate text', 'event-tickets' ),
				'title' => esc_attr_x( 'Jaws', 'Series pass email thumbnail title', 'event-tickets' ),
			],
			'email'                  => $this,
		];
	}

	/**
	 * Returns the template context array and creates sample data if preview.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The template context array.
	 */
	public function get_default_template_context(): array {
		// @todo complete this
		return [
			'post_description_header' => _x('Additional Information', 'Series pass email post description header', 'event-tickets'),
		];
	}
}