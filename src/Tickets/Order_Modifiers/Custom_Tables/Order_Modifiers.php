<?php
/**
 * ${CARET}
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Custom_Tables;
 */

namespace TEC\Tickets\Order_Modifiers\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Orders_Modifiers.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Order_Modifiers\Custom_Tables;
 */
class Order_Modifiers extends Table {
	/**
	 * {@inheritdoc}
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * {@inheritdoc}
	 */
	protected static $base_table_name = 'tec_order_modifiers';

	/**
	 * {@inheritdoc}
	 */
	protected static $group = 'tec_order_modifiers_group';

	/**
	 * {@inheritdoc}
	 */
	protected static $schema_slug = 'tec-order-modifiers';

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
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`post_id` BIGINT UNSIGNED NOT NULL,
				`modifier_type` VARCHAR(255) NOT NULL,
				`sub_type` VARCHAR(255) NOT NULL,
				`fee_amount_cents` INT NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`display_name` VARCHAR(255) NOT NULL,
				`status` VARCHAR(20) NOT NULL DEFAULT 'draft',
				`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`start_time` TIMESTAMP NULL DEFAULT NULL,
				`end_time` TIMESTAMP NULL DEFAULT NULL,
				`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}

	/**
	 * {@inheritdoc}
	 *
	 * phpcs:disable
	 * WordPress.DB.DirectDatabaseQuery.DirectQuery,
	 * WordPress.DB.DirectDatabaseQuery.NoCaching,
	 * WordPress.DB.DirectDatabaseQuery.SchemaChange,
	 * WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	 */
	protected function after_update( array $results ) {
		// If nothing was changed by dbDelta(), bail.
		if ( ! count( $results ) ) {
			return $results;
		}

		global $wpdb;
		$table_name = self::table_name( true );

		// Add a regular index on post_id.
		if ( $this->exists() && ! $this->has_index( 'tec_order_modifier_indx_post_id' ) ) {
			$updated = $wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `tec_order_modifier_indx_post_id` ( `post_id` )" );

			if ( $updated ) {
				$message = "Added index to the {$table_name} table on post_id.";
			} else {
				$message = "Failed to add an index on the {$table_name} table.";
			}

			$results[ $table_name . '.post_id' ] = $message;
		}

		// Add a regular index on slug (optional, if slug is often queried alone).
		if ( $this->exists() && ! $this->has_index( 'tec_order_modifier_indx_slug' ) ) {
			$updated = $wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `tec_order_modifier_indx_slug` ( `slug` )" );

			if ( $updated ) {
				$message = "Added index to the {$table_name} table on slug.";
			} else {
				$message = "Failed to add an index on the {$table_name} table.";
			}

			$results[ $table_name . '.slug' ] = $message;
		}

		// Add a composite index on post_id and status.
		if ( $this->exists() && ! $this->has_index( 'tec_order_modifier_indx_post_id_status' ) ) {
			$updated = $wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `tec_order_modifier_indx_post_id_status` ( `post_id`, `status` )" );

			if ( $updated ) {
				$message = "Added composite index to the {$table_name} table on post_id and status.";
			} else {
				$message = "Failed to add a composite index on the {$table_name} table.";
			}

			$results[ $table_name . '.post_id_status' ] = $message;
		}

		// Add a composite index on status, modifier_type, and slug.
		if ( $this->exists() && ! $this->has_index( 'tec_order_modifier_indx_status_modifier_type_slug' ) ) {
			$updated = $wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `tec_order_modifier_indx_status_modifier_type_slug` ( `status`, `modifier_type`, `slug` )" );

			if ( $updated ) {
				$message = "Added composite index to the {$table_name} table on status, modifier_type, and slug.";
			} else {
				$message = "Failed to add a composite index on the {$table_name} table.";
			}

			$results[ $table_name . '.status_modifier_type_slug' ] = $message;
		}

		return $results;
	}
	/** @phpcs:enable */
}
