<?php

namespace TEC\Tickets\Deferred_Save\Block;

use Codeception\TestCase\WPTestCase;

class Editor_Config_Test extends WPTestCase {
	private array $deferred_posts = [];

	public function setUp(): void {
		parent::setUp();
		add_filter(
			'tec_tickets_deferred_save_enabled',
			function ( bool $enabled, int $post_id ): bool {
				return in_array( $post_id, $this->deferred_posts, true ) ? true : $enabled;
			},
			10,
			2
		);
	}

	public function tearDown(): void {
		$this->deferred_posts = [];
		$GLOBALS['post']      = null;
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function the_editor_config_says_whether_the_post_uses_deferred_save(): void {
		global $post;
		$post = get_post( static::factory()->post->create( [ 'post_type' => 'page' ] ) );

		$config = apply_filters( 'tec_tickets_editor_configuration_localized_data', [ 'providers' => [] ] );
		$this->assertSame( [], $config['providers'], 'Existing keys are kept.' );
		$this->assertFalse( $config['usesDeferredSave'] );

		$this->deferred_posts[] = $post->ID;
		$config                 = apply_filters( 'tec_tickets_editor_configuration_localized_data', [] );
		$this->assertTrue( $config['usesDeferredSave'] );
	}

	/**
	 * @test
	 */
	public function without_a_post_the_flag_is_false(): void {
		$GLOBALS['post'] = null;

		$config = apply_filters( 'tec_tickets_editor_configuration_localized_data', [] );

		$this->assertFalse( $config['usesDeferredSave'] );
	}
}
