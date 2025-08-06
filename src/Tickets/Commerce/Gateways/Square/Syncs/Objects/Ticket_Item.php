<?php
/**
 * Ticket Item object for Square synchronization.
 *
 * This class represents a Ticket as an Item Variation in Square's catalog. It handles
 * the mapping between a WordPress Ticket and its representation in Square.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Commerce__Currency as Currency;

/**
 * Class Ticket_Item
 *
 * Handles the representation of a WordPress Ticket as a Square catalog item variation.
 * Tickets in Square are represented as variations of an Event item.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class Ticket_Item extends Item {
	/**
	 * The type of Square catalog item this class represents.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const ITEM_TYPE = 'ITEM_VARIATION';

	/**
	 * The data structure for the Square catalog item variation.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	protected array $data = [
		'type'                     => self::ITEM_TYPE,
		'id'                       => null,
		'is_deleted'               => false,
		'present_at_all_locations' => false,
		'present_at_location_ids'  => [],
		'item_variation_data'      => [
			'name'                      => '', // max 255 chars.
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
		],
	];

	/**
	 * The WordPress ticket object.
	 *
	 * @since 5.24.0
	 *
	 * @var Ticket_Object
	 */
	protected Ticket_Object $ticket;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Ticket_Object $ticket The ticket object to represent in Square.
	 */
	public function __construct( Ticket_Object $ticket ) {
		$this->ticket = $ticket;
		$this->register_hooks();
	}

	/**
	 * Get the WordPress ID of the ticket.
	 *
	 * @since 5.24.0
	 *
	 * @return int The ticket post ID.
	 */
	public function get_wp_id(): int {
		return $this->ticket->ID;
	}

	/**
	 * Get the ticket object.
	 *
	 * @since 5.24.0
	 *
	 * @return Ticket_Object The ticket object.
	 */
	public function get_ticket(): Ticket_Object {
		return $this->ticket;
	}

	/**
	 * Set the object values for synchronization with Square.
	 *
	 * @since 5.24.0
	 *
	 * @return array The data array prepared for Square synchronization.
	 */
	protected function set_object_values(): array {
		$this->set( 'is_deleted', ! $this->ticket->get_event() || $this->ticket->get_event()->post_status === 'trash' );
		$this->set_item_data( 'name', $this->ticket->name ? $this->ticket->name : __( 'Untitled Ticket', 'event-tickets' ) );
		$this->set_item_data( 'sku', $this->ticket->sku );
		$this->set_item_data( 'sellable', time() + 30 < $this->ticket->end_date() );
		$this->set_item_data(
			'price_money',
			[
				'amount'   => (int) ( ( (float) $this->ticket->price ) * 100 ),
				'currency' => Currency::get_provider_currency_code( $this->ticket->provider_class, $this->ticket->ID ),
			]
		);

		return $this->data;
	}
}
