<?php

namespace TEC\Tickets\Tests\Emails\Email;

use TEC\Tickets\Emails\Dispatcher;
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

	public $test_is_enabled = true;

	public $test_title = '%%TITLE%%';

	public $test_to = '%%TO%%';

	public $test_content = '%%CONTENT%%';

	public $test_subject = '%%SUBJECT%%';

	public function is_enabled(): bool {
		return $this->test_is_enabled;
	}

	public function get_title(): string {
		return $this->test_title;
	}

	public function get_to(): string {
		return $this->test_to;
	}

	public function get_recipient(): string {
		return $this->recipient;
	}

	public function get_subject(): string {
		return $this->test_subject;
	}

	public function get_content( $args = [] ): string {
		return $this->test_content;
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

	public function send() {
		$recipient = $this->get_recipient();

		// Bail if there is no email address to send to.
		if ( empty( $recipient ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		return Dispatcher::from_email( $this )->send();
	}
}