<?php

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * A JSON-LD class to hook and change how Orders work on Events
 * @todo rework this class to make it standalone from The Events Calendar
 */
class Tribe__Tickets__JSON_LD__Order {

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return self
	 *
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance instanceof self ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Value indicating low stock availability for a specific ticket.
	 *
	 * This can be overridden with the tribe_tickets_json_ld_low_inventory_level filter.
	 *
	 * @var int
	 */
	protected $low_stock = 5;

	/**
	 * Setup Google Event Data for tickets.
	 */
	public static function hook() {
		$myself = self::instance();

		add_filter( 'tribe_json_ld_event_object', array( $myself, 'add_ticket_data' ), 10, 3 );
	}

	/**
	 * Used to setup variables on this class
	 */
	protected function __construct() {
		/**
		 * Allow users to change the Low inventory mark
		 * This is the old filter
		 *
		 * @todo remove on 4.4
		 * @deprecated
		 * @var int
		 */
		$this->low_stock = apply_filters( 'tribe_events_tickets_google_low_inventory_level', $this->low_stock );

		/**
		 * Allow users to change the Low inventory mark
		 * @var int
		 */
		$this->low_stock = apply_filters( 'tribe_tickets_json_ld_low_inventory_level', $this->low_stock );
	}

	/**
	 * Adds the tickets data to the event Object
	 *
	 * @param array   $data
	 * @param array   $args
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function add_ticket_data( $data, $args, $post ) {
		if ( ! tribe_events_has_tickets( $post->ID ) ) {
			return $data;
		}

		$tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $post->ID );

		// Reset it
		$data->offers = array();

		foreach ( $tickets as $ticket ) {
			$data->offers[] = $this->get_offer( $ticket, $post );
		}

		return $data;
	}

	/**
	 * Builds an object representing a ticket offer.
	 *
	 * @param object  $ticket
	 * @param WP_Post $event
	 * @return object
	 */
	public function get_offer( $ticket, $event ) {
		$price = $ticket->price;
		// We use `the-events-calendar` domain to make sure it's translate-able the correct way
		$string_free = __( 'Free', 'the-events-calendar' );

		// JSON-LD can't have free as a price
		if ( strpos( strtolower( trim( $price ) ), $string_free ) !== false ) {
			$price = 0;
		}

		$offer = (object) array(
			'@type'        => 'Offer',
			'url'          => $ticket->frontend_link,
			'price'        => $price,
			'category'     => 'primary',
			'availability' => $this->get_ticket_availability( $ticket ),
		);

		if ( ! empty( $ticket->start_date ) ) {
			$offer->validFrom = date( DateTime::ISO8601, strtotime( $ticket->start_date ) );
		}

		if ( ! empty( $ticket->end_date ) ) {
			$offer->validThrough = date( DateTime::ISO8601, strtotime( $ticket->end_date ) );
		}

		/**
		 * Allows modifications to be made to the offer object representing a specific
		 * event ticket.
		 *
		 * @param object                        $offer
		 * @param Tribe__Tickets__Ticket_Object $ticket
		 * @param object                        $event
		 */
		return (object) apply_filters( 'tribe_json_ld_offer_object', $offer, $ticket, $event );
	}

	/**
	 * Returns a string indicating current availability of the ticket.
	 *
	 * @param  object  $ticket
	 * @return string
	 */
	public function get_ticket_availability( $ticket ) {
		$stock = $ticket->stock();

		if ( $stock <= 0 && $stock !== '' ) {
			return 'SoldOut';
		}
		if ( $stock >= 1 && $stock <= $this->low_stock ) {
			return 'LimitedAvailability';
		} else {
			return 'InStock';
		}
	}
}
