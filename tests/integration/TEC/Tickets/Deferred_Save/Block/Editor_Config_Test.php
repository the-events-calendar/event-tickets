<?php

namespace TEC\Tickets\Deferred_Save\Block;

use Codeception\TestCase\WPTestCase;

class Editor_Config_Test extends WPTestCase {
	public function tearDown(): void {
		$GLOBALS['post'] = null;
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_should_add_the_flag_to_the_editor_config_and_keep_the_existing_keys(): void {
		global $post;
		$post = get_post( static::factory()->post->create( [ 'post_type' => 'page' ] ) );

		$config = apply_filters( 'tec_tickets_editor_configuration_localized_data', [ 'providers' => [] ] );

		$this->assertSame( [], $config['providers'], 'Existing keys are kept.' );
		$this->assertTrue( $config['usesDeferredSave'] );
	}
}
