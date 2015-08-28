<?php
if ( ! class_exists( 'Tribe__Tickets__Ticket_Object' ) ) {
	/**
	 *    Generic object to hold information about a single ticket
	 */
	class Tribe__Tickets__Ticket_Object {
		/**
		 * This value - an empty string - should be used to populate the stock
		 * property in situations where no limit has been placed on stock
		 * levels.
		 */
		const UNLIMITED_STOCK = '';

		/**
		 * Unique identifier
		 * @var
		 */
		public $ID;
		/**
		 * Name of the ticket
		 * @var string
		 */
		public $name;

		/**
		 * Free text with a description of the ticket
		 * @var string
		 */
		public $description;

		/**
		 * Current sale price, without any sign. Just a float.
		 *
		 * @var float
		 */
		public $price;

		/**
		 * Regular price (if the ticket is not on a special sale this will be identical to
		 * $price).
		 *
		 * @var float
		 */
		public $regular_price;

		/**
		 * Indicates if the ticket is currently being offered at a reduced price as part
		 * of a special sale.
		 *
		 * @var bool
		 */
		public $on_sale;

		/**
		 * Link to the admin edit screen for this ticket in the provider system,
		 * or null if the provider doesn't have any way to edit the ticket.
		 * @var string
		 */
		public $admin_link;

		/**
		 * Link to the front end of this ticket, if the providers has single view
		 * for this ticket.
		 * @var string
		 */
		public $frontend_link;

		/**
		 * Class name of the provider handling this ticket
		 * @var
		 */
		public $provider_class;

		/**
		 * Amount of tickets of this kind in stock
		 * @var mixed
		 */
		public $stock;

		/**
		 * Amount of tickets of this kind sold
		 * @var int
		 */
		public $qty_sold;

		/**
		 * Number of tickets for which an order has been placed but not confirmed or "completed".
		 *
		 * @var int
		 */
		public $qty_pending = 0;

		/**
		 * When the ticket should be put on sale
		 * @var
		 */
		public $start_date;

		/**
		 * When the ticket should be stop being sold
		 * @var
		 */
		public $end_date;

		/**
		 * Determines if the given date is within the ticket's start/end date range
		 *
		 * @param string $datetime The date/time that we want to determine if it falls within the start/end date range
		 *
		 * @return boolean Whether or not the provided date/time falls within the start/end date range
		 */
		public function date_in_range( $datetime ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} else {
				$timestamp = strtotime( $datetime );
			}

			$end_date = null;
			if ( ! empty( $this->end_date ) ){
				$end_date = strtotime( $this->end_date );
			} else {
				$end_date = strtotime( tribe_get_end_date( get_the_ID(), false, 'Y-m-d G:i' ) );
			}

			$start_date = null;
			if ( ! empty( $this->start_date ) ) {
				$start_date = strtotime( $this->start_date );
			}

			return ( empty( $start_date ) || $timestamp > $start_date ) && ( empty( $end_date ) || $timestamp < $end_date );
		}

		/**
		 * Determines if there is any stock for purchasing
		 *
		 * @return boolean
		 */
		public function is_in_stock() {
			// if we aren't tracking stock, then always assume it is in stock
			if ( empty( $this->stock ) ) {
				return true;
			}

			return ( absint( $this->stock ) - absint( $this->qty_sold ) - absint( $this->qty_pending ) ) > 0;
		}
	}
}
