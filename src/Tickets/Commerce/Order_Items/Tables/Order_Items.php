<?php
/**
 * The Order Items custom table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Tables
 */

namespace TEC\Tickets\Commerce\Order_Items\Tables;

use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Collections\Index_Collection;
use TEC\Common\StellarWP\Schema\Columns\Column_Types;
use TEC\Common\StellarWP\Schema\Columns\Created_At;
use TEC\Common\StellarWP\Schema\Columns\Datetime_Column;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Columns\PHP_Types;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Text_Column;
use TEC\Common\StellarWP\Schema\Indexes\Unique_Key;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;

/**
 * Class Order_Items.
 *
 * One row per line of an order (ticket, fee, coupon or discount) as it was at purchase.
 * Money columns hold signed minor units: coupon and discount lines are negative.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Tables
 */
class Order_Items extends Table {
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
	protected static $base_table_name = 'tec_tc_order_items';

	/**
	 * The organizational group this table belongs to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $group = 'tec_tickets_commerce_order_items';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-tickets-commerce-order-items';

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
				$columns[] = new Referenced_ID( 'order_id' );
				$columns[] = ( new String_Column( 'type' ) )->set_length( 50 );
				$columns[] = ( new String_Column( 'item_key' ) )->set_length( 191 )->set_nullable( true );
				// The line's 0-based place in the order's item list; row IDs do not keep it once a line is added to an existing order.
				$columns[] = ( new Integer_Column( 'position' ) )->set_type( Column_Types::INT )->set_length( 11 )->set_signed( false );

				/*
				 * The line identity columns are NOT NULL and writers store 0 for "none": MySQL treats NULLs
				 * as distinct in a unique key, so a nullable column would let the same ticket line be stored twice.
				 */
				$columns[] = new Referenced_ID( 'ticket_id' );
				$columns[] = new Referenced_ID( 'modifier_id' );
				$columns[] = new Referenced_ID( 'purchase_rule_id' );
				$columns[] = ( new Referenced_ID( 'event_id' ) )->set_nullable( true );
				$columns[] = ( new Referenced_ID( 'post_id' ) )->set_nullable( true );
				$columns[] = ( new Referenced_ID( 'occurrence_id' ) )->set_nullable( true );
				$columns[] = ( new String_Column( 'event_title' ) )->set_length( 255 )->set_nullable( true );
				// The column type defaults to timestamp, which would shift the stored value with the session time zone.
				$columns[] = ( new Datetime_Column( 'event_start_date' ) )->set_type( Column_Types::DATETIME )->set_nullable( true );
				$columns[] = ( new Datetime_Column( 'event_start_date_utc' ) )->set_type( Column_Types::DATETIME )->set_nullable( true );
				$columns[] = ( new String_Column( 'name' ) )->set_length( 255 );
				$columns[] = ( new String_Column( 'currency' ) )->set_length( 3 );
				$columns[] = ( new String_Column( 'sku' ) )->set_length( 255 )->set_nullable( true );
				$columns[] = ( new String_Column( 'ticket_type' ) )->set_length( 50 )->set_nullable( true );
				$columns[] = ( new Integer_Column( 'quantity' ) )->set_type( Column_Types::INT )->set_length( 11 );
				$columns[] = new Integer_Column( 'price' );
				$columns[] = ( new Integer_Column( 'regular_price' ) )->set_nullable( true );
				$columns[] = new Integer_Column( 'sub_total' );
				$columns[] = ( new Integer_Column( 'regular_sub_total' ) )->set_nullable( true );
				$columns[] = ( new Text_Column( 'extra' ) )->set_type( Column_Types::MEDIUMTEXT )->set_php_type( PHP_Types::JSON )->set_nullable( true );
				$columns[] = new Created_At( 'created_at' );

				$indexes   = new Index_Collection();
				$indexes[] = ( new Unique_Key( 'order_line_identity' ) )->set_columns( 'order_id', 'ticket_id', 'modifier_id', 'purchase_rule_id' );

				return new Table_Schema( $table_name, $columns, $indexes );
			},
		];
	}
}
