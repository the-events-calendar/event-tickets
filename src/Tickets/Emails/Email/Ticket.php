<?php
/**
 * Class Ticket
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use \TEC\Tickets\Emails\Email_Template;

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
	 * Check if the email is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool True if the email is enabled.
	 */
	public function is_enabled(): bool {
		// @todo: This value should come from the settings.
		return true;
	}

	/**
	 * Checks if this email is customer focussed.
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
	 * Get email subject.
	 *
	 * @since TBD
	 *
	 * @return string The email subject.
	 */
	public function get_subject(): string {
		$subject = ''; // This comes from the option.

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
			// @todo @codingmusician: Include all the email settings here.
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
