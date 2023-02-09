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
	public $id = 'ticket';

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
	 * Get email subject.
	 *
	 * @since TBD
	 *
	 * @return string The email subject.
	 */
	public function get_subject() {
		$subject = ''; // This comes from the option.

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
		$subject = apply_filters( 'tec_tickets_emails_subject_' . $this->id, $subject, $this->if );

		return $subject;
	}

	/**
	 * Get content.
	 *
	 * @since TBD
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email content.
	 */
	public function get_content( $args ) {
		// @todo: Parse args, etc.
		$context = ! empty( $args['context'] ) ? $args['context'] : [];

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( true );

		return $email_template->get_html( $context, $this->id ); // @todo @juanfra @codingmusician: we may want to inverse these parameters.
	}
}
