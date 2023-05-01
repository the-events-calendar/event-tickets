<?php

namespace TEC\Tickets\Tests\Emails\Email;

use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Dummy_Email
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails\Email
 */
class Dummy_Email extends Email_Abstract {
	public $id = 'tec_tickets_emails_dummy';

	public $slug = 'dummy';

	public $template = 'dummy';

	public $recipient = 'recipient_dummy@example.dev';

	public bool $test_is_enabled = true;

	public function is_enabled(): bool {
		return $this->test_is_enabled;
	}

	public function get_title(): string {
		return '%%TITLE%%';
	}

	public function get_to(): string {
		return '%%TO%%';
	}

	public function get_default_recipient(): string {
		return 'default_recipient_dummy@example.dev';
	}

	public function get_default_heading(): string {
		return '%%DEFAULT_HEADING%%';
	}

	public function get_default_subject():string {
		return '%%DEFAULT_SUBJECT%%';
	}

	public function get_settings_fields(): array {
		return [];
	}

	public function get_default_preview_context( $args = [] ): array {
		return [];
	}

	public function get_default_template_context(): array {
		return [];
	}

	public function get_content( $args = [] ): string {
		return '%%CONTENT%%';
	}

	public function send() {
		$recipient = $this->get_recipient();

		// Bail if there is no email address to send to.
		if ( empty( $recipient ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		return $this->get_dispatcher()->send();
	}
}