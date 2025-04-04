<?php

namespace TEC\Tickets\Blocks;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Classy\Back_Compatibility\Editor;

class ControllerTest extends Controller_Test_Case {

	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function it_should_not_register_blocks_with_classy_active() {
		if ( ! tec_using_classy_editor() ) {
			$this->markTestSkipped( 'Classy editor is not active.' );
		}

		$editor = $this->test_services->make( 'editor' );
		$this->assertInstanceOf( Editor::class, $editor );
		$this->assertFalse( $editor->should_load_blocks() );

		$this->make_controller()->register();

		$should_not_register = [
			'assets',
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

	/**
	 * @test
	 */
	public function it_should_register_blocks_without_classy_active() {
		if ( tec_using_classy_editor() ) {
			$this->markTestSkipped( 'Classy editor is active.' );
		}

		// Something else might have filtered blocks to off; ensure they are on.
		add_filter( 'tribe_editor_should_load_blocks', '__return_true', 1000 );

		$this->make_controller()->register();

		$should_register = [
			'compatibility.tickets',
			'assets',
			'blocks.tickets-item',
			'blocks.attendees',
		];

		foreach ( $should_register as $service ) {
			$service_string = "tickets.editor.{$service}";
			$this->assertTrue(
				$this->test_services->has( $service_string ),
				"Service {$service_string} should be registered without Classy active."
			);
		}
	}

	/**
	 * @test
	 */
	public function it_should_register_items_regardless_of_editor() {
		$should_register = [
			'tickets.editor.warnings',
			'tickets.editor.template.overwrite',
			'tickets.editor.template',
			'tickets.editor.configuration',
			'tickets.editor.meta',
			'tickets.editor.rest.compatibility',
			'tickets.editor.attendees_table',
		];

		foreach ( $should_register as $service ) {
			$this->assertTrue(
				$this->test_services->has( $service ),
				"Service {$service} should be registered."
			);
		}
	}
}
