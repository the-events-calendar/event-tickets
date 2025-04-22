<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use InvalidArgumentException;
use JsonSerializable;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Remote_Objects;
class Inventory_Change implements JsonSerializable {
	protected const VALID_TYPES = [
		'PHYSICAL_COUNT' => 1,
		'ADJUSTMENT'     => 1,
		'TRANSFER'       => 1,
	];

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

	protected array $data = [];

	protected Ticket_Item $ticket_item;

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
			'occurred_at'       => date( Remote_Objects::SQUARE_DATE_TIME_FORMAT, $data['occurred_at'] ?? time() ),
		];

		$method = 'set_' . strtolower( $type ) . '_data';

		if ( is_callable( [ $this, $method ] ) ) {
			$this->$method( $data );
		}
	}

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

	public function set_adjustment_data( array $data = [] ): void {
		$cache     = tribe_cache();
		$cache_key = 'square_sync_object_state_' . $this->ticket_item->get_id() . '_' . $data['location_id'];

		$cached_state     = $cache[ $cache_key ];

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

		$default_to_state = $quantity - $previous_quantity > 0 ? 'IN_STOCK' : 'SOLD_ONLINE';

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

	public function set_physical_count_data(): void {
		$this->data['physical_count']['quantity'] = (string) $this->ticket_item->get_ticket()->available();
	}

	public function jsonSerialize(): array {
		return $this->data;
	}
}