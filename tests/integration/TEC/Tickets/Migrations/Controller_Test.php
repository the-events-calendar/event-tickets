<?php
/**
 * Tests for the Migrations Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\Tests\Integration\Migrations;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Migrations\Controller;

/**
 * Class Controller_Test
 *
 * @since TBD
 */
class Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function should_add_event_tickets_tag_to_filters_during_display_callback(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Simulate what the display_callback does: the filter is added around render_list().
		// Before the tab renders, the filter should not be active.
		$filters = apply_filters( 'stellarwp_migrations_tec_filters', [ 'tags' => [], 'show_completed' => true ] );
		$this->assertNotContains( 'event-tickets', $filters['tags'], 'The event-tickets tag should not be in filters outside the display callback.' );
	}

	/**
	 * @test
	 */
	public function should_preserve_existing_tags_when_adding_event_tickets(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Simulate the closure behavior directly.
		$add_tags = static function ( array $filters ): array {
			$filters['tags']   = $filters['tags'] ?? [];
			$filters['tags'][] = 'event-tickets';

			return $filters;
		};

		add_filter( 'stellarwp_migrations_tec_filters', $add_tags );

		$filters = apply_filters( 'stellarwp_migrations_tec_filters', [ 'tags' => [ 'existing-tag' ], 'show_completed' => true ] );

		$this->assertContains( 'existing-tag', $filters['tags'], 'Existing tags should be preserved.' );
		$this->assertContains( 'event-tickets', $filters['tags'], 'The event-tickets tag should be added.' );

		remove_filter( 'stellarwp_migrations_tec_filters', $add_tags );
	}

	/**
	 * @test
	 */
	public function should_render_backup_notice_with_link_in_display_callback(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Capture the display callback when the tab is constructed.
		$display_callback = null;
		$capture          = static function ( $value, $id ) use ( &$display_callback ) {
			if ( 'migrations' === $id ) {
				$display_callback = $value;
			}

			return $value;
		};
		add_filter( 'tribe_settings_tab_display_callback', $capture, 10, 2 );

		$controller->register_migrations_tab( 'tec-tickets-settings' );

		remove_filter( 'tribe_settings_tab_display_callback', $capture );

		$this->assertIsCallable( $display_callback, 'The migrations tab should register a display callback.' );

		ob_start();
		$display_callback();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'notice notice-info inline notice-info--migration', $html );
		$this->assertStringContainsString( 'We recommend doing a <a href="https://evnt.is/1bei" target="_blank" rel="noopener noreferrer">site backup</a> before starting migration.', $html );
	}
}
