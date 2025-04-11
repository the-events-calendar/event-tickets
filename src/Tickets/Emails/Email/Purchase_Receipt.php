<?php
/**
 * Class Purchase_Receipt
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Email_Template;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\JSON_LD\Order_Schema;

/**
 * Class Purchase_Receipt
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Emails
 */
class Purchase_Receipt extends Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_purchase_receipt';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $slug = 'purchase-receipt';

	/**
	 * Email template.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $template = 'customer-purchase-receipt';

	/**
	 * Get email title.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html__( 'Purchase Receipt', 'event-tickets' );
	}

	/**
	 * Get email to.
	 *
	 * @since 5.5.11
	 *
	 * @return string The email "to".
	 */
	public function get_to(): string {
		return esc_html__( 'Purchaser', 'event-tickets' );
	}

	/**
	 * Get default email heading.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return esc_html__( 'Your purchase receipt for #{order_number}', 'event-tickets' );
	}

	/**
	 * Get default email subject.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return esc_html__( 'Your purchase receipt for #{order_number}', 'event-tickets' );
	}

	/**
	 * Get email settings.
	 *
	 * @since 5.5.10
	 * @since TBD Added new classes for settings.
	 *
	 * @return array
	 */
	public function get_settings_fields(): array {
		$kb_link = sprintf(
			'<a href="https://evnt.is/event-tickets-emails" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$email_description = sprintf(
			// Translators: %1$s: Purchase Receipt Emails knowledgebase article link.
			esc_html_x( 'The ticket purchaser will receive an email about the purchase that was completed. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. %1$s.', 'about Purchase Receipt email', 'event-tickets' ),
			$kb_link
		);

		return [
			[
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap tec-settings-form__header-block--horizontal">',
			],
			[
				'type' => 'html',
				'html' => '<h2>' . esc_html__( 'Purchase Receipt Email Settings', 'event-tickets' ) . '</h2>',
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
			$this->get_option_key( 'subject' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
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
			$this->get_option_key( 'additional-content' ) => [
				'type'                => 'wysiwyg',
				'label'               => esc_html__( 'Additional content', 'event-tickets' ),
				'default'             => '',
				'tooltip'             => esc_html__( 'Additional content will be displayed below the purchase receipt details in the email.', 'event-tickets' ),
				'size'                => 'large',
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
		$defaults = [
			'email'              => $this,
			'is_preview'         => true,
			'title'              => $this->get_heading(),
			'heading'            => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'order'              => Preview_Data::get_order(),
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
