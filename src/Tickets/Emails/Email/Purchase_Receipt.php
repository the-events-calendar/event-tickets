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
	public $recipient = 'customer';

	/**
	 * Enabled option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_enabled = 'tec-tickets-emails-purchase-receipt-enabled';

	/**
	 * Subject option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_subject = 'tec-tickets-emails-purchase-receipt-subject';

	/**
	 * Email heading option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_heading = 'tec-tickets-emails-purchase-receipt-heading';

	/**
	 * Email heading option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_add_content = 'tec-tickets-emails-purchase-receipt-add-content';

	/**
	 * Checks if this email is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return tribe_is_truthy( tribe_get_option( static::$option_enabled, true ) );
	}

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
	 * Get email heading.
	 *
	 * @since TBD
	 *
	 * @return string The email heading.
	 */
	public function get_heading(): string {
		$heading = tribe_get_option( static::$option_heading, $this->get_default_heading() );

		/**
		 * Allow filtering the email heading for Purchase Receipt.
		 *
		 * @since TBD
		 *
		 * @param string          $heading  The email heading.
		 * @param string          $id       The email id.
		 * @param Tribe__Template $template Template object.
		 */
		$heading = apply_filters( 'tec_tickets_emails_purchase_receipt_heading', $heading, self::$id, $this->template );

		/**
		 * Allow filtering the email heading globally.
		 *
		 * @since TBD
		 *
		 * @param string          $heading  The email heading.
		 * @param string          $id       The email id.
		 * @param Tribe__Template $template Template object.
		 */
		$heading = apply_filters( 'tec_tickets_emails_heading', $heading, self::$id, $this->template );

		return $this->format_string( $heading );
	}

	/**
	 * Get default email heading.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return esc_html__( 'Your purchase receipt for #{order-number}', 'event-tickets' );
	}

	/**
	 * Get email subject.
	 *
	 * @since TBD
	 *
	 * @return string The email subject.
	 */
	public function get_subject(): string {
		$subject = tribe_get_option( static::$option_subject, $this->get_default_subject() );

		$subject = $this->format_string( $subject );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna
		// apply filters on the subject maybe move the filter to the parent::get_subject() ?

		/**
		 * Allow filtering the email subject for Purchase Receipt.
		 *
		 * @since TBD
		 *
		 * @param string          $subject  The email subject.
		 * @param string          $id       The email id.
		 * @param Tribe__Template $template Template object.
		 */
		$subject = apply_filters( 'tec_tickets_emails_purchase_receipt_subject', $subject, self::$id, $this->template );

		/**
		 * Allow filtering the email subject globally.
		 *
		 * @since TBD
		 *
		 * @param string          $subject  The email subject.
		 * @param string          $id       The email id.
		 * @param Tribe__Template $template Template object.
		 */
		$subject = apply_filters( 'tec_tickets_emails_subject', $subject, self::$id, $this->template );

		return $subject;
	}

	/**
	 * Get default email subject.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return esc_html__( 'Your purchase receipt for #{order-number}', 'event-tickets' );
	}

	/**
	 * Get email content.
	 *
	 * @since TBD
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email content.
	 */
	public function get_content( $args = [] ): string {
		// @todo: Parse args, etc.
		$context = ! empty( $args['context'] ) ? $args['context'] : [];

		// @todo: We need to grab the proper information that's going to be sent as context.

		$email_template = tribe( Email_Template::class );

		// @todo @juanfra @codingmusician: we may want to inverse these parameters.
		return $email_template->get_html( $context, $this->template );
	}

	/**
	 * Get email settings.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_settings(): array {

		$settings = [
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
				'html' => '<p>' . esc_html__( 'The ticket purchaser will receive an email about the purchase that  was completed.' ) . '</p>',
			],
			static::$option_enabled => [
				'type'                => 'toggle',
				'label'               => esc_html__( 'Enabled', 'event-tickets' ),
				'default'             => true,
				'validation_type'     => 'boolean',
			],
			static::$option_subject => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			static::$option_heading => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $this->get_default_heading(),
				'placeholder'         => $this->get_default_heading(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			static::$option_add_content => [
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

		/**
		 * Allow filtering the settings for Purchase Receipt.
		 *
		 * @since TBD
		 *
		 * @param array  $settings  The settings array.
		 * @param string $id        Email ID.
		 */
		$settings = apply_filters( 'tec_tickets_emails_purchase_receipt_settings', $settings, self::$id );

		/**
		 * Allow filtering the settings globally.
		 *
		 * @since TBD
		 *
		 * @param array  $settings  The settings array.
		 * @param string $id        Email ID.
		 */
		return apply_filters( 'tec_tickets_emails_settings', $settings, self::$id );
	}

	/**
	 * Get the `post_type` data for this email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_post_type_data(): array {
		$data = [
			'slug'      => self::$id,
			'title'     => $this->get_title(),
			'template'  => $this->template,
			'recipient' => $this->recipient,
		];

		return $data;
	}
}
