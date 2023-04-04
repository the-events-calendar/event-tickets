<?php
/**
 * Models the `posts_and_users` custom table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */

namespace TEC\Tickets\Flexible_Tickets\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Posts_And_Users.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */
class Posts_And_Users extends Table {
	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 */
	protected static $base_table_name = 'tec_posts_and_users';

	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 */
	protected static $group = 'tec_tickets_flexible_tickets';

	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 */
	protected static $schema_slug = 'tec-ft-post-and-users';

	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 */
	protected static $uid_column = 'id';

	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `$table_name` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`user_id` bigint(20) unsigned NOT NULL,
				`post_id` bigint(20) unsigned NOT NULL,
				`type` varchar(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 */
	protected function after_update( array $results ) {
		// If nothing was changed by dbDelta(), bail.
		if ( ! count( $results ) ) {
			return $results;
		}

		global $wpdb;

		$table_name = static::table_name();

		if ( $this->exists() && ! $this->has_foreign_key( 'user_id' ) ) {
			$users   = $wpdb->users;
			$updated = $wpdb->query( "ALTER TABLE `$table_name`
    			ADD FOREIGN KEY ( `user_id` ) REFERENCES $users(ID) ON DELETE CASCADE" );

			$message = $updated ?
				"Added FOREIGN KEY constraint to the $table_name table on $users(ID)."
				: "Failed to add FOREIGN KEY constraint on the $table_name table.";

			$results[ $table_name . '.user_id' ] = $message;
		}

		if ( $this->exists() && ! $this->has_foreign_key( 'post_id' ) ) {
			$posts   = $wpdb->posts;
			$updated = $wpdb->query( "ALTER TABLE `$table_name`
    			ADD FOREIGN KEY ( `post_id` ) REFERENCES $posts(ID) ON DELETE CASCADE" );

			$message = $updated ?
				"Added FOREIGN KEY constraint to the $table_name table on $posts(ID)."
				: "Failed to add FOREIGN KEY constraint on the $table_name table.";

			$results[ $table_name . '.post_id' ] = $message;
		}

		return $results;
	}
}