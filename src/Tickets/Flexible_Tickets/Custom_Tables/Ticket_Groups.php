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
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Text_Column;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Columns\Float_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.DirectQuerySchemaChange

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
	public const SCHEMA_VERSION = '1.2.0';

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
	 * Internal way to track prior versions.
	 *
	 * @since 5.24.1.1
	 *
	 * @var array<string>
	 */
	protected static $versions = [
		'1.0.0',
		'1.1.0',
		'1.2.0',
	];

	/**
	 * {@inheritdoc}
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = ( new String_Column( 'slug' ) )->set_length( 255 )->set_default( '' );
				$columns[] = new Text_Column( 'data' );
				$columns[] = ( new Integer_Column( 'capacity' ) )->set_length( 11 )->set_default( 0 );
				$columns[] = ( new Float_Column( 'cost' ) )->set_length( 10 )->set_precision( 2 )->set_default( 0.0 );
				$columns[] = ( new String_Column( 'name' ) )->set_length( 255 )->set_default( '' );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 5.24.1 Add `name`, `capacity`, and `cost` columns for Ticket Presets use.
	 */
	public function get_definition(): string {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `$table_name` (
				`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`slug` varchar(255) DEFAULT '' NOT NULL,
				`data` text NOT NULL,
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
	 * @since 5.24.1.1    Handle MySQL compatibility fix for TEXT column DEFAULT value removal.
	 */
	protected function after_update( array $results = [] ) {
		$results          = parent::after_update( $results );
		$previous_version = $this->get_stored_previous_version();

		// Run version-specific migrations.
		if ( version_compare( $previous_version, '1.1.0', '<' ) ) {
			$success = $this->migrate_to_1_1_0( $results );

			if ( ! $success ) {
				// Roll back the schema versions here - because we failed.
				update_option( $this->get_schema_version_option(), $previous_version );
				// We can hardcode this version here. We know that 1.0.0 is the first version.
				update_option( $this->get_schema_previous_version_option(), '1.0.0' );
				return $results;
			}
		}

		if ( version_compare( $previous_version, '1.2.0', '<' ) ) {
			$success = $this->migrate_to_1_2_0( $results );

			if ( ! $success ) {
				// Roll back the schema versions here - because we failed.
				update_option( $this->get_schema_version_option(), $previous_version );
				// Roll back to the previous version. This ensures  that the update will run again,
				// this assumes that if we got here the 1.1.0 migration has run.
				update_option( $this->get_schema_previous_version_option(), '1.1.0' );

				return $results;
			}
		}

		// Fallback.
		return $results;
	}

	/**
	 * Migrates data from JSON to dedicated columns for schema version 1.1.0.
	 *
	 * @since 5.24.1
	 *
	 * @param array $results The results array to update. Passed by reference.
	 *
	 * @return bool Whether the migration was successful.
	 */
	protected function migrate_to_1_1_0( array &$results = [] ): bool {
		global $wpdb;
		$table_name = self::table_name();

		$start_transaction = $wpdb->query( 'START TRANSACTION' );

		if ( false === $start_transaction ) {
			$results[ $table_name . '.migration' ] = sprintf(
				// Translators: %1$s: table name.
				__( 'Failed to start 1.1.0 migration transaction for %1$s table.', 'event-tickets' ),
				$table_name
			);
			return false;
		}

		$remaining = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count(id) FROM %i WHERE name = '' OR name IS NULL",
				$table_name
			)
		);

		if ( null === $remaining ) {
			$results[ $table_name . '.migration' ] = sprintf(
				// Translators: %1$s: table name.
				__( 'Failed to get remaining rows for 1.1.0 migration for %1$s table.', 'event-tickets' ),
				$table_name
			);
			return false;
		}

		$migrated = 0;
		$failed   = 0;

		while ( $remaining > 0 ) {
			// Get all rows where name is empty (indicating data hasn't been migrated yet).

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, data FROM %i WHERE name = '' OR name IS NULL LIMIT %d,1000",
					$table_name,
					$migrated
				)
			);

			if ( ! is_array( $rows ) ) {
				$results[ $table_name . '.migration' ] = sprintf(
					// Translators: %1$s: table name.
					__( 'Failed to get rows for 1.1.0 migration for %1$s table.', 'event-tickets' ),
					$table_name
				);
				return false;
			}

			if ( empty( $rows ) ) {
				$results[ $table_name . '.migration' ] = sprintf(
					// Translators: %1$s: table name.
					__( 'No rows needed 1.1.0 migration for %1$s table.', 'event-tickets' ),
					$table_name
				);
				return $wpdb->query( 'COMMIT' ) !== false;
			}

			foreach ( $rows as $row ) {
				$data = json_decode( $row->data, true );

				if ( empty( $data ) ) {
					++$failed;
					break;
				}

				// Extract values from data JSON.
				$name     = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
				$capacity = isset( $data['capacity'] ) ? absint( $data['capacity'] ) : 0;
				$cost     = isset( $data['cost'] ) ? sanitize_text_field( (string) $data['cost'] ) : '0.000000';

				// Update the row with extracted values.
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

				if ( $updated !== false ) {
					++$migrated;
				} else {
					++$failed;
					// Break on first failure.
					break;
				}
			}

			$remaining -= count( $rows );
		}

		// Add a message to the results array.
		if ( $failed > 0 ) {
			$results[ $table_name . '.migration' ] = sprintf(
				// Translators: %1$s: table name.
				__( '1.1.0 migration failed, refresh the page to re-run.', 'event-tickets' ),
				$table_name
			);

			// Rollback data transaction.
			$wpdb->query( 'ROLLBACK' );

			return false;
		}

		$results[ $table_name . '.migration' ] = sprintf(
			// Translators: %1$d: number of rows migrated, %2$s: table name.
			__( '1.1.0 migrated %1$d rows in the %2$s table.', 'event-tickets' ),
			$migrated,
			$table_name
		);

		return $wpdb->query( 'COMMIT' ) !== false;
	}

	/**
	 * Handles MySQL compatibility migration for schema version 1.2.0.
	 *
	 * Ensures all `data` column values are properly set since we removed
	 * the DEFAULT ('') clause for compatibility with older MySQL versions.
	 *
	 * @since 5.24.1.1
	 *
	 * @param array $results The results array to update. Passed by reference.
	 *
	 * @return bool Whether the migration was successful.
	 */
	protected function migrate_to_1_2_0( array &$results = [] ): bool {
		global $wpdb;
		$table_name = self::table_name();

		$start_transaction = $wpdb->query( 'START TRANSACTION' );

		if ( false === $start_transaction ) {
			$results[ $table_name . '.migration' ] = sprintf(
				// Translators: %1$s: table name.
				__( 'Failed to start transaction for %1$s table.', 'event-tickets' ),
				$table_name
			);
			return false;
		}

		// Check if any rows have NULL or problematic data values.
		$rows_with_null_data = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE data IS NULL OR data = '' LIMIT 1000",
				$table_name
			)
		);

		if ( ! $rows_with_null_data ) {
			$results[ $table_name . '.compatibility_migration' ] = sprintf(
				// Translators: %1$s: table name.
				__( 'No rows needed MySQL compatibility migration for %1$s table.', 'event-tickets' ),
				$table_name
			);
			return $wpdb->query( 'COMMIT' ) !== false;
		}

		$migrated = 0;
		$failed   = 0;

		while ( $rows_with_null_data > 0 ) {
			// Get all rows where data is empty or NULL.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id FROM %i WHERE data IS NULL OR data = '' LIMIT %d,1000",
					$table_name,
					$migrated
				)
			);

			if ( ! is_array( $rows ) ) {
				$results[ $table_name . '.migration' ] = sprintf(
					// Translators: %1$s: table name.
					__( 'Failed to get rows for 1.2.0 migration for %1$s table.', 'event-tickets' ),
					$table_name
				);
				$wpdb->query( 'ROLLBACK' );
				return false;
			}

			if ( empty( $rows ) ) {
				$results[ $table_name . '.migration' ] = sprintf(
					// Translators: %1$s: table name.
					__( 'No rows needed 1.2.0 migration for %1$s table.', 'event-tickets' ),
					$table_name
				);
				return $wpdb->query( 'COMMIT' ) !== false;
			}

			foreach ( $rows as $row ) {
				// Update the row with empty string for data.
				$updated = $wpdb->update(
					$table_name,
					[ 'data' => '{}' ],
					[ 'id' => $row->id ],
					[ '%s' ],
					[ '%d' ]
				);

				if ( $updated !== false ) {
					++$migrated;
				} else {
					++$failed;
					// Break on first failure.
					break;
				}
			}

			$rows_with_null_data -= count( $rows );
		}

		// Add a message to the results array.
		if ( $failed > 0 ) {
			$results[ $table_name . '.migration' ] = sprintf(
				// Translators: %1$s: table name.
				__( '1.2.0 migration failed, refresh the page to re-run.', 'event-tickets' ),
				$table_name
			);

			// Rollback data transaction.
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$results[ $table_name . '.migration' ] = sprintf(
			// Translators: %1$d: number of rows migrated, %2$s: table name.
			__( '1.2.0 migrated %1$d rows in the %2$s table.', 'event-tickets' ),
			$migrated,
			$table_name
		);

		return $wpdb->query( 'COMMIT' ) !== false;
	}
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.DirectQuerySchemaChange
