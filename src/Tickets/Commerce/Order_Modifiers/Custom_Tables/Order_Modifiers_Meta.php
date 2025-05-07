<?php
/**
 * Order Modifiers Meta custom table logic.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;

use TEC\Common\Integrations\Custom_Table_Abstract as Table;

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
	 * @var string|null The version number for this schema definition.
	 */
	public const SCHEMA_VERSION = '1.0.0';

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
	 * @since 5.20.0
	 *
	 * @var string[]
	 */
	public static function get_columns(): array {
		return [
			'id',
			'order_modifier_id',
			'meta_key',
			'meta_value',
			'priority',
			'created_at',
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
				`order_modifier_id` BIGINT UNSIGNED NOT NULL,
				`meta_key` VARCHAR(100) NOT NULL,
				`meta_value` TEXT NOT NULL,
				`priority` INT NOT NULL DEFAULT 0,
				`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

		// Helper method to check and add indexes.
		$results = $this->check_and_add_index( $results, 'tec_order_modifier_meta_index_order_modifier_id', 'order_modifier_id' );
		$results = $this->check_and_add_index( $results, 'tec_order_modifier_meta_index_meta_key', 'meta_key' );

		return $results;
	}
}
