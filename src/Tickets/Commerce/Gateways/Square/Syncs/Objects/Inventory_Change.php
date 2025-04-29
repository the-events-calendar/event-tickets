<?php
/**
 * Inventory Change object for Square synchronization.
 *
 * This class represents an inventory change operation to be sent to Square.
 * It handles different types of inventory changes like physical counts and adjustments.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use InvalidArgumentException;
use JsonSerializable;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Remote_Objects;

/**
 * Class Inventory_Change
 *
 * Represents an inventory change operation for Square's API.
 * Handles the formatting and validation of inventory adjustments, physical counts,
 * and transfers according to Square's API requirements.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class Inventory_Change implements JsonSerializable {
	/**
	 * Valid types of inventory changes for Square.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected const VALID_TYPES = [
		'PHYSICAL_COUNT' => 1,
		'ADJUSTMENT'     => 1,
		'TRANSFER'       => 1,
	];

	/**
	 * Valid inventory states for Square.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected const VALID_STATES = [
		'CUSTOM'               => 1,
		'IN_STOCK'             => 1,
		'SOLD'                 => 1,
		'RETURNED_BY_CUSTOMER' => 1,
		'RESERVED_FOR_SALE'    => 1,
		'SOLD_ONLINE'          => 1,
		'ORDERED_FROM_VENDOR'  => 1,
		'RECEIVED_FROM_VENDOR' => 1,
		'NONE'                 => 1,
		'WASTE'                => 1,
		'UNLINKED_RETURN'      => 1,
	];

	/**
	 * The data structure for the inventory change.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * The ticket item associated with this inventory change.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Item
	 */
	protected Ticket_Item $ticket_item;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param string      $type        The type of inventory change.
	 * @param Ticket_Item $ticket_item The ticket item to change inventory for.
	 * @param array       $data        Additional data for the inventory change.
	 *
	 * @throws InvalidArgumentException If the type is invalid.
	 */
	public function __construct( string $type, Ticket_Item $ticket_item, array $data ) {
		if ( ! isset( self::VALID_TYPES[ strtoupper( $type ) ] ) ) {
			throw new InvalidArgumentException( 'Invalid type' );
		}

		$this->ticket_item = $ticket_item;

		if ( ! has_action( 'tec_tickets_commerce_square_sync_inventory_changed_' . $this->ticket_item->get_id() ) ) {
			add_action( 'tec_tickets_commerce_square_sync_inventory_changed_' . $this->ticket_item->get_id(), [ $this, 'process_inventory_changed' ], 10, 2 );
		}

		$this->data['type'] = $type;

		$this->data[ strtolower( $type ) ] = [
			'catalog_object_id' => $this->ticket_item->get_id(),
			'location_id'       => $data['location_id'],
			'occurred_at'       => gmdate( Remote_Objects::SQUARE_DATE_TIME_FORMAT, $data['occurred_at'] ?? time() ),
		];

		$method = 'set_' . strtolower( $type ) . '_data';

		if ( is_callable( [ $this, $method ] ) ) {
			$this->$method( $data );
		}
	}

	/**
	 * Process inventory changes from Square.
	 *
	 * @since TBD
	 *
	 * @param string $state    The inventory state reported by Square.
	 * @param string $quantity The quantity reported by Square.
	 *
	 * @return void
	 */
	public function process_inventory_changed( string $state, string $quantity ): void {
		$ticket = $this->ticket_item->get_ticket();

		$count = $ticket->available();

		if ( $count === -1 ) {
			if ( $state !== 'IN_STOCK' ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync - Ticket ' . $this->ticket_item->get_id() . ' has unlimited stock but Square says it is not in stock.' );
			}
			return;
		}

		if ( $count > 0 ) {
			if ( $state !== 'IN_STOCK' ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync - Ticket ' . $this->ticket_item->get_id() . ' has ' . $count . ' in stock but Square says it is not in stock.' );
			}

			if ( $quantity !== (string) $count ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync - Ticket ' . $this->ticket_item->get_id() . ' has ' . $count . ' in stock but Square says it is ' . $quantity . '.' );
			}

			return;
		}

		if ( $count === 0 ) {
			if ( $state !== 'SOLD' ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync - Ticket ' . $this->ticket_item->get_id() . ' has no stock but Square says it is not sold.' );
			}
		}
	}

	/**
	 * Set the data for an adjustment type inventory change.
	 *
	 * @since TBD
	 *
	 * @param array $data Additional data for the adjustment.
	 *
	 * @return void
	 * @throws NoChangeNeededException If no change is needed.
	 * @throws InvalidArgumentException If the data is invalid.
	 */
	public function set_adjustment_data( array $data = [] ): void {
		$cache     = tribe_cache();
		$cache_key = 'square_sync_object_state_' . $this->ticket_item->get_id() . '_' . $data['location_id'];

		$cached_state = $cache[ $cache_key ];

		$previous_quantity = (int) ( $cached_state['quantity'] ?? 0 );

		$quantity = $this->ticket_item->get_ticket()->available();

		if ( $quantity === -1 ) {
			if ( $previous_quantity > 900000000 ) {
				throw new NoChangeNeededException( 'Ticket ' . $this->ticket_item->get_id() . ' has unlimited stock and Square stock is still enough.' );
			}
			$quantity = 1000000000;
		}

		if ( $quantity === $previous_quantity ) {
			throw new NoChangeNeededException( 'Ticket ' . $this->ticket_item->get_id() . ' is already at the desired stock level in Square.' );
		}

		$default_to_state = $quantity - $previous_quantity > 0 ? 'IN_STOCK' : 'SOLD';

		if ( ! $quantity ) {
			throw new InvalidArgumentException( 'Quantity is required for an adjustment. We need to specify the amount of tickets that where adjusted!' );
		}

		$to_state   = $data['to_state'] ?? $default_to_state;
		$from_state = $data['from_state'] ?? ( 'IN_STOCK' === $to_state ? 'NONE' : 'IN_STOCK' );

		if ( ! isset( self::VALID_STATES[ strtoupper( $from_state ) ] ) ) {
			throw new InvalidArgumentException( 'Invalid from state' );
		}

		if ( ! isset( self::VALID_STATES[ strtoupper( $to_state ) ] ) ) {
			throw new InvalidArgumentException( 'Invalid to state' );
		}

		$this->data['adjustment'] = array_merge(
			$this->data['adjustment'],
			[
				'from_state' => $from_state,
				'to_state'   => $to_state,
				'quantity'   => (string) abs( $quantity - $previous_quantity ),
			]
		);
	}

	/**
	 * Set the data for a physical count type inventory change.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function set_physical_count_data(): void {
		$this->data['physical_count']['quantity'] = (string) $this->ticket_item->get_ticket()->available();
	}

	/**
	 * Serialize the object to JSON.
	 *
	 * @since TBD
	 *
	 * @return array The data array for JSON serialization.
	 */
	public function jsonSerialize(): array {
		return $this->data;
	}
}
