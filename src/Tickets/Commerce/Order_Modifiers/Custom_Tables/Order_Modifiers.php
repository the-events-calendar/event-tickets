<?php
/**
 * Order Modifiers custom table logic.
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;

use TEC\Common\Integrations\Custom_Table_Abstract as Table;

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
	 *
	 * @var string|null The version number for this schema definition.
	 */
	public const SCHEMA_VERSION = '1.0.0';

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
	 * An array of all the columns in the table.
	 *
	 * @since 5.20.0
	 *
	 * @var string[]
	 */
	public static function get_columns(): array {
		return [
			'id',
			'modifier_type',
			'sub_type',
			'raw_amount',
			'display_name',
			'status',
			'created_at',
			'start_time',
			'end_time',
			'updated_at',
		];
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since 5.18.0
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `$table_name` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`modifier_type` VARCHAR(150) NOT NULL,
				`sub_type` VARCHAR(255) NOT NULL,
				`raw_amount` DECIMAL(18,6) NOT NULL,
				`slug` VARCHAR(150) NOT NULL,
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
	 * Allows extending classes that require it to run some methods
	 * immediately after the table creation or update.
	 *
	 * @since 5.18.0
	 *
	 * @param array<string,string> $results A map of results in the format
	 *                                      returned by the `dbDelta` function.
	 *
	 * @return array<string,string> A map of results in the format returned by
	 *                              the `dbDelta` function.
	 */
	protected function after_update( array $results ): array {
		// If nothing was changed by dbDelta(), bail.
		if ( ! count( $results ) ) {
			return $results;
		}

		// Check if the table exists first.
		if ( ! $this->exists() ) {
			return $results;
		}

		// Helper method to check and add indexes.
		$results = $this->check_and_add_index( $results, 'tec_order_modifier_index_slug', 'slug' );
		$results = $this->check_and_add_index( $results, 'tec_order_modifier_index_modifier_type', 'modifier_type' );
		$results = $this->check_and_add_index( $results, 'tec_order_modifier_index_status_modifier_type', 'status, modifier_type' );

		return $results;
	}
}
