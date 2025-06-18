<?php
/**
 * Class Completed_Order
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\JSON_LD\Order_Schema;

/**
 * Class Completed_Order
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Emails
 */
class Completed_Order extends Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_completed_order';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $slug = 'completed-order';

	/**
	 * Email template.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $template = 'admin-new-order';

	/**
	 * Get email title.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html__( 'Completed Order', 'event-tickets' );
	}

	/**
	 * Get email to.
	 *
	 * @since 5.5.11
	 *
	 * @return string The email "to".
	 */
	public function get_to(): string {
		return esc_html__( 'Admin', 'event-tickets' );
	}

	/**
	 * Get default email recipient.
	 *
	 * @since 5.5.10
	 *
	 * @return string $recipient The default email recipient.
	 */
	public function get_default_recipient(): string {
		return get_option( 'admin_email' );
	}

	/**
	 * Get default email heading.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return esc_html__( 'Completed order: #{order_number}', 'event-tickets' );
	}

	/**
	 * Get default email subject.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_subject():string {
		return esc_html__( '[{site_title}]: Completed order #{order_number}', 'event-tickets' );
	}

	/**
	 * Get email settings.
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
			// Translators: %1$s: Completed order Emails knowledge base article link.
			esc_html_x( 'The site admin will receive an email about any orders that were made. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. %1$s.', 'about Completed Order email', 'event-tickets' ),
			$kb_link
		);

		return [
			'tec-settings-email-template-wrapper_start'   => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__header-block--horizontal">',
			],
			'tec-settings-email-template-header'          => [
				'type' => 'html',
				'html' => '<h3>' . esc_html_x( 'Completed Order Email Settings', 'Email Title', 'event-tickets' ) . '</h3>',
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
			$this->get_option_key( 'recipient' )          => [
				'type'            => 'text',
				'label'           => esc_html__( 'Recipient(s)', 'event-tickets' ),
				'default'         => $this->get_default_recipient(),
				'tooltip'         => esc_html__( 'Add additional recipient emails separated by commas.', 'event-tickets' ),
				'size'            => 'large',
				'validation_type' => 'email_list',
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
				'default'         => $this->get_default_additional_content(),
				'tooltip'         => esc_html__( 'Additional content will be displayed below the order details.', 'event-tickets' ),
				'size'            => 'large',
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
		$order = Preview_Data::get_order();
		$placeholders = [
			'{order_number}' => $order->ID,
			'{order_id}'     => $order->ID,
		];
		$this->set_placeholders( $placeholders );

		$defaults = [
			'email'              => $this,
			'is_preview'         => true,
			'title'              => $this->get_heading(),
			'heading'            => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'order'              => $order,
			'attendees'          => Preview_Data::get_attendees(),
		];

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
			'order'              => $this->get( 'order' ),
			'json_ld'            => Order_Schema::build_from_email( $this ),
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

		$order = $this->get( 'order' );

		// Bail if there's no order.
		if ( empty( $order ) ) {
			return false;
		}

		$placeholders = [
			'{order_number}' => $order->ID,
			'{order_id}'     => $order->ID,
		];

		$this->set_placeholders( $placeholders );

		return Dispatcher::from_email( $this )->send();
	}
}
