<?php
/**
 * Tickets Emails Email abstract class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use WP_Post;

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
	 * Email template filename.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Email recipient.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $recipient;

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
	 * Get email title.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * Get email subject.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract public function get_subject();

	/**
	 * Is email enabled.
	 *
	 * @since TBD
	 *
	 * @return boolean
	 */
	abstract public function is_enabled();

	/**
	 * Handles the hooking of a given email to the correct actions in WP.
	 *
	 * @since TBD
	 */
	abstract public function hook();

	/**
	 * Get the post type data for the email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract public function get_post_type_data(): array;

	/**
	 * Get the settings for the email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract public function get_settings(): array;

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
	 * @return string
	 */
	public function get_content( $args ) {
		return '';
	}

	/**
	 * Get post object of email.
	 * 
	 * @since TBD
	 * 
	 * @return WP_Post|null;
	 */
	public function get_post() {
		return get_page_by_path( $this->id, OBJECT, Email_Handler::POSTTYPE );
	}
}
