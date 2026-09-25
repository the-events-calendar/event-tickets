<?php
/**
 * Picks the class that converts each type of order item.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

use TEC\Common\lucatume\DI52\Container;

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
		'ticket'   => Ticket_Line_Item::class,
		'fee'      => Fee_Line_Item::class,
		'coupon'   => Coupon_Line_Item::class,
		'discount' => Discount_Line_Item::class,
	];

	/**
	 * The container.
	 *
	 * @since TBD
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * The line item types built so far, keyed by class.
	 *
	 * @since TBD
	 *
	 * @var array<class-string<Line_Item_Type>,Line_Item_Type>
	 */
	private array $instances = [];

	/**
	 * Line_Item_Types constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Returns the line item type that converts items of a type.
	 *
	 * @since TBD
	 *
	 * @param string $type The item type, as found in the item's or the row's `type`.
	 *
	 * @return Line_Item_Type The type's class, or the generic one for a type none is registered for.
	 */
	public function get( string $type ): Line_Item_Type {
		/**
		 * Filters the class that converts each type of order item to an Order Items table row and back.
		 *
		 * The class is built by the container. An entry that is not the name of a class implementing
		 * Line_Item_Type is ignored.
		 *
		 * @since TBD
		 *
		 * @param array<string,class-string<Line_Item_Type>> $types The class of each item type, keyed by the item's `type`.
		 */
		$types = apply_filters( 'tec_tickets_commerce_order_items_line_item_types', self::TYPES );
		$class = is_array( $types ) ? ( $types[ $type ] ?? Generic_Line_Item::class ) : null;

		if ( ! is_string( $class ) || ! is_subclass_of( $class, Line_Item_Type::class ) ) {
			$class = self::TYPES[ $type ] ?? Generic_Line_Item::class;
		}

		return $this->instances[ $class ] ??= $this->container->get( $class );
	}

	/**
	 * Returns the line item type that converts an item.
	 *
	 * @since TBD
	 *
	 * @param array $item The order item.
	 *
	 * @return Line_Item_Type The class for the item's `type`, or the generic one.
	 */
	public function get_for_item( array $item ): Line_Item_Type {
		return $this->get( is_string( $item['type'] ?? null ) ? $item['type'] : '' );
	}
}
