<?php
/**
 * Order Modifiers Meta custom table logic.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\Created_At;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Text_Column;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;

/**
 * Class Order_Modifiers_Meta.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */
class Order_Modifiers_Meta extends Table {

	/**
	 * @since 5.18.0
	 *
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
	protected static $base_table_name = 'tec_order_modifiers_meta';

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
	protected static $schema_slug = 'tec-order-modifiers-meta';

	/**
	 * @since 5.18.0
	 *
	 * @var string The field that uniquely identifies a row in the table.
	 */
	protected static $uid_column = 'id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name( true );

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = new Referenced_ID( 'order_modifier_id' );
				$columns[] = ( new String_Column( 'meta_key' ) )->set_length( 100 )->set_is_index( true );
				$columns[] = new Text_Column( 'meta_value' );
				$columns[] = ( new Integer_Column( 'priority' ) )->set_default( 0 );
				$columns[] = new Created_At( 'created_at' );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since 5.18.0
	 * @since 5.25.0 Removed the `updated_at` column.
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	public function get_definition(): string {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
				CREATE TABLE `$table_name` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`order_modifier_id` BIGINT UNSIGNED NOT NULL,
				`meta_key` VARCHAR(100) NOT NULL,
				`meta_value` TEXT NOT NULL,
				`priority` INT NOT NULL DEFAULT 0,
				`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}
}
