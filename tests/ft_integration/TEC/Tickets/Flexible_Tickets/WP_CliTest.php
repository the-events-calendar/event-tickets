<?php

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Tickets\Flexible_Tickets\Test\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;

class WP_CliTest extends Controller_Test_Case {
	use With_Uopz;

	protected $controller_class = WP_Cli::class;

	/**
	 * It should not be active if not WP CLI context
	 *
	 * @test
	 */
	public function should_not_be_active_if_not_wp_cli_context(): void {
		$this->set_const_value( 'WP_CLI', false );

		$controller = $this->make_controller();

		$this->assertFalse( $controller->is_active() );
	}

	/**
	 * It should be active in WP CLI context
	 *
	 * @test
	 */
	public function should_be_active_in_wp_cli_context(): void {
		$this->set_const_value( 'WP_CLI', true );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->is_active() );
	}

	/**
	 * It should truncate tables on site empty
	 *
	 * @test
	 */
	public function should_truncate_tables_on_site_empty(): void {
		$this->set_const_value( 'WP_CLI', true );

		$controller = $this->make_controller();

		$this->assertNotEmpty( $controller->truncate_custom_tables() );
	}
}
