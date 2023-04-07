<?php
/**
 * Models the `capacities` custom table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */

namespace TEC\Tickets\Flexible_Tickets\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Capacities.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */
class Capacities extends Table {
	/**
	 * {@inheritdoc}
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * The value that represents unlimited capacity.
	 *
	 * @since TBD
	 */
	public const VALUE_UNLIMITED = -1;

	/**
	 * {@inheritdoc}
	 */
	protected static $base_table_name = 'tec_capacities';

	/**
	 * {@inheritdoc}
	 */
	protected static $group = 'tec_tickets_flexible_tickets';

	/**
	 * {@inheritdoc}
	 */
	protected static $schema_slug = 'tec-ft-capacities';

	/**
	 * {@inheritdoc}
	 */
	protected static $uid_column = 'id';

	/**
	 * {@inheritdoc}
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `$table_name` (
				`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`value` int(11) DEFAULT -1 NOT NULL,
				`mode` varchar(50) DEFAULT '' NOT NULL,
				`name` varchar(50) DEFAULT '' NOT NULL,
				`description` varchar(50) DEFAULT '' NULL,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}
}