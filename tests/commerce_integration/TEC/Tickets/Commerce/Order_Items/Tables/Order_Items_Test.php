<?php

namespace TEC\Tickets\Commerce\Order_Items\Tables;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Register;

class Order_Items_Test extends WPTestCase {
	/**
	 * @after
	 */
	public function empty_table(): void {
		DB::query( DB::prepare( 'DELETE FROM %i', Order_Items::table_name() ) );
	}

	public function test_columns_match_the_documented_schema(): void {
		$bigint_unsigned = [ 'bigint unsigned', 'YES', null ];
		$line_identity   = [ 'bigint unsigned', 'NO', null ];

		$expected = [
			'id'                    => [ 'bigint unsigned', 'NO', null ],
			'order_id'              => [ 'bigint unsigned', 'NO', null ],
			'type'                  => [ 'varchar(50)', 'NO', null ],
			'item_key'              => [ 'varchar(191)', 'YES', null ],
			'position'              => [ 'int unsigned', 'NO', null ],
			'ticket_id'             => $line_identity,
			'modifier_id'           => $line_identity,
			'purchase_rule_id'      => $line_identity,
			'event_id'              => $bigint_unsigned,
			'post_id'               => $bigint_unsigned,
			'occurrence_id'         => $bigint_unsigned,
			'event_title'           => [ 'varchar(255)', 'YES', null ],
			'event_start_date'      => [ 'datetime', 'YES', null ],
			'event_start_date_utc'  => [ 'datetime', 'YES', null ],
			'name'                  => [ 'varchar(255)', 'NO', null ],
			'currency'              => [ 'varchar(3)', 'NO', null ],
			'sku'                   => [ 'varchar(255)', 'YES', null ],
			'ticket_type'           => [ 'varchar(50)', 'YES', null ],
			'quantity'              => [ 'int', 'NO', null ],
			'price'                 => [ 'bigint', 'NO', null ],
			'regular_price'         => [ 'bigint', 'YES', null ],
			'sub_total'             => [ 'bigint', 'NO', null ],
			'regular_sub_total'     => [ 'bigint', 'YES', null ],
			'extra'                 => [ 'mediumtext', 'YES', null ],
			'created_at'            => [ 'timestamp', 'NO', 'CURRENT_TIMESTAMP' ],
		];

		$this->assertSame( $expected, $this->get_columns() );
		$this->assertSame( 'auto_increment', DB::get_var( DB::prepare( 'SHOW COLUMNS FROM %i WHERE Field = %s', Order_Items::table_name(), 'id' ), 5 ) );
	}

	public function test_indexes_match_the_documented_schema(): void {
		$expected = [
			'PRIMARY'             => [ 'unique' => true, 'columns' => [ 'id' ] ],
			'order_id'            => [ 'unique' => false, 'columns' => [ 'order_id' ] ],
			'ticket_id'           => [ 'unique' => false, 'columns' => [ 'ticket_id' ] ],
			'modifier_id'         => [ 'unique' => false, 'columns' => [ 'modifier_id' ] ],
			'purchase_rule_id'    => [ 'unique' => false, 'columns' => [ 'purchase_rule_id' ] ],
			'event_id'            => [ 'unique' => false, 'columns' => [ 'event_id' ] ],
			'post_id'             => [ 'unique' => false, 'columns' => [ 'post_id' ] ],
			'occurrence_id'       => [ 'unique' => false, 'columns' => [ 'occurrence_id' ] ],
			'order_line_identity' => [ 'unique' => true, 'columns' => [ 'order_id', 'ticket_id', 'modifier_id', 'purchase_rule_id' ] ],
		];

		$indexes = $this->get_indexes();
		ksort( $expected );
		ksort( $indexes );

		$this->assertSame( $expected, $indexes );
	}

	public function test_registering_again_changes_nothing_and_keeps_rows(): void {
		Order_Items::insert( $this->row( [ 'order_id' => 1 ] ) );
		$create_table = $this->get_create_table();

		Register::table( Order_Items::class );
		// Forces dbDelta to run against the existing table even though the stored version is current.
		( new Order_Items() )->update();

		$this->assertSame( $create_table, $this->get_create_table() );
		$this->assertSame( 1, Order_Items::get_total_items() );
	}

