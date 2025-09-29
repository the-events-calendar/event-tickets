<?php
/**
 * The Maps table schema.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Common\StellarWP\Schema\Collections\Index_Collection;
use TEC\Common\StellarWP\Schema\Indexes\Primary_Key;


/**
 * Class Maps.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Tables;
 */
class Maps extends Table {
	/**
	 * The schema version.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_slr_maps';

	/**
	 * The table group.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $group = 'tec_slr';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-slr-maps';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 5.16.0
	 *
	 * @var string
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
			self::SCHEMA_VERSION => function() use ( $table_name ) {
				$columns = new Column_Collection();
				$columns[] = ( new String_Column( 'id' ) )->set_length( 36 );
				$columns[] = ( new String_Column( 'name' ) )->set_length( 255 );
				$columns[] = ( new Integer_Column( 'seats' ) )->set_length( 11 )->set_default( 0 );
				$columns[] = ( new String_Column( 'screenshot_url' ) )->set_length( 255 )->set_default( '' );

				$indexes = new Index_Collection();
				$indexes[] = ( new Primary_Key( 'id' ) )->set_columns( 'id' );

				return new Table_Schema( $table_name, $columns, $indexes );
			},
		];
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 * @since 5.16.0
	 */
	public function get_definition(): string {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `{$table_name}` (
				`id` varchar(36) NOT NULL,
				`name` varchar(255) NOT NULL,
				`seats` int(11) NOT NULL DEFAULT '0',
				`screenshot_url` varchar(255) DEFAULT '',
				PRIMARY KEY (`id`)
			) {$charset_collate};
		";
	}
}
