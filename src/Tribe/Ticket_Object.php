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
		 * Whether to show the description on the front end and in emails
		 *
		 * @since TBD
		 *
		 * @var boolean
		 */
		public $show_description = true;

		/**
		 * Meta data key we store show_description under
		 *
		 * @since TBD
		 *
		 * @var string
		 */
		public $show_description_key = '_ticket_show_description';

		/**
		 * Current sale price, without any sign. Just a float.
		 *
		 * @var float
		 */
		public $price;

		/**
		 * Ticket Capacity
		 *
		 * @since  TBD
		 *
		 * @var    int
		 */
		public $capacity;

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
		 * Link to the report screen for this ticket in the provider system,
		 * or null if the provider doesn't have any sales reports.
		 * @var string
		 */
		public $report_link;

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
		 * Holds the SKU for the ticket
		 *
		 * @var string
		 */
		public $sku;

		/**
		 * Holds the menu order for the ticket
		 *
		 * @since TBD
		 *
		 * @var string
		 */
		public $menu_order;

		/**
		 * @var Tribe__Tickets__Tickets
		 */
		protected $provider;

		/**
		 * Amount of tickets of this kind in stock
		 * Use $this->stock( value ) to set manage and get the value
		 *
		 * @var mixed
		 */
		protected $stock = 0;

		/**
		 * The mode of stock handling to be used for the ticket when global stock
		 * is enabled for the event.
		 *
		 * @var string
		 */
		protected $global_stock_mode = Tribe__Tickets__Global_Stock::OWN_STOCK_MODE;

		/**
		 * The maximum permitted number of sales for this ticket when global stock
		 * is enabled for the event and CAPPED_STOCK_MODE is in effect.
		 *
		 * @var int
		 */
		protected $global_stock_cap = 0;

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
		 * Date the ticket should be put on sale
		 *
		 * @var string
		 */
		public $start_date;

		/**
		 * Time the ticket should be put on sale
		 *
		 * @since TBD
		 *
		 * @var string
		 */
		public $start_time;

		/**
		 * Date the ticket should be stop being sold
		 * @var string
		 */
		public $end_date;

		/**
		 * Time the ticket should be stop being sold
		 *
		 * @since TBD
		 *
		 * @var string
		 */
		public $end_time;

		/**
		 * Purchase limite for the ticket
		 *
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
		 * Get the ticket's start date
		 *
		 * @since 4.2
		 *
		 * @return string
		 */
		public function start_date() {
			$start_date = null;
			if ( ! empty( $this->start_date ) ) {
				$start_date = strtotime( $this->start_date );
			}

			return $start_date;
		}

		/**
		 * Get the ticket's end date
		 *
		 * @since 4.2
		 *
		 * @return string
		 */
		public function end_date() {
			$end_date = null;
			if ( ! empty( $this->end_date ) ) {
				$end_date = strtotime( $this->end_date );
			} else {
				$post_id = get_the_ID();

				/**
				 * Set a default end date for tickets if the end date wasn't specified in the registration form
				 *
				 * @param $date End date for the tickets (defaults to tomorrow ... which means registrations will not end)
				 * @param $post_id Post id for the post that tickets are attached to
				 */
				$end_date = apply_filters( 'tribe_tickets_default_end_date', date( 'Y-m-d G:i', strtotime( '+1 day' ) ), $post_id );
				$end_date = strtotime( $end_date );
			}

			return $end_date;
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

			$start_date = $this->start_date();
			$end_date   = $this->end_date();

			return ( empty( $start_date ) || $timestamp > $start_date ) && ( empty( $end_date ) || $timestamp < $end_date );
		}

		/**
		 * Determines if the given date is smaller than the ticket's start date
		 *
		 * @param string $datetime The date/time that we want to determine if it is smaller than the ticket's start date
		 *
		 * @return boolean Whether or not the provided date/time is smaller than the ticket's start date
		 */
		public function date_is_earlier( $datetime ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} else {
				$timestamp = strtotime( $datetime );
			}

			$start_date = $this->start_date();

			return empty( $start_date ) || $timestamp < $start_date;
		}

		/**
		 * Determines if the given date is greater than the ticket's end date
		 *
		 * @param string $datetime The date/time that we want to determine if it is smaller than the ticket's start date
		 *
		 * @return boolean Whether or not the provided date/time is greater than the ticket's end date
		 */
		public function date_is_later( $datetime ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} else {
				$timestamp = strtotime( $datetime );
			}

			$end_date = $this->end_date();

			return empty( $end_date ) || $timestamp > $end_date;
		}

		/**
		 * Returns ticket availability slug
		 *
		 * The availability slug is used for CSS class names and filter helper strings
		 *
		 * @since 4.2
		 *
		 * @return string
		 */
		public function availability_slug( $datetime = null ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} elseif ( $datetime ) {
				$timestamp = strtotime( $datetime );
			} else {
				$timestamp = current_time( 'timestamp' );
			}

			$slug = 'available';

			if ( $this->date_is_earlier( $timestamp ) ) {
				$slug = 'availability-future';
			} elseif ( $this->date_is_later( $timestamp ) ) {
				$slug = 'availability-past';
			}

			/**
			 * Filters the availability slug
			 *
			 * @param string Slug
			 * @param string Datetime string
			 */
			$slug = apply_filters( 'event_tickets_availability_slug', $slug, $datetime );

			return $slug;
		}

		/**
		 * Provides the quantity of original stock of tickets
		 *
		 * @todo  re-strcture this method if it's used elsewhere
		 *
		 * @return int
		 */
		public function original_stock() {
			if ( ! $this->managing_stock() ) {
				return '';
			}

			$global_stock_mode = $this->global_stock_mode();

			if ( Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $global_stock_mode ) {
				$global_stock_obj = new Tribe__Tickets__Global_Stock( $this->get_event()->ID );
				return $global_stock_obj->get_stock_level() + $global_stock_obj->tickets_sold();
			} elseif ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $global_stock_mode ) {
				return $this->global_stock_cap() + $this->qty_sold();
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
		 * Method to get the total `stock` property of the Object.
		 *
		 * Returns the current ticket total capacity: either an integer or a
		 * string if stock is unlimited.
		 *
		 * @return int|string
		 */
		public function get_original_stock() {
			$orginal_stock = $this->original_stock();
			$global_stock_mode = $this->global_stock_mode();

			if ( empty( $global_stock_mode ) || empty( $orginal_stock ) ) {
				$orginal_stock = Tribe__Tickets__Tickets_Handler::instance()->unlimited_term;
			}

			return $orginal_stock;
		}

		/**
		 * Method to display the total `stock` property of the Object.
		 *
		 * Returns the current ticket total capacity: either an integer or a
		 * string if stock is unlimited.
		 *
		 * @param bool (true) $display whether to echo or return the value
		 *
		 * @return string - escaped
		 */
		public function display_original_stock( $display = true ) {
			if ( empty( $display ) ) {
				return $this->get_original_stock();
			}

			echo esc_html( $this->get_original_stock() );
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
		 * Turns a Stock, Remaining or Capacity into a Human Readable Format
		 *
		 * @since  TBD
		 *
		 * @param  string|int $number Which you are tring to convert
		 * @param  string     $mode   Mode this post is on
		 *
		 * @return string
		 */
		public function get_readable_format( $number, $mode = 'own' ) {
			$html = array();

			$show_parens = Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $mode || Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $mode;
			if ( $show_parens ) {
				$html[] = '(';
			}

			if ( -1 === (int) $number || self::UNLIMITED_STOCK === $number ) {
				$html[] = esc_html( ucfirst( tribe( 'tickets.handler' )->unlimited_term ) );
			} else {
				$html[] = esc_html( $number );
			}

			if ( $show_parens ) {
				$html[] = ')';
			}

			return implode( '', $html );
		}

		/**
		 * Provides the quantity of remaining tickets
		 *
		 * @return int
		 */
		public function remaining() {
			// if we aren't tracking stock, then always assume it is in stock or capacity is unlimited
			if ( ! $this->managing_stock() || -1 === $this->capacity() ) {
				return -1;
			}

			// Do the math!
			$remaining = $this->stock() - $this->qty_sold() - $this->qty_pending();

			// Adjust if using global stock with a sales cap
			if ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $this->global_stock_mode() ) {
				$global_stock_obj = new Tribe__Tickets__Global_Stock( $this->get_event()->ID );
				$remaining = min( $remaining, $this->capacity() - $global_stock_obj->tickets_sold() );
			}

			// Prevents Negative
			return max( $remaining, 0 );
		}

		/**
		 * Gets the Capacity for the Ticket
		 *
		 *	@since  TBD
		 *
		 * @return string|int
		 */
		public function capacity( ) {
			$stock_mode = $this->global_stock_mode();

			// Unlimited is always unlimited
			if ( -1 === (int) $this->capacity ) {
				return (int) $this->capacity;
			}

			// If Capped or we used the local Capacity
			if (
				Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $stock_mode
				|| Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === $stock_mode ) {
				return (int) $this->capacity;
			}

			$event_capacity = get_post_meta( $this->get_event()->ID, tribe( 'tickets.handler' )->key_capacity, true );

			return (int) $event_capacity;
		}

		/**
		 * Method to manage the protected `stock` property of the Object
		 * Prevents setting `stock` lower then zero.
		 *
		 * Returns the current ticket stock level: either an integer or an
		 * empty string (Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK)
		 * if stock is unlimited.
		 *
		 * @param int|null $value This will overwrite the old value
		 *
		 * @return int|string
		 */
		public function stock( $value = null ) {
			// If the Value was passed as numeric value overwrite
			if ( is_numeric( $value ) || $value === self::UNLIMITED_STOCK ) {
				$this->stock = $value;
			}

			// if stock is negative, force it to 0
			$this->stock = 0 >= $this->stock ? 0 : $this->stock;

			// return the new Stock
			return $this->stock;
		}

		/**
		 * Sets or gets the current global stock mode in effect for the ticket.
		 *
		 * Typically this is one of the constants provided by Tribe__Tickets__Global_Stock:
		 *
		 *     GLOBAL_STOCK_MODE if it should draw on the global stock
		 *     CAPPED_STOCK_MODE as above but with a limit on the total number of allowed sales
		 *     OWN_STOCK_MODE if it should behave as if global stock is not in effect
		 *
		 * @param string $mode
		 *
		 * @return string
		 */
		public function global_stock_mode( $mode = null ) {
			if ( ! is_null( $mode ) ) {
				$this->global_stock_mode = $mode;
			}

			return $this->global_stock_mode;
		}

		/**
		 * Sets or gets any cap on sales that might be in effect for this ticket when global stock
		 * mode is in effect.
		 *
		 * @param int $cap
		 *
		 * @return int
		 */
		public function global_stock_cap( $cap = null ) {
			if ( is_numeric( $cap ) ) {
				$this->global_stock_cap = (int) $cap;
			}

			return (int) $this->global_stock_cap;
		}

		/**
		 * Method to manage the protected `qty_sold` property of the Object
		 * Prevents setting `qty_sold` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_sold( $value = null ) {
			return $this->qty_getter_setter( $this->qty_sold, $value );
		}

		/**
		 * Method to manage the protected `qty_pending` property of the Object
		 * Prevents setting `qty_pending` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_pending( $value = null ) {
			return $this->qty_getter_setter( $this->qty_pending, $value );
		}

		/**
		 * Method to get/set protected quantity properties, disallowing illegal
		 * things such as setting a negative value.
		 *
		 * Callables are also supported, allowing properties to be lazily fetched
		 * or calculated on demand.
		 *
		 * @param int               &$property
		 * @param int|callable|null $value
		 *
		 * @return int|mixed
		 */
		protected function qty_getter_setter( &$property, $value = null ) {
			// Set to a positive numeric value
			if ( is_numeric( $value ) ) {
				$property = (int) $value;

				// Disallow negative values (and force to zero if one is passed)
				$property = max( (int) $property, 0 );
			}

			// Set to a callback
			if ( is_callable( $value ) ) {
				$property = $value;
			}

			// Return the callback's output if appropriate: but only when the
			// property is being set to avoid upfront costs
			if ( null === $value && is_callable( $property ) ) {
				return call_user_func( $property, $this->ID );
			}

			// Or else return the current property value
			return $property;
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
		 * Method to manage the protected `qty_cancelled` property of the Object
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

		/**
		 * Returns an instance of the provider class.
		 *
		 * @return Tribe__Tickets__Tickets|null
		 */
		public function get_provider() {
			if ( empty( $this->provider ) ) {
				if ( empty( $this->provider_class ) || ! class_exists( $this->provider_class ) ) {
					return null;
				}

				if ( method_exists( $this->provider_class, 'get_instance' ) ) {
					$this->provider = call_user_func( array( $this->provider_class, 'get_instance' ) );
				} else {
					$this->provider = new $this->provider_class;
				}
			}

			return $this->provider;
		}

		/**
		 * Returns the ID of the event post this ticket belongs to.
		 *
		 * @return WP_Post|null
		 */
		public function get_event() {
			$provider = $this->get_provider();

			if ( null !== $provider ) {
				return $provider->get_event_for_ticket( $this->ID );
			}

			return null;
		}

		/**
		 * Returns whether the ticket description should show on
		 * the front page and in emails. Defaults to true.
		 *
		 * @since TBD
		 *
		 * @return boolean
		 */
		public function show_description() {
			$show = true;
			if ( metadata_exists( 'post', $this->ID, $this->show_description_key ) ) {
				$show = get_post_meta( $this->ID, $this->show_description_key, true );
			}

			/**
			 * Allows filtering of the value so we can for example, disable it for a theme/site
			 *
			 * @since TBD
			 *
			 * @param boolean whether to show the description or not
			 * @param int ticket ID
			 */
			return apply_filters( 'tribe_tickets_show_description', $show, $this->ID );
		}
	}
}
