<?php

namespace TEC\Tickets_Plus\Test\Integration\Settings;

use TEC\Tickets\Emails\Admin\Emails_Tab;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Generator;

/**
 * Class Attendee_Registration_Test
 *
 * @package TEC\Tickets_Plus\Tests\Integration\Settings
 */
class Email_Settings_Test extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * Original option values.
	 *
	 * @var array<string,mixed>
	 */
	protected array $original_options = [];

	/**
	 * @after
	 */
	public function reset_options(): void {
		foreach ( $this->original_options as $key => $value ) {
			tribe_update_option( $key, $value );
		}
		$this->original_options = [];
	}

	/**
	 * Store original option value and set new one.
	 *
	 * @param string $key   The option key.
	 * @param mixed  $value The new option value.
	 */
	protected function set_option( string $key, $value ): void {
		if ( ! isset( $this->original_options[ $key ] ) ) {
			$this->original_options[ $key ] = tribe_get_option( $key );
		}
		tribe_update_option( $key, $value );
	}

	/**
	 * Data provider for attendee registration tab tests.
	 *
	 * @since TBD
	 *
	 * @return Generator<string,array{
	 *     tab: string,
	 *     options: array<string,bool>,
	 *     content_var: string
	 * }>
	 */
	public function provide_attendee_registration_tab_tests(): Generator {
		yield 'main emails tab' => [
			'emails',
			[],
		];

		// Modal enabled
		yield 'Sender Name filled' => [
			'emails',
			[
				'tec-tickets-emails-sender-name' => 'Fred',
			],
		];

		// Modal disabled
		yield 'Sender Name empty' => [
			'emails',
			[
				'tec-tickets-emails-sender-name' => false,
			],
		];
	}

	/**
	 * Clean dynamic content from HTML to avoid false mismatches in snapshot tests.
	 *
	 * @since TBD
	 *
	 * @param string $content The content to clean.
	 *
	 * @return string The cleaned content.
	 */
	protected function clean_dynamic_content( string $content ): string {
		// Replace plugin path in common resources
		$content = preg_replace(
			'#plugins/[^/]+/common/src/resources#',
			'plugins/*placeholder*/common/src/resources',
			$content
		);

		return $content;
	}

	/**
	 * @test
	 * @dataProvider provide_attendee_registration_tab_tests
	 */
	public function should_match_attendee_registration_tab_snapshots( string $tab, array $options ): void {
		// Set up WordPress admin environment
		$this->set_fn_return( 'is_admin', true );
		$this->set_fn_return( 'current_user_can', true );
		$this->set_fn_return( 'check_admin_referer', true );

		// Set options if provided
		foreach ( $options as $key => $value ) {
			$this->set_option( $key, $value );
		}

		// Set the current tab
		$_GET['tab'] = $tab;
		$emails_tab  = tribe( Emails_Tab::class );
		$emails_tab->register_tab( 'tec-tickets-settings' );

		// Get the settings instance and get the fields
		$tab = $emails_tab->get_settings_tab();

		// Capture the output
		ob_start();
		$tab->do_content();
		$content = ob_get_clean();

		// Clean dynamic content
		$content = $this->clean_dynamic_content( $content );

		// Assert the snapshot
		$this->assertMatchesHtmlSnapshot( $content );
	}
}
