<?php

namespace TEC\Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Admin\Panels_Data\Ticket_Panel_Data;

class Empty_Ticket_Settings_Upsell_Test extends WPTestCase {
	/**
	 * @before
	 */
	public function set_admin_context(): void {
		set_current_screen( 'post' );
	}

	/**
	 * @after
	 */
	public function reset_admin_context(): void {
		set_current_screen( 'front' );
	}

	private function render_settings_panel( int $post_id, array $context = [] ): string {
		$panel_data = ( new Ticket_Panel_Data( $post_id ) )->to_array();

		return tribe( 'tickets.admin.views' )->template(
			'editor/panel/settings',
			array_merge( $panel_data, [ 'post_id' => $post_id ], $context ),
			false
		);
	}

	/**
	 * @test
	 */
	public function it_should_show_the_upsell_when_the_settings_panel_is_empty(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_true' );
		$post_id = static::factory()->post->create();

		$html = $this->render_settings_panel( $post_id, [ 'active_providers' => [] ] );

		$this->assertStringContainsString( 'tec-admin__upsell-tec-tickets-empty-settings', $html );
		$this->assertStringContainsString( 'https://evnt.is/1f4c', $html );
		$this->assertStringNotContainsString( 'tribe-tickets-image', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_the_upsell_when_the_header_image_setting_shows(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_false' );
		$post_id = static::factory()->post->create();

		$html = $this->render_settings_panel( $post_id, [ 'active_providers' => [] ] );

		$this->assertStringContainsString( 'tribe-tickets-image', $html );
		$this->assertStringNotContainsString( 'tec-admin__upsell-tec-tickets-empty-settings', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_the_upsell_when_there_is_a_provider_choice(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_true' );
		$post_id   = static::factory()->post->create();
		$providers = [
			[
				'class'           => 'Provider_A',
				'html_safe_class' => 'Provider_A',
				'name'            => 'Provider A',
			],
			[
				'class'           => 'Provider_B',
				'html_safe_class' => 'Provider_B',
				'name'            => 'Provider B',
			],
		];

		$upsell = tribe( Upsell::class );
		ob_start();
		$upsell->maybe_show_empty_ticket_settings_upsell( $providers );
		$html = ob_get_clean();

		$this->assertSame( '', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_the_upsell_when_another_plugin_adds_settings(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_true' );
		add_action( 'tribe_events_tickets_settings_content', '__return_null' );
		$post_id = static::factory()->post->create();

		$html = $this->render_settings_panel( $post_id, [ 'active_providers' => [] ] );

		$this->assertStringNotContainsString( 'tec-admin__upsell-tec-tickets-empty-settings', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_the_upsell_when_upsells_are_hidden(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_true' );
		add_filter( 'tec_should_hide_upsell', '__return_true' );
		$post_id = static::factory()->post->create();

		$html = $this->render_settings_panel( $post_id, [ 'active_providers' => [] ] );

		$this->assertStringNotContainsString( 'tec-admin__upsell-tec-tickets-empty-settings', $html );
	}
}
