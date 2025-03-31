<?php

namespace TEC\Tickets\Blocks;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Classy\Back_Compatible_Editor;

class ControllerTest extends Controller_Test_Case {

	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function it_should_not_register_blocks_with_classy_active() {
		add_filter( 'tec_using_classy_editor', '__return_true' );

		$editor = $this->test_services->make( 'editor' );
		$this->assertInstanceOf( Back_Compatible_Editor::class, $editor );
		$this->assertFalse( $editor->should_load_blocks() );

		$this->make_controller()->register();

		$should_not_register = [
			'assets',
			'block.tickets',
			'blocks.rsvp',
			'blocks.tickets-item',
			'block.attendees',
		];

		foreach ( $should_not_register as $service ) {
			$service_string = "tickets.editor.{$service}";
			$this->assertFalse(
				$this->test_services->has( $service_string ),
				"Service {$service_string} should not be registered with Classy active."
			);
		}
	}
}
