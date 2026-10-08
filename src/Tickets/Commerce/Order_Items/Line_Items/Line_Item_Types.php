<?php
/**
 * Picks the class that converts each type of order item.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

use InvalidArgumentException;
use TEC\Tickets\RSVP\V2\Constants;

/**
 * Class Line_Item_Types.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
class Line_Item_Types {
	/**
	 * The line item type class of each item type.
	 *
	 * @since TBD
	 *
	 * @var array<string,class-string<Line_Item_Type>>
	 */
	private const TYPES = [
		'ticket'                => Ticket_Line_Item::class,
		'fee'                   => Fee_Line_Item::class,
		'coupon'                => Coupon_Line_Item::class,
		'discount'              => Discount_Line_Item::class,
		Constants::TC_RSVP_TYPE => Ticket_Line_Item::class,
	];

	/**
	 * Returns the line item type class that converts items of a type.
	 *
	 * @since TBD
	 *
	 * @param string $type The item type, as found in the item's or the row's `type`.
	 *
	 * @return class-string<Line_Item_Type> The type's class.
	 *
	 * @throws InvalidArgumentException When no line item type class is registered for the type.
	 */
	public function get( string $type ): string {
		/**
		 * Filters the class that converts each type of order item to an Order Items table row and back.
		 *
		 * An order holding an item of a type with no class, or with a class that does not implement
		 * Line_Item_Type, is not stored in the table and keeps its items in the order meta only.
		 *
		 * @since TBD
		 *
		 * @param array<string,class-string<Line_Item_Type>> $types The class of each item type, keyed by the item's `type`.
		 */
		$types = apply_filters( 'tec_tickets_commerce_order_items_line_item_types', self::TYPES );
		// An item without a type is a ticket, as in Order::create_from_cart(), and its row stores the type empty.
		$class = is_array( $types ) ? ( $types[ '' === $type ? 'ticket' : $type ] ?? null ) : null;

		if ( ! is_string( $class ) || ! is_subclass_of( $class, Line_Item_Type::class ) ) {
			throw new InvalidArgumentException( sprintf( 'No line item type class is registered for the "%s" item type.', $type ) );
		}

		return $class;
	}

	/**
	 * Returns the line item type class that converts an item.
	 *
	 * @since TBD
	 *
	 * @param array $item The order item.
	 *
	 * @return class-string<Line_Item_Type> The class for the item's `type`.
	 *
	 * @throws InvalidArgumentException When the item's type is not a string, or no line item type class is registered for it.
	 */
	public function get_for_item( array $item ): string {
		$type = $item['type'] ?? '';

		if ( ! is_string( $type ) ) {
			throw new InvalidArgumentException( 'The order item type is not a string.' );
		}

		return $this->get( $type );
	}
}
