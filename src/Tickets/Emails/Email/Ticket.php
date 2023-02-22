<?php
/**
 * Class Ticket
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use \TEC\Tickets\Emails\Email_Template;
use WP_Post;

/**
 * Class Ticket
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
class Ticket extends \TEC\Tickets\Emails\Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_ticket';

	/**
	 * Email template.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $template = 'ticket';

	/**
	 * Email recipient.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $recipient = 'customer';

	/**
	 * Email version number.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Default subject.
	 * 
	 * @since TBD
	 * 
	 * @var string
	 */
	public $default_subject = '';

	/**
	 * Enabled option key.
	 * 
	 * @since TBD
	 * 
	 * @var string
	 */
	static $option_enabled = 'tec_tickets_emails_ticket_option_enabled';

	/**
	 * Subject option key.
	 * 
	 * @since TBD
	 * 
	 * @var string
	 */
	static $option_subject = 'tec_tickets_emails_ticket_option_subject';

	/**
	 * Email heading option key.
	 * 
	 * @since TBD
	 * 
	 * @var string
	 */
	static $option_heading = 'tec_tickets_emails_ticket_option_heading';

	/**
	 * Email heading option key.
	 * 
	 * @since TBD
	 * 
	 * @var string
	 */
	static $option_add_content = 'tec_tickets_emails_ticket_option_add_content';

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
		// @todo @codingmusician: apply filters?
		return esc_html__( 'Ticket Email', 'event-tickets' );
	}

	/**
	 * Get email heading.
	 *
	 * @since TBD
	 *
	 * @return string The email heading.
	 */
	public function get_heading(): string {
		// @todo @codingmusician: apply filters?
		$heading = '';

		return $this->format_string( $heading );
	}

	/**
	 * Get email "from" name.
	 *
	 * @since TBD
	 *
	 * @return string The from name.
	 */
	public function get_from_name(): string {
		// @todo @codingmusician: Get the from name.
		return '';
	}

	/**
	 * Get email "from" email.
	 *
	 * @since TBD
	 *
	 * @return string The from email.
	 */
	public function get_from_email(): string {
		// @todo @codingmusician: Get the from email.
		return '';
	}

	/**
	 * Get email subject.
	 *
	 * @since TBD
	 *
	 * @return string The email subject.
	 */
	public function get_subject(): string {
		$subject = tribe_get_option( static::$option_subject, true );

		$subject = $this->format_string( $subject );

		// @todo: Probably we want more data parsed, or maybe move the filters somewhere else as we're always gonna
		// apply filters on the subject maybe move the filter to the parent::get_subject() ?

		/**
		 * Allow filtering the email subject.
		 *
		 * @since TBD
		 *
		 * @param string $subject  The email subject.
		 * @param string $id       The ticket id.
		 */
		$subject = apply_filters( 'tec_tickets_emails_subject_' . $this->id, $subject, $this->id, $this->template );

		return $subject;
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
		$email_template->set_preview( true );

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
				'html' => '<h3>' . esc_html__( 'Ticket Email Settings', 'event-tickets' ) . '</h3>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Ticket purchasers will receive an email including their ticket and additional info upon completion of purchase. Customize the content of this specific email using the tools below. The brackets [event-name], [event-date], and [ticket-name] can be used to pull dynamic content from the ticket into your email. Learn more about customizing email templates in our Knowledgebase.' ) . '</p>',
			],
			static::$option_enabled => [
				'type'                => 'checkbox_bool',
				'label'               => esc_html__( 'Ticket Email ', 'event-tickets' ),
				'default'             => true,
				'validation_type'     => 'boolean',
			],
			static::$option_subject => [
				'type'                => 'text',
				'label'               => esc_html__( 'Email subject', 'event-tickets' ),
				'default'             => esc_html__( 'Your tickets to [event-name]', 'event-tickets' ),
				'validation_callback' => 'is_string',
			],
			static::$option_heading => [
				'type'                => 'text',
				'label'               => esc_html__( 'Email heading', 'event-tickets' ),
				'default'             => esc_html__( 'Here\'s your ticket, [attendee-name]!', 'event-tickets' ),
				'validation_callback' => 'is_string',
			],
			static::$option_add_content => [
				'type'                => 'textarea',
				'label'               => esc_html__( 'Additional content', 'event-tickets' ),
				'default'             => '',
				'tooltip'             => esc_html__( 'Additional content will be displayed below the tickets in your email.', 'event-tickets' ),
				'validation_type'     => 'textarea',
			],
		];

		return $settings;
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
			'slug'      => $this->id,
			'title'     => $this->get_title(),
			'template'  => $this->template,
			'recipient' => 'customer',
		];

		return $data;
	}
}
