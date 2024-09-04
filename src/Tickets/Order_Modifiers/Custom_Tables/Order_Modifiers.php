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
}
