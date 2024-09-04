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
 * Class Order_Modifiers_Meta.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Order_Modifiers\Custom_Tables;
 */
class Order_Modifiers_Meta extends Table {
	/**
	 * {@inheritdoc}
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * {@inheritdoc}
	 */
	protected static $base_table_name = 'tec_order_modifiers_meta';

	/**
	 * {@inheritdoc}
	 */
	protected static $group = 'tec_order_modifiers_group';

	/**
	 * {@inheritdoc}
	 */
	protected static $schema_slug = 'tec-order-modifiers-meta';

	/**
	 * {@inheritdoc}
	 */
	protected static $uid_column = 'id';

	/**
	 * {@inheritdoc}
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name        = self::table_name( true );
		$charset_collate   = $wpdb->get_charset_collate();
		$parent_table_name = Order_Modifiers::table_name();
		$parent_table_uid  = Order_Modifiers::uid_column();

		return "
			CREATE TABLE `$table_name` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`order_modifier_id` BIGINT UNSIGNED NOT NULL,
				`meta_key` VARCHAR(100) NOT NULL,
				`meta_value` TEXT NOT NULL,
				`priority` INT NOT NULL DEFAULT 0,
				`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				FOREIGN KEY (`order_modifier_id`)
				REFERENCES $parent_table_name($parent_table_uid)
				ON DELETE CASCADE
			) $charset_collate;
		";
	}
}