	public function test_money_columns_keep_negative_values_and_currency(): void {
		Order_Items::insert( $this->row( [ 'type' => 'coupon', 'price' => -500, 'sub_total' => -500, 'currency' => 'EUR' ] ) );

		$row = DB::get_row( DB::prepare( 'SELECT price, sub_total, currency FROM %i', Order_Items::table_name() ), ARRAY_A );

		$this->assertSame( [ 'price' => '-500', 'sub_total' => '-500', 'currency' => 'EUR' ], $row );
	}

	public function line_identity_provider(): Generator {
		yield 'same order, ticket, modifier and rule' => [ [], 1 ];
		yield 'fee line for the same ticket' => [ [ 'type' => 'fee', 'modifier_id' => 3 ], 2 ];
		yield 'same combination in another order' => [ [ 'order_id' => 2 ], 2 ];
		yield 'same item key, another ticket' => [ [ 'ticket_id' => 8 ], 2 ];
	}

	/**
	 * @dataProvider line_identity_provider
	 */
	public function test_an_order_line_is_unique_by_ticket_modifier_and_rule( array $second, int $expected ): void {
		Order_Items::insert( $this->row( [ 'ticket_id' => 7 ] ) );

		$rejected = false;
		try {
			Order_Items::insert( $this->row( array_merge( [ 'ticket_id' => 7 ], $second ) ) );
		} catch ( DatabaseQueryException $e ) {
			$rejected = true;
		}

		$this->assertSame( 1 === $expected, $rejected );
		$this->assertSame( $expected, Order_Items::get_total_items() );
	}

	/**
	 * @param array<string,int|string> $overrides Column values to replace the defaults with.
	 *
	 * @return array<string,int|string>
	 */
	private function row( array $overrides = [] ): array {
		return array_merge(
			[
				'order_id'  => 1,
				'type'      => 'ticket',
				'item_key'  => '0',
				'position'  => 0,
				'ticket_id' => 0,
				'modifier_id' => 0,
				'purchase_rule_id' => 0,
				'name'      => 'General Admission',
				'currency'  => 'USD',
				'quantity'  => 1,
				'price'     => 1050,
				'sub_total' => 1050,
			],
			$overrides
		);
	}

	/**
	 * @return array<string,array{0: string, 1: string, 2: ?string}> Column name to [ type, nullable, default ], in table order.
	 */
	private function get_columns(): array {
		$columns = [];
		foreach ( DB::get_results( DB::prepare( 'SHOW COLUMNS FROM %i', Order_Items::table_name() ), ARRAY_A ) as $column ) {
			// MySQL < 8.0.19 and MariaDB report an integer display width, later MySQL versions do not.
			$columns[ $column['Field'] ] = [ preg_replace( '/^(big)?int\(\d+\)/', '$1int', $column['Type'] ), $column['Null'], $column['Default'] ];
		}

		return $columns;
	}

	/**
	 * @return array<string,array{unique: bool, columns: string[]}>
	 */
	private function get_indexes(): array {
		$indexes = [];
		foreach ( DB::get_results( DB::prepare( 'SHOW INDEX FROM %i', Order_Items::table_name() ), ARRAY_A ) as $index ) {
			$indexes[ $index['Key_name'] ]['unique']                              = '0' === $index['Non_unique'];
			$indexes[ $index['Key_name'] ]['columns'][ $index['Seq_in_index'] - 1 ] = $index['Column_name'];
		}

		return $indexes;
	}

	private function get_create_table(): string {
		$row = DB::get_row( DB::prepare( 'SHOW CREATE TABLE %i', Order_Items::table_name() ), ARRAY_N );

		// The AUTO_INCREMENT counter moves with every insert and is not part of the schema.
		return preg_replace( '/ AUTO_INCREMENT=\d+/', '', $row[1] );
	}
}
