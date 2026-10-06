<?php
/**
 * The Recurring Event Tickets custom table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Tables
 */

namespace TEC\Tickets\Recurring_Tickets\Tables;

use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Collections\Index_Collection;
use TEC\Common\StellarWP\Schema\Columns\Boolean_Column;
use TEC\Common\StellarWP\Schema\Columns\Column_Types;
use TEC\Common\StellarWP\Schema\Columns\Datetime_Column;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Columns\PHP_Types;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Text_Column;
use TEC\Common\StellarWP\Schema\Columns\Updated_At;
use TEC\Common\StellarWP\Schema\Indexes\Unique_Key;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;

/**
 * Class Tickets.
 *
 * One row per template ticket and date of a recurring event: the ticket a customer buys for that date.
 * Every date is stored twice, event-local and UTC.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Tables
 */
final class Tickets extends Table {
	/**
	 * The schema version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_tickets';

	/**
	 * The organizational group this table belongs to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $group = 'tec_tickets_recurring_tickets';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-tickets-recurring-tickets';

	/**
	 * Returns the schema history for this table.
	 *
	 * @since TBD
	 *
	 * @return array<string, callable> The schema builders, keyed by schema version.
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => static function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = ( new String_Column( 'type' ) )->set_length( 50 );
				// Not a Referenced_ID: the unique template and date key already serves lookups by template.
				$columns[] = ( new Integer_Column( 'parent_id' ) )->set_signed( false )->set_nullable( true );
				$columns[] = new Referenced_ID( 'post_id' );
				$columns[] = ( new Referenced_ID( 'occurrence_id' ) )->set_nullable( true );
				// Every date is a DATETIME: the column type defaults to TIMESTAMP, which shifts with the session time zone.
				$columns[] = self::date_column( 'occurrence_start' );
				$columns[] = self::date_column( 'occurrence_start_utc' );
				$columns[] = ( new String_Column( 'name' ) )->set_length( 255 );
				$columns[] = ( new Text_Column( 'description' ) )->set_nullable( true );
				// MySQL stores BOOLEAN as tinyint(1).
				$columns[] = ( new Boolean_Column( 'show_description' ) )->set_default( true );
				$columns[] = ( new String_Column( 'sku' ) )->set_length( 255 )->set_nullable( true );
				// Minor units of the Tickets Commerce currency. A sale price stays on the template.
				$columns[] = ( new Integer_Column( 'price' ) )->set_signed( false );
				// -1 is unlimited, as for ticket posts.
				$columns[] = ( new Integer_Column( 'capacity' ) )->set_default( -1 );
				// NULL when unlimited.
				$columns[] = ( new Integer_Column( 'stock' ) )->set_signed( false )->set_nullable( true );
				// The schema emits no DEFAULT for a falsy value: the repository writes 0 when a row leaves this out.
				$columns[] = ( new Integer_Column( 'sales' ) )->set_signed( false );
				$columns[] = ( new String_Column( 'stock_mode' ) )->set_length( 20 )->set_default( 'own' );
				$columns[] = self::date_column( 'start_date' );
				$columns[] = self::date_column( 'end_date' );
				$columns[] = self::date_column( 'start_date_utc' );
				$columns[] = self::date_column( 'end_date_utc' );
				// As for sales, the repository writes 0 when a row leaves this out.
				$columns[] = ( new Integer_Column( 'menu_order' ) )->set_type( Column_Types::INT )->set_length( 11 );
				$columns[] = ( new String_Column( 'status' ) )->set_length( 20 )->set_default( 'publish' );
				$columns[] = self::json_column( 'relative_date_settings' );
				$columns[] = self::json_column( 'iac_settings' );
				// The columns changed on this date only, e.g. ["price","capacity"]. The sync never overwrites them.
				$columns[] = self::json_column( 'overrides' );

				/*
				 * Before MySQL 5.6.5 only one TIMESTAMP column may use CURRENT_TIMESTAMP, and a DATETIME cannot.
				 * updated_at takes it, so it follows every write, raw stock updates included; the repository writes created_at.
				 */
				$columns[] = ( new Datetime_Column( 'created_at' ) )->set_type( Column_Types::DATETIME );
				$columns[] = new Updated_At( 'updated_at' );

				$indexes   = new Index_Collection();
				$indexes[] = ( new Unique_Key( 'template_occurrence' ) )->set_columns( 'parent_id', 'occurrence_id' );

				return new Table_Schema( $table_name, $columns, $indexes );
			},
		];
	}

	/**
	 * Builds a model from a row, so the table's lookups return models.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $row The row, with values cast to their PHP types.
	 *
	 * @return Ticket The model.
	 */
	public static function transform_from_array( array $row ): Ticket {
		return Ticket::fromData( $row );
	}

	/**
	 * Builds a nullable DATETIME column.
	 *
	 * @since TBD
	 *
	 * @param string $name The column name.
	 *
	 * @return Datetime_Column The column.
	 */
	private static function date_column( string $name ): Datetime_Column {
		return ( new Datetime_Column( $name ) )->set_type( Column_Types::DATETIME )->set_nullable( true );
	}

	/**
	 * Builds a nullable MEDIUMTEXT column holding JSON.
	 *
	 * @since TBD
	 *
	 * @param string $name The column name.
	 *
	 * @return Text_Column The column.
	 */
	private static function json_column( string $name ): Text_Column {
		return ( new Text_Column( $name ) )->set_type( Column_Types::MEDIUMTEXT )->set_php_type( PHP_Types::JSON )->set_nullable( true );
	}
}
