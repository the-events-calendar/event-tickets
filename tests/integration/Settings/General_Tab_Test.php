<?php

namespace TEC\Tickets_Plus\Test\Integration\Settings;

use Tribe\Tickets\Admin\Settings;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Generator;

/**
 * Class General_Tab_Test
 *
 * @package TEC\Tickets_Plus\Tests\Integration\Settings
 */
class General_Tab_Test extends WPTestCase {
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
		codecept_debug( 'Setting ' . $key . ' to ' . $value );
		tribe_update_option( $key, $value );
	}

	/**
	 * Data provider for general tab tests.
	 *
	 * @since TBD
	 *
	 * @return Generator<string,array{
	 *     tab: string,
	 *     options: array<string,mixed>
	 * }>
	 */
	public function provide_general_tab_tests(): Generator {
		// Default settings
		yield 'default settings' => [
			'event-tickets',
			[],
		];

		// With ticket label customization
		yield 'custom ticket labels' => [
			'event-tickets',
			[
				'ticket_label_singular' => 'Pass',
				'ticket_label_plural' => 'Passes',
			],
		];

		// With ticket position settings
		yield 'ticket position settings' => [
			'event-tickets',
			[
				'ticket_position' => 'after',
				'ticket_show_attendees' => true,
			],
		];

		// With RSVP settings
		yield 'RSVP settings' => [
			'event-tickets',
			[
				'rsvp_enabled' => true,
				'rsvp_show_attendees' => true,
				'rsvp_show_not_going' => true,
			],
		];

		// With all settings enabled
		yield 'all settings enabled' => [
			'event-tickets',
			[
				'ticket_label_singular' => 'Pass',
				'ticket_label_plural' => 'Passes',
				'ticket_position' => 'after',
				'ticket_show_attendees' => true,
				'rsvp_enabled' => true,
				'rsvp_show_attendees' => true,
				'rsvp_show_not_going' => true,
			],
		];
	}

	/**
	 * @test
	 * @dataProvider provide_general_tab_tests
	 */
	public function should_match_general_tab_snapshots( string $tab, array $options ): void {
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
		$general_tab_setting = tribe( Settings::class );
		$general_tab_setting->settings_ui( 'tec-tickets-settings' );

		// Get the settings instance and get the fields
		$tab = $general_tab_setting->get_settings_tab();
		// Capture the output
		ob_start();
		$tab->do_content();
		$content = ob_get_clean();

		// Assert the snapshot
		$this->assertMatchesHtmlSnapshot( $content );
	}
}
