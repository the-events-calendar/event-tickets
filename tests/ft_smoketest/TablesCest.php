<?php

use FT_Smoketester as Tester;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Users;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups;

class TablesCest {
	public function _before( Tester $I ) {
		$I->loginAsAdmin();
		$I->amOnAdminPage( '/' );
	}

	/**
	 * It should create capacities table
	 *
	 * @test
	 */
	public function should_create_capacities_table( Tester $I ): void {
		$I->seeTableInDatabase( Capacities::table_name( true ) );
	}

	/**
	 * It should create capacities_relationships table
	 *
	 * @test
	 */
	public function should_create_capacities_relationships_table( Tester $I ): void {
		$I->seeTableInDatabase( Capacities_Relationships::table_name( true ) );
	}

	/**
	 * It should creatae posts_and_posts table
	 *
	 * @test
	 */
	public function should_creatae_posts_and_posts_table( Tester $I ): void {
		$I->seeTableInDatabase( Posts_And_Posts::table_name( true ) );
	}

	/**
	 * It should create posts_and_users table
	 *
	 * @test
	 */
	public function should_create_posts_and_users_table( Tester $I ): void {
		$I->seeTableInDatabase( Posts_And_Users::table_name( true ) );
	}

	/**
	 * It should create ticket_groups table
	 *
	 * @test
	 */
	public function should_create_ticket_groups_table( Tester $I ): void {
		$I->seeTableInDatabase( Ticket_Groups::table_name( true ) );
	}

	/**
	 * It should create posts_and_ticket_groups_table
	 *
	 * @test
	 */
	public function should_create_posts_and_ticket_groups_table( Tester $I ): void {
		$I->seeTableInDatabase( Posts_And_Ticket_Groups::table_name() );
	}
}
