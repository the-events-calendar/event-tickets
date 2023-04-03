<?php
/**
 * Models the `posts_and_posts` custom table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */

namespace TEC\Tickets\Flexible_Tickets\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Posts_And_Posts.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */
class Posts_And_Posts extends Table {
	/**
	 * {@inheritdoc}
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * {@inheritdoc}
	 */
	protected static $base_table_name = 'tec_posts_and_posts';

	/**
	 * {@inheritdoc}
	 */
	protected static $group = 'tec_tickets_flexible_tickets';

	/**
	 * {@inheritdoc}
	 */
	protected static $schema_slug = 'tec-ft-post-and-posts';

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
				`post_id_1` bigint(20) NOT NULL,
				`post_id_2` bigint(20) NOT NULL,
				`type` varchar(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}
}