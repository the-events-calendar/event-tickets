<?php
/**
 * Class Failed_Order
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use \TEC\Tickets\Emails\Email_Template;

/**
 * Class Failed_Order
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Emails
 */
class Failed_Order extends \TEC\Tickets\Emails\Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_failed_order';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $slug = 'failed-order';

	/**
	 * Email template.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $template = 'admin-failed-order';

	/**
	 * Email recipient.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $recipient = 'admin';

	/**
	 * Get email title.
	 *
	 * @since 5.5.10
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html__( 'Failed Order', 'event-tickets' );
	}

	/**
	 * Get default email recipient.
	 *
	 * @since 5.5.10
	 *
	 * @return string
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
		return esc_html__( 'Failed order: #{order_number}', 'event-tickets' );
	}

	/**
	 * Get default email subject.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return esc_html__( '[{site_title}]: Failed order #{order_number}', 'event-tickets' );
	}

	/**
	 * Get email settings.
	 *
	 * @since 5.5.10
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
				'html' => '<h2>' . esc_html__( 'Failed Order Email Settings', 'event-tickets' ) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Site administrators and additional recipients will be notified when there’s a problem with a ticket purchase. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {ticket_name} can be used to pull dynamic content from the ticket into your email. Learn more about customizing email templates in our Knowledgebase.' ) . '</p>',
			],
			$this->get_option_key( 'enabled' ) => [
				'type'                => 'toggle',
				'label'               => esc_html__( 'Enabled', 'event-tickets' ),
				'default'             => true,
				'validation_type'     => 'boolean',
			],
			$this->get_option_key( 'recipient' ) => [
				'type'                => 'text',
				'label'               => esc_html__( 'Recipient(s)', 'event-tickets' ),
				'default'             => $this->get_default_recipient(),
				'size'                => 'large',
				'validation_type' => 'email_list',
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
				'tooltip'             => esc_html__( 'Additional content will be displayed below the failed order details in your email.', 'event-tickets' ),
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

	/**
	 * Get preview context for email.
	 *
	 * @since 5.5.10
	 *
	 * @param array $args The arguments.
	 * @return array $args The modified arguments
	 */
	public function get_preview_context( $args = [] ): array {
		$defaults = [
			'is_preview' => true,
			'title'      => $this->get_heading(),
			'heading'    => $this->get_heading(),
		];

		return wp_parse_args( $args, $defaults );
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
		// @todo: We need to grab the proper information that's going to be sent as context.
		$is_preview = ! empty( $args['is_preview'] ) ? tribe_is_truthy( $args['is_preview'] ) : false;

		$defaults = [
			'title'              => $this->get_title(),
			'heading'            => $this->get_heading(),
			'additional_content' => $this->format_string( tribe_get_option( $this->get_option_key( 'add-content' ), '' ) ),
		];

		$args = wp_parse_args( $args, $defaults );

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( $is_preview );

		return $email_template->get_html( $this->template, $args );
	}
}
