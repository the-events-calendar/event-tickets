<?php

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Users;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups;

class Custom_TablesTest extends Controller_Test_Case {
	protected string $controller_class = Custom_Tables::class;

	public function custom_tables_provider(): array {
		return [
			'posts_and_ticket_groups' => [ 'posts_and_ticket_groups' => Posts_And_Ticket_Groups::class ],
			'ticket_groups'           => [ 'ticket_groups' => Ticket_Groups::class ],
		];
	}

	/**
	 * It should register the capacities table correctly
	 *
	 * @test
	 * @dataProvider custom_tables_provider
	 */
	public function should_register_the_capacities_table_correctly( string $table_class ): void {
		$controller = $this->make_controller();
		$controller->register();

		do_action( 'tribe_plugins_loaded' );

		$this->assertTrue( $this->test_services->isBound( $table_class ) );
		$table = $this->test_services->get( $table_class );
		$this->assertInstanceOf( $table_class, $table );
		$this->assertTrue( $table->exists() );
	}

	/*
	 * Why is there no test for the drop_tables() method?
	 * This test case is running in the context of a transaction managed by the integration suite.
	 * DROP and CREATE TABLE statements will be filtered to TEMPORARY.
	 * The drop and create statements are executed in suite _bootstrap.php file and there is the their check.
	 */
}
