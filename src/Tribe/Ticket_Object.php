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
		 * Use $this->stock( value ) to set manage and get the value
		 *
		 * @var mixed
		 */
		protected $stock = 0;

		/**
		 * Amount of tickets of this kind sold
		 * Use $this->qty_sold( value ) to set manage and get the value
		 *
		 * @var int
		 */
		protected $qty_sold = 0;

		/**
		 * Number of tickets for which an order has been placed but not confirmed or "completed".
		 * Use $this->qty_pending( value ) to set manage and get the value
		 *
		 * @var int
		 */
		protected $qty_pending = 0;

		/**
		 * Number of tickets for which an order has been cancelled.
		 * Use $this->qty_cancelled( value ) to set manage and get the value
		 *
		 * @var int
		 */
		protected $qty_cancelled = 0;

		/**
		 * Holds whether or not stock is being managed
		 *
		 * @var boolean
		 */
		protected $manage_stock = false;

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
		 * Purchase limite for the ticket
		 * @var
		 */
		public $purchase_limit;

		/**
		 * Returns whether or not the ticket is managing stock
		 *
		 * @param boolean $manages_stock Boolean to set stock management state
		 * @return boolean
		 */
		public function manage_stock( $manages_stock = null ) {

			if ( null !== $manages_stock ) {

				// let's catch a truthy string and consider it false
				if ( 'no' === $manages_stock ) {
					$manages_stock = false;
				}

				$this->manage_stock = (bool) $manages_stock;
			}

			return $this->manage_stock;
		}

		/**
		 * Returns whether or not the ticket is managing stock. Alias method with a friendlier name for fetching state.
		 *
		 * @param boolean $manages_stock Boolean to set stock management state
		 * @return boolean
		 */
		public function managing_stock( $manages_stock = null ) {
			return $this->manage_stock( $manages_stock );
		}

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
				$post_id = get_the_ID();

				/**
				 * Set a default end date for tickets if the end date wasn't specified in the registration form
				 *
				 * @var $date End date for the tickets (defaults to tomorrow ... which means registrations will not end)
				 * @var $post_id Post id for the post that tickets are attached to
				 */
				$end_date = apply_filters( 'tribe_tickets_default_end_date', date( 'Y-m-d G:i', strtotime( '+1 day' ) ), $post_id );
				$end_date = strtotime( $end_date );
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
			if ( ! $this->managing_stock() ) {
				return true;
			}

			$remaining = $this->remaining();

			return false === $remaining || $remaining > 0;
		}

		/**
		 * Provides the quantity of remaining tickets
		 *
		 * @return int
		 */
		public function remaining() {
			// if we aren't tracking stock, then always assume it is in stock
			if ( ! $this->managing_stock() ) {
				return false;
			}

			// Do the math!
			$remaining = $this->original_stock() - $this->qty_sold() - $this->qty_pending();

			// Prevents Negative
			return max( $remaining, 0 );
		}

		/**
		 * Provides the quantity of original stock of tickets
		 *
		 * @return int
		 */
		public function original_stock() {
			if ( ! $this->managing_stock() ) {
				return '';
			}

			$stock = $this->stock();

			// if the stock is less than 0, that means we've sold more than what we want in stock. If stock
			// holds a value greater than 0, then we want the original stock to be greater than the number
			// sold by the new stock amount. We do that with some simple math to offset the negative stock
			// with the quantity sold
			if ( 0 > $stock ) {
				$stock += $this->qty_sold();
			}

			return $stock + $this->qty_sold() + $this->qty_pending();
		}

		/**
		 * Method to manage the protected `stock` propriety of the Object
		 * Prevents setting `stock` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function stock( $value = null ) {
			// If the Value was passed as numeric value overwrite
			if ( is_numeric( $value ) ){
				$this->stock = $value;
			}

			// return the new Stock
			return $this->stock;
		}

		/**
		 * Method to manage the protected `qty_sold` propriety of the Object
		 * Prevents setting `qty_sold` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_sold( $value = null ) {
			// If the Value was passed as numeric value overwrite
			if ( is_numeric( $value ) ){
				$this->qty_sold = $value;
			}

			// Prevents qty_sold from going negative
			$this->qty_sold = max( (int) $this->qty_sold, 0 );

			// return the new Qty Sold
			return $this->qty_sold;
		}

		/**
		 * Method to manage the protected `qty_pending` propriety of the Object
		 * Prevents setting `qty_pending` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_pending( $value = null ) {
			// If the Value was passed as numeric value overwrite
			if ( is_numeric( $value ) ){
				$this->qty_pending = $value;
			}

			// Prevents qty_pending from going negative
			$this->qty_pending = max( (int) $this->qty_pending, 0 );

			// return the new Qty Pending
			return $this->qty_pending;
		}

		/**
		 * Magic getter to handle fetching protected properties
		 *
		 * @deprecated 4.0
		 * @todo Remove when event-tickets-* plugins are fully de-supported
		 */
		public function __get( $var ) {
			switch ( $var ) {
				case 'stock':
					return $this->stock();
					break;
				case 'qty_pending':
					return $this->qty_pending();
					break;
				case 'qty_sold':
					return $this->qty_sold();
					break;
				case 'qty_cancelled':
					return $this->qty_cancelled();
					break;
			}

			return null;
		}

		/**
		 * Magic setter to handle setting protected properties
		 *
		 * @deprecated 4.0
		 * @todo Remove when event-tickets-* plugins are fully de-supported
		 */
		public function __set( $var, $value ) {
			switch ( $var ) {
				case 'stock':
					return $this->stock( $value );
					break;
				case 'qty_pending':
					return $this->qty_pending( $value );
					break;
				case 'qty_sold':
					return $this->qty_sold( $value );
					break;
				case 'qty_cancelled':
					return $this->qty_cancelled( $value );
					break;
			}

			return null;
		}

		/**
		 * Method to manage the protected `qty_cancelled` propriety of the Object
		 * Prevents setting `qty_cancelled` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_cancelled(  $value = null ) {
			// If the Value was passed as numeric value overwrite
			if ( is_numeric( $value ) ) {
				$this->qty_cancelled = $value;
			}

			// Prevents qty_cancelled from going negative
			$this->qty_cancelled = max( (int) $this->qty_cancelled, 0 );

			// return the new Qty Cancelled
			return $this->qty_cancelled;
		}
	}

}
