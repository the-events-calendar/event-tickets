<?php
/**
 * Models the `capacities_relationships` custom table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */

namespace TEC\Tickets\Flexible_Tickets\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Capacities_Relationships.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */
class Capacities_Relationships extends Table {
	/**
	 * {@inheritdoc}
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * {@inheritdoc}
	 */
	protected static $base_table_name = 'tec_capacities_relationships';

	/**
	 * {@inheritdoc}
	 */
	protected static $group = 'tec_tickets_flexible_tickets';

	/**
	 * {@inheritdoc}
	 */
	protected static $schema_slug = 'tec-ft-capacities-relationships';

	/**
	 * {@inheritdoc}
	 */
	protected static $uid_column = 'id';

	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();
		$capacities      = Capacities::table_name( true );

		return "
			CREATE TABLE `$table_name` (
				`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`capacity_id` int(11) UNSIGNED NOT NULL,
				`object_id` bigint(20) UNSIGNED NOT NULL,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}

	protected function after_update( array $results ) {
		// If nothing was changed by dbDelta(), bail.
		if ( ! count( $results ) ) {
			return $results;
		}

		global $wpdb;

		$table_name = static::table_name();

		if ( $this->exists() && $this->has_foreign_key( 'capacity_id' ) ) {
			return $results;
		}

		$capacities = Capacities::table_name();
		$updated    = $wpdb->query( "ALTER TABLE $table_name
			ADD FOREIGN KEY ( capacity_id ) REFERENCES $capacities(id) ON DELETE CASCADE" );

		$message = $updated ?
			"Added FOREIGN KEY constraint to the $table_name table on $capacities.id."
			: "Failed to add FOREIGN KEY constraint on the $table_name table to $capacities.id.";

		$results[ $table_name . '.user_id' ] = $message;

		return $results;
	}
}