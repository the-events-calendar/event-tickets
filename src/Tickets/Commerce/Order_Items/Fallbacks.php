<?php
/**
 * Names the lines of an order whose ticket no longer exists.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items;
use Throwable;
use WP_Post;

/**
 * Class Fallbacks.
 *
 * Used by the admin order screens in place of the ticket's live name once the ticket is gone.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
class Fallbacks {
	/**
	 * The Order Items repository.
	 *
	 * @since TBD
	 *
	 * @var Order_Items
	 */
	private Order_Items $repository;

	/**
	 * The values stored at purchase, by order ID, then item key.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<string,array{name: string, ticket_type: ?string}>>
	 */
	private array $rows = [];

	/**
	 * Fallbacks constructor.
	 *
	 * @since TBD
	 *
	 * @param Order_Items $repository The Order Items repository.
	 */
	public function __construct( Order_Items $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Returns what to show for an order line whose ticket no longer exists.
	 *
	 * The name comes from, in order: the order's stored row, the item's `snapshot` entry, then "Ticket #ID"; it ends
	 * with a text marker saying the ticket no longer exists. The type comes from the same sources and is `default`
	 * when none is stored. Values are not escaped.
	 *
	 * @since TBD
	 *
	 * @param WP_Post    $order The order.
	 * @param int|string $key   The item's key in the order's item list.
	 * @param array      $item  The order item.
	 *
	 * @return array{name: string, type: string} The name, followed by the marker, and the ticket type.
	 */
	public function get_missing_ticket( WP_Post $order, $key, array $item ): array {
		$stored   = $this->get_stored_rows( $order->ID )[ (string) $key ] ?? [];
		$snapshot = is_array( $item['snapshot'] ?? null ) ? $item['snapshot'] : [];
		$name     = $this->first_string( $stored['name'] ?? null, $snapshot['name'] ?? null );
		$type     = $this->first_string( $stored['ticket_type'] ?? null, $snapshot['ticket_type'] ?? null );

		if ( '' === $name ) {
			$name = sprintf(
				// translators: 1) is the singular ticket label, 2) is the ID of a ticket that no longer exists.
				_x( '%1$s #%2$d', 'The name of an order line whose ticket no longer exists.', 'event-tickets' ),
				tribe_get_ticket_label_singular( 'order_items_missing_ticket' ),
				absint( $item['ticket_id'] ?? 0 )
			);
		}

		return [
			'name' => sprintf(
				// translators: %s is the name of a ticket that no longer exists.
				_x( '%s (no longer exists)', 'An order line whose ticket has been deleted.', 'event-tickets' ),
				$name
			),
			'type' => '' === $type ? 'default' : $type,
		];
	}

	/**
	 * Returns the first non-empty string.
	 *
	 * @since TBD
	 *
	 * @param mixed ...$values The candidate values.
	 *
	 * @return string The first non-empty string, or an empty string.
	 */
	private function first_string( ...$values ): string {
		foreach ( $values as $value ) {
			if ( is_string( $value ) && '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Returns the name and ticket type an order stored at purchase for each line, reading its rows once per request.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return array<string,array{name: string, ticket_type: ?string}> The stored values, by item key. Empty for orders
	 *                                                                 not stored in the table.
	 */
	private function get_stored_rows( int $order_id ): array {
		if ( isset( $this->rows[ $order_id ] ) ) {
			return $this->rows[ $order_id ];
		}

		$this->rows[ $order_id ] = [];

		// Without the reader the table may not exist, and the order shows the items from its meta.
		if ( ! Reader::is_registered() || Writer::VERSION !== absint( get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) ) ) {
			return [];
		}

		try {
			foreach ( $this->repository->get_by_order( $order_id ) as $row ) {
				$this->rows[ $order_id ][ $row->item_key ] = [
					'name'        => $row->name,
					'ticket_type' => $row->ticket_type,
				];
			}
		} catch ( Throwable $e ) {
			// The line still shows, as "Ticket #ID".
			$this->rows[ $order_id ] = [];
		}

		return $this->rows[ $order_id ];
	}
}
