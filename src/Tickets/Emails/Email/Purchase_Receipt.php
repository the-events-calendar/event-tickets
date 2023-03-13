<?php
/**
 * Class Purchase_Receipt
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use \TEC\Tickets\Emails\Email_Template;

/**
 * Class Purchase_Receipt
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
class Purchase_Receipt extends \TEC\Tickets\Emails\Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $id = 'tec_tickets_emails_purchase_receipt';

	/**
	 * Email template.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $template = 'customer-purchase-receipt';

	/**
	 * Email recipient.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $recipient = 'purchaser';

	/**
	 * Checks if this email is sent to customer.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_customer_email(): bool {
		return true;
	}

	/**
	 * Get email title.
	 *
	 * @since TBD
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html__( 'Purchase Receipt', 'event-tickets' );
	}

	/**
	 * Get default recipient.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_recipient(): string {
		return '{purchaser-email}';
	}

	/**
	 * Get default email heading.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return esc_html__( 'Your purchase receipt for #{order-number}', 'event-tickets' );
	}

	/**
	 * Get default email subject.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return esc_html__( 'Your purchase receipt for #{order-number}', 'event-tickets' );
	}

	/**
	 * Get email settings.
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
				'html' => '<h2>' . esc_html__( 'Purchase Receipt Email Settings', 'event-tickets' ) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'The ticket purchaser will receive an email about the purchase that was completed.' ) . '</p>',
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
			$this->get_option_key( 'heading' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $this->get_default_heading(),
				'placeholder'         => $this->get_default_heading(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'add-content' ) => [
				'type'                => 'wysiwyg',
				'label'               => esc_html__( 'Additional content', 'event-tickets' ),
				'default'             => '',
				'tooltip'             => esc_html__( 'Additional content will be displayed below the purchase receipt details in the email.', 'event-tickets' ),
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
