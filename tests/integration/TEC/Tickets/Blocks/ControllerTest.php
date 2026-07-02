<?php

namespace TEC\Tickets\Blocks;

use TEC\Common\Tests\Provider\Controller_Test_Case;

class ControllerTest extends Controller_Test_Case {
	protected string $controller_class = Controller::class;

	public function test_render_form_toggle_buttons_shows_disabled_rsvp_button_when_rsvp_disabled(): void {
		$post_id = static::factory()->post->create();

		add_filter( 'tec_tickets_enabled_ticket_forms', static function ( array $enabled ): array {
			$enabled['rsvp'] = false;

			return $enabled;
		} );

		$controller = $this->make_controller();

		ob_start();
		$controller->render_form_toggle_buttons( $post_id );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'id="rsvp_form_toggle"', $html, 'RSVP button should be rendered even when disabled.' );
		$this->assertStringContainsString( 'disabled', $html, 'RSVP button should have the disabled attribute.' );
		$this->assertStringContainsString( 'migration is in progress', $html, 'RSVP button should show migration tooltip.' );
	}

	public function test_render_form_toggle_buttons_shows_enabled_rsvp_button_by_default(): void {
		$post_id = static::factory()->post->create();

		$controller = $this->make_controller();

		ob_start();
		$controller->render_form_toggle_buttons( $post_id );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'id="rsvp_form_toggle"', $html, 'RSVP button should be rendered.' );
		$this->assertStringNotContainsString( 'disabled', $html, 'RSVP button should not be disabled by default.' );
	}
}
