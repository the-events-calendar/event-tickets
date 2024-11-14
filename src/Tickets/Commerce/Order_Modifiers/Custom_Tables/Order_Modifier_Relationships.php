<?php
/**
 * Order Modifiers Relationships custom table logic.
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;

/**
 * Class Order_Modifier_Relationships.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;
 */
class Order_Modifier_Relationships extends Abstract_Custom_Table {
	/**
	 * @since TBD
	 *
	 * @var string|null The version number for this schema definition.
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * @since TBD
	 *
	 * @var string The base table name.
	 */
	protected static $base_table_name = 'tec_order_modifier_relationships';

	/**
	 * @since TBD
	 *
	 * @var string The organizational group this table belongs to.
	 */
	protected static $group = 'tec_order_modifiers_group';

	/**
	 * @since TBD
	 *
	 * @var string|null The slug used to identify the custom table.
	 */
	protected static $schema_slug = 'tec-order-modifiers-relationships';

	/**
	 * @since TBD
	 *
	 * @var string The field that uniquely identifies a row in the table.
	 */
	protected static $uid_column = 'object_id';

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since TBD
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
				`object_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`modifier_id` BIGINT UNSIGNED NOT NULL,
				`post_id`  BIGINT UNSIGNED NOT NULL,
				`post_type` VARCHAR(20) NOT NULL,
				PRIMARY KEY (`object_id`)
			) $charset_collate;
		";
	}

	/**
	 * Allows extending classes that require it to run some methods
	 * immediately after the table creation or update.
	 *
	 * @since TBD
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

		$table_name = self::table_name();

		// Helper method to check and add indexes.
		$results = $this->check_and_add_index( $results, $table_name, 'tec_order_modifier_relationship_indx_modifier_id', 'modifier_id' );
		$results = $this->check_and_add_index( $results, $table_name, 'tec_order_modifier_relationship_indx_post_type', 'post_id,post_type' );
		$results = $this->check_and_add_index( $results, $table_name, 'tec_order_modifier_relationship_indx_composite_join', 'modifier_id, post_id, post_type' );

		return $results;
	}
}
