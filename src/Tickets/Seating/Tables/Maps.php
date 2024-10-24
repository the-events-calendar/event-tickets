<?php
/**
 * The Maps table schema.
 *
 * @since TBD
 *
 * @package TEC\Controller\Tables;
 */
	
namespace TEC\Tickets\Seating\Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Maps.
 *
 * @since TBD
 *
 * @package TEC\Controller\Tables;
 */
class Maps extends Table {
	use Custom_Table_Query_Methods;
		
	/**
	 * The schema version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';
		
	/**
	 * The base table name, without the table prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_slr_maps';
		
	/**
	 * The table group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $group = 'tec_slr';
		
	/**
	 * The slug used to identify the custom table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-slr-maps';
		
	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $uid_column = 'id';
		
	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 * @since TBD
	 */
	protected function get_definition() {
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
