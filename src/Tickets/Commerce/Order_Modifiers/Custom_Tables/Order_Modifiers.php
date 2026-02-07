<?php
/**
 * Order Modifiers custom table logic.
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Float_Column;
use TEC\Common\StellarWP\Schema\Columns\Created_At;
use TEC\Common\StellarWP\Schema\Columns\Datetime_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Common\StellarWP\Schema\Collections\Index_Collection;
use TEC\Common\StellarWP\Schema\Indexes\Classic_Index;


/**
 * Class Orders_Modifiers.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */
class Order_Modifiers extends Table {
	/**
	 * @since 5.18.0
	 * @since 5.25.0 Removed the `updated_at` column.
	 *
	 * @var string|null The version number for this schema definition.
	 */
	public const SCHEMA_VERSION = '1.1.0';

	/**
	 * @since 5.18.0
	 *
	 * @var string The base table name.
	 */
	protected static $base_table_name = 'tec_order_modifiers';

	/**
	 * @since 5.18.0
	 *
	 * @var string The organizational group this table belongs to.
	 */
	protected static $group = 'tec_order_modifiers_group';

	/**
	 * @since 5.18.0
	 *
	 * @var string|null The slug used to identify the custom table.
	 */
	protected static $schema_slug = 'tec-order-modifiers';

	/**
	 * @since 5.18.0
	 *
	 * @var string The field that uniquely identifies a row in the table.
	 */
	protected static $uid_column = 'id';

	/**
	 * Returns the schema history for this table.
	 *
	 * @since 5.27.0
	 *
	 * @return array<string, callable>
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();
		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = ( new String_Column( 'modifier_type' ) )->set_length( 150 )->set_is_index( true );
				$columns[] = ( new String_Column( 'sub_type' ) )->set_length( 255 );
				$columns[] = ( new Float_Column( 'raw_amount' ) )->set_length( 18 )->set_precision( 6 );
				$columns[] = ( new String_Column( 'slug' ) )->set_length( 150 )->set_is_index( true );
				$columns[] = ( new String_Column( 'display_name' ) )->set_length( 255 );
				$columns[] = ( new String_Column( 'status' ) )->set_length( 20 )->set_default( 'draft' );
				$columns[] = new Created_At( 'created_at' );
				$columns[] = ( new Datetime_Column( 'start_time' ) )->set_nullable( true );
				$columns[] = ( new Datetime_Column( 'end_time' ) )->set_nullable( true );

				$indexes   = new Index_Collection();
				$indexes[] = ( new Classic_Index( 'tec_order_modifier_index_status_modifier_type' ) )->set_columns( 'status', 'modifier_type' );

				return new Table_Schema( $table_name, $columns, $indexes );
			},
		];
	}
}
