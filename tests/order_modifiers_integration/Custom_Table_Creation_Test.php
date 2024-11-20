<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships;

/**
 * Class Custom_Table_Creation_Test
 *
 * Tests for verifying the existence and index creation of Order Modifiers custom tables.
 *
 * @since TBD
 */
class Custom_Table_Creation_Test extends WPTestCase {

	/**
	 * Tests if the necessary custom tables exist in the database.
	 *
	 * @test
	 * @dataProvider custom_tables_provider
	 *
	 * @param string $table_name The name of the table to check.
	 */
	public function custom_table_exists( string $table_name ) {
		$this->assertTrue( $this->table_exists( $table_name ), "{$table_name} should exist." );
	}

	/**
	 * Tests if the necessary custom tables have at least one index.
	 *
	 * @test
	 * @dataProvider custom_tables_provider
	 *
	 * @param string $table_name The name of the table to check.
	 */
	public function custom_table_has_indexes( string $table_name ) {
		$this->assertTrue( $this->table_has_indexes( $table_name ), "{$table_name} should have at least one index." );
	}

	/**
	 * Data provider for custom table tests.
	 *
	 * Provides the table names for existence and index checks along with a description.
	 *
	 * @return \Generator Yields table descriptions and names.
	 */
	public function custom_tables_provider() {
		yield 'Order Modifiers Table' => [
			'table_name' => Order_Modifiers::table_name(),
		];

		yield 'Order Modifiers Meta Table' => [
			'table_name' => Order_Modifiers_Meta::table_name(),
		];

		yield 'Order Modifiers Relationships Table' => [
			'table_name' => Order_Modifier_Relationships::table_name(),
		];
	}

	/**
	 * Helper function to check if a table exists in the database using the DB library.
	 *
	 * @param string $table_name The table name to check.
	 *
	 * @return bool True if the table exists, false otherwise.
	 */
	protected function table_exists( $table_name ): bool {
		$table_count = DB::table( DB::raw( 'information_schema.TABLES' ) )
						 ->whereRaw( 'WHERE TABLE_SCHEMA = DATABASE()' )
						 ->where( 'TABLE_NAME', $table_name )
						 ->count();

		return $table_count > 0;
	}

	/**
	 * Helper function to check if a table has at least one index using the DB library.
	 *
	 * @param string $table_name The table name to check.
	 *
	 * @return bool True if the table has at least one index, false otherwise.
	 */
	protected function table_has_indexes( $table_name ): bool {
		$index_count = DB::table( DB::raw( 'information_schema.statistics' ) )
						 ->whereRaw( 'WHERE TABLE_SCHEMA = DATABASE()' )
						 ->where( 'TABLE_NAME', $table_name )
						 ->count();

		return $index_count > 0;
	}
}
