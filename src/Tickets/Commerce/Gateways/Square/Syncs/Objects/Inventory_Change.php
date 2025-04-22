<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use InvalidArgumentException;
use JsonSerializable;

class Inventory_Change implements JsonSerializable {
	protected const VALID_TYPES = [
		'PHYSICAL_COUNT' => 1,
		'ADJUSTMENT'     => 1,
		'TRANSFER'       => 1,
	];

	protected const VALID_STATES = [
		'IN_STOCK' => 1,
		'SOLD' => 1,
		'RETURNED_BY_CUSTOMER' => 1,
		'RESERVED_FOR_SALE' => 1,
		'NONE' => 1,
		'WASTE' => 1,
	];

	protected array $data = [];

	protected Ticket_Item $ticket_item;

	public function __construct( string $type, Ticket_Item $ticket_item, array $data ) {
		if ( ! isset( self::VALID_TYPES[ strtoupper( $type ) ] ) ) {
			throw new InvalidArgumentException( 'Invalid type' );
		}

		$this->ticket_item = $ticket_item;

		if ( ! has_action( 'tec_tickets_commerce_square_sync_inventory_changed_' . $this->ticket_item->get_id() ) ) {
			add_action( 'tec_tickets_commerce_square_sync_inventory_changed_' . $this->ticket_item->get_id(), [ $this, 'process_inventory_changed' ] );
		}

		$this->data['type'] = $type;

		$this->data[ strtolower( $type ) ] = [
			'catalog_object_id'   => $this->ticket_item->get_id(),
			'catalog_object_type' => Ticket_Item::ITEM_TYPE,
			'location_id'         => $data['location_id'],
		];

		$method = 'set_' . strtolower( $type ) . '_data';

		if ( is_callable( [ $this, $method ] ) ) {
			$this->$method( $data );
		}
	}

	public function process_inventory_changed( array $data ): void {
		$ticket = $this->ticket_item->get_ticket();

		$count = $ticket->available();

		if ( $count === -1 ) {
			if ( $data['state'] !== 'IN_STOCK' ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync', 'Ticket ' . $this->ticket_item->get_id() . ' has unlimited stock but Square says it is not in stock.' );
			}
			return;
		}

		if ( $count > 0 ) {
			if ( $data['state'] !== 'IN_STOCK' ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync', 'Ticket ' . $this->ticket_item->get_id() . ' has ' . $count . ' in stock but Square says it is not in stock.' );
			}

			if ( $data['quantity'] !== $count ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync', 'Ticket ' . $this->ticket_item->get_id() . ' has ' . $count . ' in stock but Square says it is ' . $data['quantity'] . '.' );
			}

			return;
		}

		if ( $count === 0 ) {
			if ( $data['state'] !== 'SOLD' ) {
				do_action( 'tribe_log', 'error', 'Square Inventory Sync', 'Ticket ' . $this->ticket_item->get_id() . ' has no stock but Square says it is not sold.' );
			}
		}
	}

	public function set_adjustment_data( array $data = [] ): void {
		$this->data['adjustment'] = array_merge(
			$this->data['adjustment'],
			[
				'from_state' => $data['from_state'] ?? 'IN_STOCK',
				'to_state'   => $data['to_state'] ?? 'IN_STOCK',
			]
		);
	}

	public function set_physical_count_data(): void {
		$this->data['physical_count']['quantity'] = $this->ticket_item->get_ticket()->available();
	}

	public function jsonSerialize(): array {
		return $this->data;
	}
}