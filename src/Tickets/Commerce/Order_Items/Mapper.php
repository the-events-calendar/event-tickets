<?php
/**
 * Converts order items to order item table rows and back.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Currency;

/**
 * Class Mapper.
 *
 * The item read back from a row is identical (`===`) to the item written, key order and value types
 * included. Everything the columns cannot hold exactly lives in the row's `extra` JSON.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
class Mapper {
	/**
	 * Item keys stored in a column of the same name, and how their value is stored.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	private const FIELDS = [
		'type'              => 'string',
		'ticket_id'         => 'int',
		'event_id'          => 'int',
		'quantity'          => 'int',
		'price'             => 'money',
		'regular_price'     => 'money',
		'sub_total'         => 'money',
		'regular_sub_total' => 'money',
	];

	/**
	 * The Tickets Commerce ticket handler.
	 *
	 * @since TBD
	 *
	 * @var Ticket
	 */
	private Ticket $tickets;

	/**
	 * Mapper constructor.
	 *
	 * @since TBD
	 *
	 * @param Ticket $tickets The Tickets Commerce ticket handler.
	 */
	public function __construct( Ticket $tickets ) {
		$this->tickets = $tickets;
	}

	/**
	 * Converts an order item to a table row.
	 *
	 * The row keys are the table's column names; `id` and `created_at` are left to the writer.
	 *
	 * @since TBD
	 *
	 * @param int|string $key      The item's key in the order's item list.
	 * @param array      $item     The order item.
	 * @param int        $order_id The order ID.
	 * @param string     $currency The order's currency code.
	 *
	 * @return array<string,int|string|null> The row.
	 */
	public function to_row( $key, array $item, int $order_id, string $currency ): array {
		$precision = (int) Currency::get_currency_precision( $currency );
		$columns   = [];
		$extra     = [
			'precision' => $precision,
			'keys'      => array_keys( $item ),
			'values'    => [],
			'raw'       => [],
		];

		foreach ( $item as $name => $value ) {
			if ( ! isset( self::FIELDS[ $name ] ) ) {
				$extra['values'][ $name ] = $value;
				continue;
			}

			$kind             = self::FIELDS[ $name ];
			$columns[ $name ] = $this->to_column( $kind, $value, $precision );

			// Values the column cannot hold exactly (a '0' string, an unrounded float) keep their original.
			if ( $this->from_column( $kind, $columns[ $name ], $precision ) !== $value ) {
				$extra['raw'][ $name ] = $value;
			}
		}

		$ticket_id = $columns['ticket_id'] ?? 0;
		$event_id  = $columns['event_id'] ?? null;
		$ticket    = $ticket_id ? $this->tickets->get_ticket( $ticket_id ) : null;

		return [
			'order_id'             => $order_id,
			'type'                 => $columns['type'] ?? '',
			'item_key'             => (string) $key,
			'ticket_id'            => $ticket_id,
			'modifier_id'          => 0,
			'purchase_rule_id'     => 0,
			'event_id'             => $event_id,
			'post_id'              => $event_id,
			'occurrence_id'        => null,
			'event_title'          => $event_id ? ( get_post_field( 'post_title', $event_id ) ?: null ) : null,
			'event_start_date'     => $event_id ? ( get_post_meta( $event_id, '_EventStartDate', true ) ?: null ) : null,
			'event_start_date_utc' => $event_id ? ( get_post_meta( $event_id, '_EventStartDateUTC', true ) ?: null ) : null,
			'name'                 => $ticket->name ?? '',
			'currency'             => $currency,
			'sku'                  => $ticket ? ( $ticket->sku ?: null ) : null,
			'ticket_type'          => $ticket ? $ticket->type() : null,
			'quantity'             => $columns['quantity'] ?? 0,
			'price'                => $columns['price'] ?? 0,
			'regular_price'        => $columns['regular_price'] ?? null,
			'sub_total'            => $columns['sub_total'] ?? 0,
			'regular_sub_total'    => $columns['regular_sub_total'] ?? null,
			// Encoded here because the schema library's encoder would turn 10.0 into 10.
			'extra'                => wp_json_encode( $extra, JSON_PRESERVE_ZERO_FRACTION ),
		];
	}

	/**
	 * Converts a table row back to the order item it was written from.
	 *
	 * The key is returned as stored; assigning it as an array key turns '1' back into 1 and leaves '01' a string,
	 * which is the type it had.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string|array|null> $row The row, as built by to_row() or read from the database.
	 *
	 * @return array{0: string, 1: array} The item's key in the order's item list, and the item.
	 */
	public function to_item( array $row ): array {
		$extra = is_string( $row['extra'] ) ? json_decode( $row['extra'], true ) : $row['extra'];
		$item  = [];

		foreach ( $extra['keys'] as $name ) {
			if ( array_key_exists( $name, $extra['raw'] ) ) {
				$item[ $name ] = $extra['raw'][ $name ];
			} elseif ( isset( self::FIELDS[ $name ] ) ) {
				// The precision recorded at write time, so a later settings change does not rescale the order.
				$item[ $name ] = $this->from_column( self::FIELDS[ $name ], $row[ $name ], $extra['precision'] );
			} else {
				$item[ $name ] = $extra['values'][ $name ];
			}
		}

		return [ $row['item_key'], $item ];
	}

	/**
	 * Converts an item value to its column value.
	 *
	 * @since TBD
	 *
	 * @param string $kind      One of `int`, `money` or `string`.
	 * @param mixed  $value     The item value.
	 * @param int    $precision The currency precision.
	 *
	 * @return int|string|null The column value.
	 */
	private function to_column( string $kind, $value, int $precision ) {
		if ( null === $value ) {
			return null;
		}

		if ( 'string' === $kind ) {
			return is_scalar( $value ) ? (string) $value : '';
		}

		if ( ! is_numeric( $value ) ) {
			return 0;
		}

		return 'money' === $kind ? (int) round( $value * ( 10 ** $precision ) ) : (int) $value;
	}

	/**
	 * Converts a column value to its natural item value: a float for money, an int or a string otherwise.
	 *
	 * @since TBD
	 *
	 * @param string $kind      One of `int`, `money` or `string`.
	 * @param mixed  $value     The column value, as stored or as read from the database.
	 * @param int    $precision The currency precision.
	 *
	 * @return float|int|string|null The item value.
	 */
	private function from_column( string $kind, $value, int $precision ) {
		if ( null === $value ) {
			return null;
		}

		if ( 'string' === $kind ) {
			return (string) $value;
		}

		return 'money' === $kind ? (float) ( (int) $value / ( 10 ** $precision ) ) : (int) $value;
	}
}
