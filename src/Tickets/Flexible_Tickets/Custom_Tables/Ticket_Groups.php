<?php
/**
 * Models the `ticket_groups` custom table.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */

namespace TEC\Tickets\Flexible_Tickets\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Ticket_Groups.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */
class Ticket_Groups extends Table {
	/**
	 * {@inheritdoc}
	 */
	public const SCHEMA_VERSION = '1.1.0';

	/**
	 * {@inheritdoc}
	 */
	protected static $base_table_name = 'tec_ticket_groups';

	/**
	 * {@inheritdoc}
	 */
	protected static $group = 'tec_tickets_flexible_tickets';

	/**
	 * {@inheritdoc}
	 */
	protected static $schema_slug = 'tec-ft-ticket-groups';

	/**
	 * {@inheritdoc}
	 */
	protected static $uid_column = 'id';

	/**
	 * {@inheritdoc}
	 *
	 * @since 5.24.1 Add `name`, `capacity`, and `cost` columns for Ticket Presets use.
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `$table_name` (
				`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`slug` varchar(255) DEFAULT '' NOT NULL,
				`data` text DEFAULT ('') NOT NULL,
				`capacity` int(11) DEFAULT 0 NOT NULL,
				`cost` decimal(10,2) DEFAULT 0 NOT NULL,
				`name` varchar(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 5.24.1 Handle hydrating new columns from `data` JSON for Ticket Presets use, if needed.
	 */
	protected function after_update( array $results = [] ) {
		$results = parent::after_update( $results );

		// Run version-specific migrations.
		if ( self::SCHEMA_VERSION === '1.1.0' ) {
			$results = $this->migrate_to_1_1_0( $results );
		}

		return $results;
	}

	/**
	 * Migrates data from JSON to dedicated columns for schema version 1.1.0.
	 *
	 * @since 5.24.1
	 *
	 * @param array $results The results array to update.
	 *
	 * @return array The updated results array.
	 */
	protected function migrate_to_1_1_0( array $results = [] ) {
		global $wpdb;
		$table_name = self::table_name();

		// Get all rows where name is empty (indicating data hasn't been migrated yet).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.DirectQuerySchemaChange
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, data FROM %i WHERE name = '' OR name IS NULL",
				$table_name
			)
		);

		if ( empty( $rows ) ) {
			$results[ $table_name . '.migration' ] = "No rows needed migration for {$table_name} table.";
			return $results;
		}

		$migrated = 0;
		$failed   = 0;

		foreach ( $rows as $row ) {
			$data = json_decode( $row->data, true );

			if ( empty( $data ) ) {
				++$failed;
				continue;
			}

			// Extract values from data JSON.
			$name     = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
			$capacity = isset( $data['capacity'] ) ? absint( $data['capacity'] ) : 0;
			$cost     = isset( $data['cost'] ) ? (string) $data['cost'] : '0.000000';

			// Update the row with extracted values.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.DirectQuerySchemaChange
			$updated = $wpdb->update(
				$table_name,
				[
					'name'     => $name,
					'capacity' => $capacity,
					'cost'     => $cost,
				],
				[ 'id' => $row->id ],
				[ '%s', '%d', '%s' ],
				[ '%d' ]
			);

			if ( $updated ) {
				++$migrated;
			} else {
				++$failed;
			}
		}

		// Add a message to the results array.
		if ( $failed > 0 ) {
			$results[ $table_name . '.migration' ] = sprintf(
				'Migrated %d rows and failed to migrate %d rows in the %s table.',
				$migrated,
				$failed,
				$table_name
			);
		} else {
			$results[ $table_name . '.migration' ] = sprintf(
				'Migrated %d rows in the %s table.',
				$migrated,
				$table_name
			);
		}

		return $results;
	}
}
