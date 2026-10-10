<?php
/**
 * Converts ticket order items to Order Items table rows and back.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

use TEC\Tickets\Commerce\Ticket;

/**
 * Class Ticket_Line_Item.
 *
 * The row keeps the ticket's name, SKU and type as they were at purchase, so the line still reads right once
 * the ticket is edited or deleted.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
class Ticket_Line_Item extends Abstract_Line_Item_Type {
	/**
	 * Item keys stored in a column, and how their value is stored.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const FIELDS = parent::FIELDS + [
		'regular_price'     => 'money',
		'regular_sub_total' => 'money',
	];

	/**
	 * Returns the ticket's name, SKU and type at purchase time.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string|null> $columns The item's column values, keyed by column name.
	 *
	 * @return array{name: string, sku: ?string, ticket_type: ?string} The line's details.
	 */
	protected static function get_details( array $columns ): array {
		$ticket_id = $columns['ticket_id'] ?? 0;
		$ticket    = $ticket_id ? tribe( Ticket::class )->get_ticket( $ticket_id ) : null;

		return [
			'name'        => $columns['name'] ?? ( $ticket->name ?? '' ),
			'sku'         => $ticket ? ( $ticket->sku ?: null ) : null,
			'ticket_type' => $ticket ? $ticket->type() : null,
		];
	}
}
