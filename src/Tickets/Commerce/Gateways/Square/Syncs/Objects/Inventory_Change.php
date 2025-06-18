<?php
/**
 * Inventory Change object for Square synchronization.
 *
 * This class represents an inventory change operation to be sent to Square.
 * It handles different types of inventory changes like physical counts and adjustments.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use InvalidArgumentException;
use JsonSerializable;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Remote_Objects;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Inventory_Change
 *
 * Represents an inventory change operation for Square's API.
 * Handles the formatting and validation of inventory adjustments, physical counts,
 * and transfers according to Square's API requirements.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class Inventory_Change implements JsonSerializable {
	/**
	 * Valid types of inventory changes for Square.
	 *
	 * @since 5.24.0
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
	 * @since 5.24.0
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
	 * @since 5.24.0
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * The ticket item associated with this inventory change.
	 *
	 * @since 5.24.0
	 *
	 * @var Ticket_Item
	 */
	protected Ticket_Item $ticket_item;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
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
	 * @since 5.24.0
	 *
	 * @param string $state    The inventory state reported by Square.
	 * @param string $quantity The quantity reported by Square.
	 *
	 * @return void
	 */
	public function process_inventory_changed( string $state, string $quantity ): void {
		$ticket = $this->ticket_item->get_ticket();

		if ( ! $ticket instanceof Ticket_Object ) {
			return;
		}

		try {
			if ( Sync_Controller::is_ticket_in_sync_with_square_data( $ticket, $quantity, $state ) ) {
				return;
			}
		} catch ( NotSyncableItemException $e ) {
			do_action( 'tribe_log', 'warning', 'Square Inventory Sync - Ticket ' . $this->ticket_item->get_id() . ' is not syncable.' );
			return;
		}

		do_action( 'tribe_log', 'warning', 'Square Inventory Sync - Ticket ' . $this->ticket_item->get_id() . ' is out of sync.' );
	}

	/**
	 * Set the data for an adjustment type inventory change.
	 *
	 * @since 5.24.0
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

		$cached_state = $cache->get( $cache_key );

		$previous_quantity = (int) ( $cached_state['quantity'] ?? 0 );
		$previous_state    = $cached_state['state'] ?? 'IN_STOCK';

		$ticket = $this->ticket_item->get_ticket();

		if ( ! $ticket instanceof Ticket_Object ) {
			// translators: %s is the ticket ID.
			throw new NoChangeNeededException( sprintf( __( 'Ticket %s is not a valid ticket object.', 'event-tickets' ), $this->ticket_item->get_id() ) );
		}

		try {
			if ( Sync_Controller::is_ticket_in_sync_with_square_data( $ticket, $previous_quantity, $previous_state ) ) {
				// translators: %s is the ticket ID.
				throw new NoChangeNeededException( sprintf( __( 'Ticket %s is already in sync with Square.', 'event-tickets' ), $this->ticket_item->get_id() ) );
			}
		} catch ( NotSyncableItemException $e ) {
			// translators: %s is the ticket ID.
			throw new NoChangeNeededException( sprintf( __( 'Ticket %s is not sync-able.', 'event-tickets' ), $this->ticket_item->get_id() ) );
		}

		$quantity = $this->ticket_item->get_ticket()->available();

		if ( -1 === $quantity ) {
			$quantity = 900000000;
		}

		if ( ! $quantity && 0 !== $quantity ) {
			throw new InvalidArgumentException( 'Quantity is required for an adjustment. We need to specify the amount of tickets that where adjusted!' );
		}

		$default_to_state = $quantity - $previous_quantity > 0 ? 'IN_STOCK' : 'SOLD';

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
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function set_physical_count_data(): void {
		$this->data['physical_count']['quantity'] = (string) $this->ticket_item->get_ticket()->available();
	}

	/**
	 * Serialize the object to JSON.
	 *
	 * @since 5.24.0
	 *
	 * @return array The data array for JSON serialization.
	 */
	public function jsonSerialize(): array {
		return $this->data;
	}
}
