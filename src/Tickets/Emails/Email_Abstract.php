<?php
/**
 * Tickets Emails Email abstract class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

/**
 * Class Email_Abstract.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
abstract class Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Email title.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Email subject.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $placeholders = [];

	/**
	 * Get email subject.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_subject();

	/**
	 * Get email attachments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract public function is_enabled();

	/**
	 * Get email headers.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_headers() {
		return '';
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_additional_content() {
		return '';
	}

	/**
	 * Get email attachments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_attachments() {
		return '';
	}

	/**
	 * Get email content.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_content( $args ) {
		return '';
	}
}
