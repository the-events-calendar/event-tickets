<?php

namespace TEC\Tickets\Flexible_Tickets\Test\Traits;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;

trait Custom_Tables_Assertions {

	protected function assert_object_capacity_not_in_db( int $object_id ): void {
		$capacity_relationships_table = Capacities_Relationships::table_name();
		$capacity_relationships       = DB::get_results( "SELECT * FROM $capacity_relationships_table WHERE object_id = {$object_id}" );

		$this->assertCount( 0, $capacity_relationships );
	}

	protected function assert_object_capacity_in_db( int $object_id, array $relationships_criteria, array $capacities_criteria ): void {
		$capacity_relationships_table = Capacities_Relationships::table_name();
		$capacity_relationships       = DB::get_results( "SELECT * FROM $capacity_relationships_table WHERE object_id = {$object_id}", ARRAY_A );

		$this->assertCount( 1, $capacity_relationships );

		$capacity_relationship = $capacity_relationships[0];
		$capacity_id           = $capacity_relationship['capacity_id'];

		foreach ( $relationships_criteria as $key => $value ) {
			$this->assertEquals(
				$value,
				$capacity_relationship[ $key ],
				"Expected capacities_relationship.{$key} to be {$value} but got {$capacity_relationship[ $key ]}"
			);
		}

		$capacities_table = Capacities::table_name();
		$capacities       = DB::get_results( "SELECT * FROM $capacities_table WHERE id = {$capacity_id}", ARRAY_A );

		$this->assertCount( 1, $capacities );

		$capacity = $capacities[0];

		codecept_debug( $capacity );

		foreach ( $capacities_criteria as $key => $value ) {
			$this->assertEquals(
				$value,
				$capacity[ $key ],
				"Expected capacities.{$key} to be {$value} but got {$capacity[ $key ]}"
			);
		}
	}

	protected function asssert_tables_empty( string ...$tables ): void {
		global $wpdb;
		foreach ( $tables as $table ) {
			$this->assertEmpty( $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) );
		}
	}
}