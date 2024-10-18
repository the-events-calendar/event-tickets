<?php

namespace TEC\Tickets\Flexible_Tickets;

class Mock_WP_CLI {
	public static function add_hook( $hook, $callback ): void {
	}
}

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;

class WP_CliTest extends Controller_Test_Case {
	use With_Uopz;

	protected $controller_class = WP_Cli::class;

	/**
	 * @before
	 */
	public function mock_wpcli_class(): void {
		if ( ! class_exists( \WP_CLI::class ) ) {
			class_alias( Mock_WP_CLI::class, '\WP_CLI' );
		}
	}

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

	/**
	 * It should disable foreign key checks before running the site empty command
	 *
	 * @test
	 */
	public function should_disable_foreign_key_checks_before_running_the_site_empty_command(): void {
		$this->set_const_value( 'WP_CLI', true );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->maybe_disable_foreign_key_checks( [ 'site', 'empty' ] ) );
	}

	/**
	 * It should not disable foreign key checks before running any other command.
	 *
	 * @test
	 */
	public function should_not_disable_foreign_key_checks_before_running_any_other_command_(): void {
		$this->set_const_value( 'WP_CLI', true );

		$controller = $this->make_controller();

		$this->assertFalse( $controller->maybe_disable_foreign_key_checks( [ 'site', 'activate' ] ) );
	}
}
