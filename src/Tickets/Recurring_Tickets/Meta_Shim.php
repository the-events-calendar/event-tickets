<?php
/**
 * Answers post meta reads on table ticket IDs from the row, or from its template.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Meta_Shim.
 *
 * Event Tickets and its add-ons read ticket data with `get_post_meta()` in about 140 places. For a table ticket ID
 * the keys a row stores are answered from the row, in the shapes Tickets Commerce stores them for a post, and any
 * other key from the template ticket post, so ET+ attendee fields work without being copied. Nothing is ever read
 * from `wp_postmeta` for a table ticket ID.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Meta_Shim {
	/**
	 * The rows.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The hydrator, for the rules a row's event and sale window follow.
	 *
	 * @since TBD
	 *
	 * @var Hydrator
	 */
	private Hydrator $hydrator;

	/**
	 * Meta_Shim constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows     $rows     The rows.
	 * @param Hydrator $hydrator The hydrator.
	 */
	public function __construct( Rows $rows, Hydrator $hydrator ) {
		$this->rows     = $rows;
		$this->hydrator = $hydrator;
	}

	/**
	 * Answers a post meta read on a table ticket ID.
	 *
	 * @since TBD
	 *
	 * @param mixed  $value     The value another filter answered, or null.
	 * @param int    $object_id The post ID.
	 * @param string $meta_key  The meta key, empty for all of them.
	 * @param bool   $single    Whether one value was asked for.
	 *
	 * @return mixed The answer: an array of values, `''` for no single value, or `$value` for any other ID.
	 */
	public function read( $value, $object_id, $meta_key, $single ) {
		if ( null !== $value || ! Ticket_ID::is_table_ticket( $object_id ) ) {
			return $value;
		}

		// A table ticket ID has no post meta. Saying so in the cache stops ECP looking the ID up as a date.
		wp_cache_add( (int) $object_id, [], 'post_meta' );

		$row = $this->rows->find( Ticket_ID::to_row_id( (int) $object_id ) );

		if ( ! $row ) {
			return $single ? '' : [];
		}

		$own = $this->row_meta( $row );

		if ( '' === (string) $meta_key ) {
			$all = $row->parent_id ? (array) get_post_meta( (int) $row->parent_id ) : [];

			foreach ( $own as $key => $own_value ) {
				unset( $all[ $key ] );

				if ( null !== $own_value ) {
					$all[ $key ] = [ $own_value ];
				}
			}

			return $all;
		}

		if ( array_key_exists( $meta_key, $own ) ) {
			$values = null === $own[ $meta_key ] ? [] : [ $own[ $meta_key ] ];
		} else {
			$values = $row->parent_id ? (array) get_post_meta( (int) $row->parent_id, $meta_key, false ) : [];
		}

		if ( ! $values ) {
			// Never null: that would fall through to a wp_postmeta query for the table ticket ID.
			return $single ? '' : [];
		}

		// WordPress hands back the first value of the array when one was asked for.
		return $single ? [ reset( $values ) ] : $values;
	}

	/**
	 * Returns the keys a row answers, null for a key it answers as absent.
	 *
	 * @since TBD
	 *
	 * @param Ticket $row The row.
	 *
	 * @return array<string,string|null> The values, keyed by meta key.
	 */
	private function row_meta( Ticket $row ): array {
		$unlimited = -1 === (int) $row->capacity;
		$window    = $this->hydrator->sale_window( $row );
		$handler   = tribe( 'tickets.handler' );

		$meta = [
			Commerce_Ticket::$price_meta_key               => Price::to_decimal( (int) $row->price ),
			$handler->key_capacity                         => (string) (int) $row->capacity,
			Commerce_Ticket::$should_manage_stock_meta_key => $unlimited ? 'no' : 'yes',
			// Tickets Commerce deletes stock and stock mode for an unlimited ticket.
			Commerce_Ticket::$stock_meta_key               => $unlimited || null === $row->stock ? null : (string) (int) $row->stock,
			Global_Stock::TICKET_STOCK_MODE                => $unlimited ? null : (string) $row->stock_mode,
			Commerce_Ticket::$sales_meta_key               => (string) (int) $row->sales,
			Commerce_Ticket::$type_meta_key                => (string) $row->type,
			Commerce_Ticket::$sku_meta_key                 => '' === (string) $row->sku ? null : (string) $row->sku,
			Commerce_Ticket::$event_relation_meta_key      => (string) $this->hydrator->event_id( $row ),
			Commerce_Ticket::START_DATE_META_KEY           => $window['start_date'] ?: null,
			Commerce_Ticket::START_TIME_META_KEY           => $window['start_time'] ?: null,
			Commerce_Ticket::END_DATE_META_KEY             => $window['end_date'] ?: null,
			Commerce_Ticket::END_TIME_META_KEY             => $window['end_time'] ?: null,
			$handler->key_show_description                 => $row->show_description ? 'yes' : 'no',
		];

		// Without a setting of its own the row falls through to the template's.
		$iac = is_array( $row->iac_settings ) ? ( $row->iac_settings['iac'] ?? null ) : null;

		if ( null !== $iac ) {
			$meta['_tribe_tickets_ar_iac'] = (string) $iac;
		}

		return $meta;
	}
}
