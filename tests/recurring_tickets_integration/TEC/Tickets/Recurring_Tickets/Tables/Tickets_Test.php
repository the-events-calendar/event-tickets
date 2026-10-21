<?php

namespace TEC\Tickets\Recurring_Tickets\Tables;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Register;

/**
 * The table is EngDoc section 4.1, exactly: a shipped schema cannot change without a data migration.
 */
class Tickets_Test extends WPTestCase {
	/**
	 * @after
	 */
	public function empty_table(): void {
		DB::query( "SET time_zone = '+00:00'" );
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_have_the_documented_columns(): void {
		$nullable_datetime = [ 'datetime', 'YES', null, '' ];
		$nullable_json     = [ 'mediumtext', 'YES', null, '' ];

		$expected = [
			'id'                     => [ 'bigint unsigned', 'NO', null, 'auto_increment' ],
			'type'                   => [ 'varchar(50)', 'NO', null, '' ],
			'parent_id'              => [ 'bigint unsigned', 'YES', null, '' ],
			'post_id'                => [ 'bigint unsigned', 'NO', null, '' ],
			'occurrence_id'          => [ 'bigint unsigned', 'YES', null, '' ],
			'occurrence_start'       => $nullable_datetime,
			'occurrence_start_utc'   => $nullable_datetime,
			'name'                   => [ 'varchar(255)', 'NO', null, '' ],
			'description'            => [ 'text', 'YES', null, '' ],
			'show_description'       => [ 'tinyint(1)', 'NO', '1', '' ],
			'sku'                    => [ 'varchar(255)', 'YES', null, '' ],
			'price'                  => [ 'bigint unsigned', 'NO', null, '' ],
			'capacity'               => [ 'bigint', 'NO', '-1', '' ],
			'stock'                  => [ 'bigint unsigned', 'YES', null, '' ],
			'sales'                  => [ 'bigint unsigned', 'NO', null, '' ],
			'stock_mode'             => [ 'varchar(20)', 'NO', 'own', '' ],
			'start_date'             => $nullable_datetime,
			'end_date'               => $nullable_datetime,
			'start_date_utc'         => $nullable_datetime,
			'end_date_utc'           => $nullable_datetime,
			'menu_order'             => [ 'int', 'NO', null, '' ],
			'status'                 => [ 'varchar(20)', 'NO', 'publish', '' ],
			'relative_date_settings' => $nullable_json,
			'iac_settings'           => $nullable_json,
			'overrides'              => $nullable_json,
			'created_at'             => [ 'datetime', 'NO', null, '' ],
			'updated_at'             => [ 'timestamp', 'YES', null, 'on update CURRENT_TIMESTAMP' ],
		];

		$this->assertSame( $expected, $this->get_columns() );
	}

	/**
	 * @test
	 */
	public function it_should_have_the_documented_indexes(): void {
		$expected = [
			'PRIMARY'             => [ 'unique' => true, 'columns' => [ 'id' ] ],
			'occurrence_id'       => [ 'unique' => false, 'columns' => [ 'occurrence_id' ] ],
			'post_id'             => [ 'unique' => false, 'columns' => [ 'post_id' ] ],
			'template_occurrence' => [ 'unique' => true, 'columns' => [ 'parent_id', 'occurrence_id' ] ],
		];

		$indexes = $this->get_indexes();
		ksort( $indexes );

		$this->assertSame( $expected, $indexes );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_second_row_for_the_same_template_and_date(): void {
		Tickets::insert( $this->row() );

		$rejected = false;
		try {
			Tickets::insert( $this->row( [ 'name' => 'Another name' ] ) );
		} catch ( DatabaseQueryException $e ) {
			$rejected = true;
		}

		$this->assertTrue( $rejected );
		$this->assertSame( 1, Tickets::get_total_items() );
	}

	/**
	 * @test
	 */
	public function it_should_store_dates_as_given_whatever_the_session_time_zone(): void {
		DB::query( "SET time_zone = '+05:00'" );
		Tickets::insert( $this->row( [ 'start_date' => '2026-11-01 10:00:00', 'occurrence_start' => '2026-11-02 18:30:00' ] ) );
		DB::query( "SET time_zone = '+00:00'" );

		$row = DB::get_row( DB::prepare( 'SELECT start_date, occurrence_start FROM %i', Tickets::table_name() ), ARRAY_A );

		$this->assertSame( [ 'start_date' => '2026-11-01 10:00:00', 'occurrence_start' => '2026-11-02 18:30:00' ], $row );
	}

	/**
	 * @test
	 */
	public function it_should_change_nothing_when_registered_again(): void {
		Tickets::insert( $this->row() );
		$create_table = $this->get_create_table();

		Register::table( Tickets::class );
		// Forces dbDelta to run against the existing table even though the stored version is current.
		( new Tickets() )->update();

		$this->assertSame( $create_table, $this->get_create_table() );
		$this->assertSame( 1, Tickets::get_total_items() );
	}

	/**
	 * @param array<string,int|string> $overrides Column values to replace the defaults with.
	 *
	 * @return array<string,int|string>
	 */
	private function row( array $overrides = [] ): array {
		return array_merge(
			[
				'type'          => 'recurring',
				'parent_id'     => 10,
				'post_id'       => 20,
				'occurrence_id' => 30,
				'name'          => 'General Admission',
				'price'         => 1050,
				'sales'         => 0,
				'menu_order'    => 0,
				'created_at'    => '2026-10-01 00:00:00',
			],
			$overrides
		);
	}

	/**
	 * @return array<string,array{0: string, 1: string, 2: ?string, 3: string}> Column name to [ type, nullable, default, extra ], in table order.
	 */
	private function get_columns(): array {
		$columns = [];
		foreach ( DB::get_results( DB::prepare( 'SHOW COLUMNS FROM %i', Tickets::table_name() ), ARRAY_A ) as $column ) {
			// MySQL < 8.0.19 and MariaDB report an integer display width, later MySQL versions do not.
			$type = preg_replace( '/^(big)?int\(\d+\)/', '$1int', $column['Type'] );
			// MariaDB reports CURRENT_TIMESTAMP as a function call, in lower case.
			$default = null === $column['Default'] ? null : preg_replace( '/^current_timestamp\(\)$/i', 'CURRENT_TIMESTAMP', trim( $column['Default'], "'" ) );
			$extra   = preg_replace( '/current_timestamp\(\)/i', 'CURRENT_TIMESTAMP', $column['Extra'] );

			// MariaDB reports NULL as the default of a nullable column without one.
			if ( 'NULL' === $default ) {
				$default = null;
			}

			$columns[ $column['Field'] ] = [ $type, $column['Null'], $default, $extra ];
		}

		return $columns;
	}

	/**
	 * @return array<string,array{unique: bool, columns: string[]}>
	 */
	private function get_indexes(): array {
		$indexes = [];
		foreach ( DB::get_results( DB::prepare( 'SHOW INDEX FROM %i', Tickets::table_name() ), ARRAY_A ) as $index ) {
			$indexes[ $index['Key_name'] ]['unique']                                = '0' === $index['Non_unique'];
			$indexes[ $index['Key_name'] ]['columns'][ $index['Seq_in_index'] - 1 ] = $index['Column_name'];
		}

		return $indexes;
	}

	private function get_create_table(): string {
		$row = DB::get_row( DB::prepare( 'SHOW CREATE TABLE %i', Tickets::table_name() ), ARRAY_N );

		// The AUTO_INCREMENT counter moves with every insert and is not part of the schema.
		return preg_replace( '/ AUTO_INCREMENT=\d+/', '', $row[1] );
	}
}
