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

	public function __construct( string $type, Ticket_Item $ticket_item ) {
		if ( ! isset( self::VALID_TYPES[ strtoupper( $type ) ] ) ) {
			throw new InvalidArgumentException( 'Invalid type' );
		}

		$this->ticket_item = $ticket_item;

		$this->data['type'] = $type;

		$this->data[ strtolower( $type ) ] = [
			'catalog_object_id'   => $this->ticket_item->get_id(),
			'catalog_object_type' => Ticket_Item::ITEM_TYPE,
			'location_id'         => $location_id,
		];

		$method = 'set_' . strtolower( $type ) . '_data';

		if ( is_callable( [ $this, $method ] ) ) {
			$this->$method();
		}
	}

	public function set_adjustment_data(): void {
		$this->data['adjustment'] = array_merge(
			$this->data['adjustment'],
			[
				'from_state' => $from_state,
				'to_state'   => $to_state,
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