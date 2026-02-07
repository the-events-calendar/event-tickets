<?php
/**
 * This class is used to store the relationship between posts and ticket groups.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */

namespace TEC\Tickets\Flexible_Tickets\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;

/**
 * Class Posts_And_Ticket_Groups.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Custom_Tables;
 */
class Posts_And_Ticket_Groups extends Table {
	/**
	 * {@inheritdoc}
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * {@inheritdoc}
	 */
	protected static $base_table_name = 'tec_posts_and_ticket_groups';

	/**
	 * {@inheritdoc}
	 */
	protected static $group = 'tec_tickets_flexible_tickets';

	/**
	 * {@inheritdoc}
	 */
	protected static $schema_slug = 'tec-ft-posts-and-ticket-groups';

	/**
	 * {@inheritdoc}
	 */
	protected static $uid_column = 'id';

	/**
	 * {@inheritdoc}
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = new Referenced_ID( 'post_id' );
				$columns[] = new Referenced_ID( 'group_id' );
				$columns[] = ( new String_Column( 'type' ) )->set_length( 255 )->set_default( '' );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_definition(): string {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `$table_name` (
				`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`post_id` bigint(20) UNSIGNED NOT NULL,
				`group_id` bigint(20) UNSIGNED NOT NULL,
				`type` varchar(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}

	protected function after_update( array $results ) {
		// If nothing was changed by dbDelta(), bail.
		if ( ! count( $results ) ) {
			return $results;
		}

		global $wpdb;

		$table_name = static::table_name();

		if ( $this->exists() && ! $this->has_foreign_key( 'group_id' ) ) {
			return $results;
		}

		$ticket_groups = Ticket_Groups::table_name();
		$updated = $wpdb->query( "ALTER TABLE `$table_name` ADD FOREIGN KEY ( `group_id` ) REFERENCES `$ticket_groups`(id) ON DELETE CASCADE" );

		$message = $updated ?
			"Added FOREIGN KEY constraint to the $table_name table on $ticket_groups(id)."
			: "Failed to add FOREIGN KEY constraint on the $table_name table.";

		$results[ $table_name . '.user_id' ] = $message;

		return $results;
	}
}
