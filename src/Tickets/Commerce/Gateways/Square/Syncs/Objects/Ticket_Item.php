<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Ticket_Item extends Item {
	public const ITEM_TYPE = 'ITEM_VARIATION';

	protected array $data = [
		'type'                     => self::ITEM_TYPE,
		'id'                       => null,
		'is_deleted'               => false,
		'present_at_all_locations' => false,
		'present_at_location_ids'  => [],
		// 'absent_at_location_ids'   => [],
		'item_variation_data'      => [
			// 'item_id'                   => '',
			'name'                      => '', // max 255
			'sku'                       => '',
			'pricing_type'              => 'FIXED_PRICING',
			'price_money'               => [
				'amount'   => 0,
				'currency' => 'USD',
			],
			'track_inventory'           => true,
			'inventory_alert_type'      => 'LOW_QUANTITY',
			'inventory_alert_threshold' => 5,
			'stockable'                 => true,
			'sellable'                  => true,
			// 'stockable_conversion'      => [
			// 	'stockable_item_variation_id' => null,
			// 	'stockable_quantity'          => 1,
			// 	'nonstockable_quantity'       => 1,
			// ],
			// 'image_ids'                 => [],
		],
	];

	protected Ticket_Object $ticket;

	public function __construct( Ticket_Object $ticket ) {
		$this->ticket     = $ticket;
		$this->register_hooks();
	}

	public function get_wp_id(): int {
		return $this->ticket->ID;
	}

	public function get_ticket(): Ticket_Object {
		return $this->ticket;
	}

	protected function set_object_values(): array {
		$this->set( 'is_deleted', ! $this->ticket->get_event() || $this->ticket->get_event()->post_status === 'trash' );
		$this->set_item_data( 'name', $this->ticket->name ? $this->ticket->name : __( 'Untitled Ticket', 'event-tickets' ) );
		$this->set_item_data( 'sku', $this->ticket->sku );
		$this->set_item_data( 'sellable', time() + 30 < $this->ticket->end_date() );
		$this->set_item_data( 'price_money', [ 'amount' => $this->ticket->price * 100, 'currency' => 'USD' ] );
		return $this->data;
	}
}
