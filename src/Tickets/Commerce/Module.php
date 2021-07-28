<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Tickets Provider class for Tickets Commerce
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Module extends \Tribe__Tickets__Tickets {

	public function __construct() {
		parent::__construct();

		$this->attendee_ticket_sent = '_tribe_tpp_attendee_ticket_sent';

		$this->attendee_optout_key = Attendee::$optout_meta_key;

		$this->attendee_tpp_key = Attendee::$status_meta_key;

		$this->ticket_object = Ticket::POSTTYPE;

		$this->event_key = Attendee::$event_relation_meta_key;

		$this->checkin_key = Attendee::$checked_in_meta_key;

		$this->order_key = Attendee::$order_relation_meta_key;

		$this->refund_order_key = '_tribe_tpp_refund_order';

		$this->security_code = Attendee::$security_code_meta_key;

		$this->full_name = '_tribe_tpp_full_name';

		$this->email = Attendee::$purchaser_email_meta_key;
	}

	/**
	 * {@inheritdoc}
	 */
	public $orm_provider = \TEC\Tickets\Commerce::PROVIDER;

	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ATTENDEE_OBJECT = Attendee::POSTTYPE;

	/**
	 * Name of the CPT that holds Orders
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ORDER_OBJECT = Order::POSTTYPE;

	/**
	 * Meta key that relates Attendees and Events.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ATTENDEE_EVENT_KEY = '_tec_tickets_commerce_event';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ATTENDEE_PRODUCT_KEY = '_tec_tickets_commerce_product';

	/**
	 * Meta key that relates Attendees and Orders.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ATTENDEE_ORDER_KEY = '_tec_tickets_commerce_order';

	/**
	 * Indicates if a ticket for this attendee was sent out via email.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $attendee_ticket_sent;

	/**
	 * Meta key that if this attendee wants to show on the attendee list
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $attendee_optout_key;

	/**
	 * Meta key that if this attendee PayPal status
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $attendee_tpp_key;

	/**
	 * Name of the CPT that holds Tickets
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $ticket_object;

	/**
	 * Meta key that relates Products and Events
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $event_key;

	/**
	 * Meta key that stores if an attendee has checked in to an event
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $checkin_key;

	/**
	 * Meta key that ties attendees together by order
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $order_key;

	/**
	 * Meta key that ties attendees together by refunded order
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $refund_order_key;

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $security_code;

	/**
	 * Meta key that holds the full name of the tickets PayPal "buyer"
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $full_name;

	/**
	 * Meta key that holds the email of the tickets PayPal "buyer"
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Meta key that holds the name of a ticket to be used in reports if the Product is deleted
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $deleted_product = '_tribe_deleted_product_name';

	/**
	 * @var bool Whether pending stock logic should be ignored or not no matter the Settings.
	 *           This is an internal property. Use the `tec_tickets_commerce_pending_stock_ignore`
	 *           filter or the accessor method to manipulate this value from another class.
	 */
	protected $ignore_pending_stock_logic = false;

	/**
	 * A variable holder if PayPal is loaded
	 *
	 * @since TBD
	 *
	 * @var boolean
	 */
	protected $is_loaded = false;

	/**
	 * This method is required for the module to properly load.
	 *
	 * @since TBD

	 * @return static
	 */
	public static function get_instance() {
		return tribe( static::class );
	}
	/**
	 * Registers all actions/filters
	 *
	 * @since TBD
	 */
	public function hooks() {
		// if the hooks have already been bound, don't do it again
		if ( $this->is_loaded ) {
			return false;
		}

//		add_filter( 'post_updated_messages', [ $this, 'updated_messages' ] );
//		add_action( 'tpp_checkin', [ $this, 'purge_attendees_transient' ] );
//		add_action( 'tpp_uncheckin', [ $this, 'purge_attendees_transient' ] );
//

//		add_action( 'init', tribe_callback( 'tickets.commerce.paypal.orders.report', 'hook' ) );
//		add_filter( 'event_tickets_attendees_tpp_checkin_stati', [ $this, 'filter_event_tickets_attendees_tpp_checkin_stati' ] );
//		add_action( 'admin_init', tribe_callback( 'tickets.commerce.paypal.notices', 'hook' ) );\
//		add_action( 'tribe_tickets_attendees_page_inside', tribe_callback( 'tickets.commerce.paypal.orders.tabbed-view', 'render' ) );
//		add_filter( 'tribe_tickets_stock_message_available_quantity', tribe_callback( 'tickets.commerce.paypal.orders.sales', 'filter_available' ), 10, 4 );
//		add_action( 'admin_init', tribe_callback( 'tickets.commerce.paypal.oversell.request', 'handle' ) );


		// @todo Address we if need to send something here.
//		add_filter( 'tribe_tickets_cart_urls', [ $this, 'add_cart_url' ], 10, 2 );
//		add_filter( 'tribe_tickets_checkout_urls', [ $this, 'add_checkout_url' ], 10, 2 );
	}

	/**
	 * Send RSVPs/tickets email for attendees.
	 *
	 * @since TBD
	 *
	 * @param array       $attendees   List of attendees.
	 * @param array       $args        {
	 *                                 The list of arguments to use for sending ticket emails.
	 *
	 * @type string       $subject     The email subject.
	 * @type string       $content     The email content.
	 * @type string       $from_name   The name to send tickets from.
	 * @type string       $from_email  The email to send tickets from.
	 * @type array|string $headers     The list of headers to send.
	 * @type array        $attachments The list of attachments to send.
	 * @type string       $provider    The provider slug (rsvp, tpp, woo, edd).
	 * @type int          $post_id     The post/event ID to send the emails for.
	 * @type string|int   $order_id    The order ID to send the emails for.
	 * }
	 *
	 * @return int The number of emails sent successfully.
	 */
	public function send_tickets_email_for_attendees( $attendees, $args = [] ) {
		$args = array_merge(
			[
				'subject'    => tribe_get_option( 'ticket-paypal-confirmation-email-subject', false ),
				'from_name'  => tribe_get_option( 'ticket-paypal-confirmation-email-sender-name', false ),
				'from_email' => tribe_get_option( 'ticket-paypal-confirmation-email-sender-email', false ),
				'provider'   => 'tpp',
			],
			$args
		);

		return parent::send_tickets_email_for_attendees( $attendees, $args );
	}

	/**
	 * Shows the tickets form in the front end
	 *
	 * @since TBD
	 *
	 * @param $content
	 *
	 * @return void
	 */
	public function front_end_tickets_form( $content ) {

		$post    = $GLOBALS['post'];
		$tickets = $this->get_tickets( $post->ID );

		foreach ( $tickets as $index => $ticket ) {
			if ( __CLASS__ !== $ticket->provider_class ) {
				unset( $tickets[ $index ] );
			}
		}

		if ( empty( $tickets ) ) {
			return;
		}

		tribe( Tickets_View::class )->get_tickets_block( $post->ID );
	}

	/**
	 * Indicates if we currently require users to be logged in before they can obtain
	 * tickets.
	 *
	 * @since 4.7
	 *
	 * @return bool
	 */
	public function login_required() {
		$requirements = (array) tribe_get_option( 'ticket-authentication-requirements', array() );

		return in_array( 'event-tickets_all', $requirements, true );
	}

	/**
	 * Get attendees by id and associated post type
	 * or default to using $post_id
	 *
	 * @since TBD
	 *
	 * @param      $post_id
	 * @param null $post_type
	 *
	 * @return array|mixed
	 */
	public function get_attendees_by_id( $post_id, $post_type = null ) {
		if ( ! $post_type ) {
			$post_type = get_post_type( $post_id );
		}

		switch ( $post_type ) {
			case $this->attendee_object:
				return $this->get_attendees_by_attendee_id( $post_id );

				break;
			case 'tpp_order_hash':
				return $this->get_attendees_by_order_id( $post_id );

				break;
			case $this->ticket_object:
				return $this->get_attendees_by_ticket_id( $post_id );

				break;
			default:
				return $this->get_attendees_by_post_id( $post_id );

				break;
		}

	}

	/**
	 * Links to sales report for all tickets for this event.
	 *
	 * @since TBD
	 *
	 * @param int  $event_id
	 * @param bool $url_only
	 *
	 * @return string
	 */
	public function get_event_reports_link( $event_id, $url_only = false ) {
		$ticket_ids = (array) $this->get_tickets_ids( $event_id );
		if ( empty( $ticket_ids ) ) {
			return '';
		}

		$query = array(
			'page'    => 'tpp-orders',
			'post_id' => $event_id,
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );

		/**
		 * Filter the PayPal Ticket Orders (Sales) Report URL
		 *
		 * @var string $report_url Report URL
		 * @var int    $event_id   The post ID
		 * @var array  $ticket_ids An array of ticket IDs
		 *
		 * @return string
		 */
		$report_url = apply_filters( 'tribe_tickets_paypal_report_url', $report_url, $event_id, $ticket_ids );

		return $url_only
			? $report_url
			: '<small> <a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Sales report', 'event-tickets' ) . '</a> </small>';
	}

	/**
	 * Links to the sales report for this product.
	 *
	 * @since TBD
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return string
	 */
	public function get_ticket_reports_link( $event_id, $ticket_id ) {
		if ( empty( $ticket_id ) ) {
			return '';
		}

		$query = array(
			'page'        => 'tpp-orders',
			'product_ids' => $ticket_id,
			'post_id'     => $event_id,
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );

		return '<span><a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Report', 'event-tickets' ) . '</a></span>';
	}

	/**
	 * Get's the product price html
	 *
	 * @since TBD
	 *
	 * @param int|object    $product
	 * @param array|boolean $attendee
	 *
	 * @return string
	 */
	public function get_price_html( $product, $attendee = false ) {
		$product_id = $product;

		if ( $product instanceof \WP_Post ) {
			$product_id = $product->ID;
		} elseif ( is_numeric( $product_id ) ) {
			$product = get_post( $product_id );
		} else {
			return '';
		}

		$price = get_post_meta( $product_id, '_price', true );
		$price = tribe( 'tickets.commerce.paypal.currency' )->format_currency( $price, $product_id );

		$price_html = '<span class="tribe-tickets-price-amount amount">' . esc_html( $price ) . '</span>';

		/**
		 * Allow filtering of the Price HTML
		 *
		 * @since 4.7
		 *
		 * @param string $price_html
		 * @param mixed  $product
		 * @param mixed  $attendee
		 *
		 */
		return apply_filters( 'tec_tickets_commerce_ticket_price_html', $price_html, $product, $attendee );
	}

	/**
	 * Filters the array of statuses that will mark an ticket attendee as eligible for check-in.
	 *
	 * @since TBD
	 *
	 * @param array $statuses An array of statuses that should mark an ticket attendee as
	 *                        available for check-in.
	 *
	 * @return array The original array plus the 'yes' status.
	 */
	public function filter_event_tickets_attendees_tpp_checkin_stati( array $statuses = array() ) {
		$statuses[] = 'completed';

		return array_unique( $statuses );
	}

	/**
	 * Returns the value of a key defined by the class.
	 *
	 * @since TBD
	 *
	 * @param string $key
	 *
	 * @return string The key value or an empty string if not defined.
	 */
	public static function get_key( $key ) {
		$instance = self::get_instance();
		$key      = strtolower( $key );

		$constant_map = [
			'attendee_event_key'   => $instance->attendee_event_key,
			'attendee_product_key' => $instance->attendee_product_key,
			'attendee_order_key'   => $instance->order_key,
			'attendee_optout_key'  => $instance->attendee_optout_key,
			'attendee_tpp_key'     => $instance->attendee_tpp_key,
			'event_key'            => $instance->get_event_key(),
			'checkin_key'          => $instance->checkin_key,
			'order_key'            => $instance->order_key,
		];

		return \Tribe__Utils__Array::get( $constant_map, $key, '' );
	}

	/**
	 * Indicates if global stock support is enabled for this provider.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function supports_global_stock() {
		/**
		 * Allows the declaration of global stock support for Tribe Commerce tickets
		 * to be overridden.
		 *
		 * @param bool $enable_global_stock_support
		 */
		return (bool) apply_filters( 'tec_tickets_commerce_enable_global_stock', true );
	}

	/**
	 * Gets the product price value
	 *
	 * @since TBD
	 *
	 * @param int|\WP_Post $product
	 *
	 * @return string
	 */
	public function get_price_value( $product ) {
		if ( ! $product instanceof \WP_Post ) {
			$product = get_post( $product );
		}

		if ( ! $product instanceof \WP_Post ) {
			return false;
		}

		return get_post_meta( $product->ID, '_price', true );
	}

	/**
	 * Whether a specific attendee is valid toward inventory decrease or not.
	 *
	 * By default only attendees generated as part of a Completed order will count toward
	 * an inventory decrease but, if the option to reserve stock for Pending Orders is activated,
	 * then those attendees generated as part of a Pending Order will, for a limited time after the
	 * order creation, cause the inventory to be decreased.
	 *
	 * @since TBD
	 *
	 * @param array $attendee
	 *
	 * @return bool
	 */
	public function attendee_decreases_inventory( array $attendee ) {
		$order_status = \Tribe__Utils__Array::get( $attendee, 'order_status', 'undefined' );
		$order_id     = \Tribe__Utils__Array::get( $attendee, 'order_id', false );
		$attendee_id  = \Tribe__Utils__Array::get( $attendee, 'attendee_id', false );

		/**
		 * Whether the pending Order stock reserve logic should be ignored completely or not.
		 *
		 * If set to `true` then the behaviour chosen in the Settings will apply, if `false`
		 * only Completed tickets will count to decrease the inventory. This is useful when
		 *
		 * @since 4.7
		 *
		 * @param bool  $ignore_pending
		 * @param array $attendee An array of data defining the current Attendee
		 */
		$ignore_pending = apply_filters( 'tec_tickets_commerce_pending_stock_ignore', $this->ignore_pending_stock_logic );

		$purchase_time = false;
		$order         = false;

		if (
			'on-pending' === tribe_get_option( 'ticket-paypal-stock-handling', 'on-complete' )
			&& ! $ignore_pending
			&& Order_Statuses::$pending === $order_status
			&& false !== $order_id
		) {
			$purchase_time = \Tribe__Utils__Array::get( $attendee, 'purchase_time', false );

			$order = \Tribe__Tickets__Commerce__PayPal__Order::from_attendee_id(
				$attendee_id,
				[
					// Get no meta fields.
				]
			);

			if ( false !== $order ) {
				$purchase_time = $order->get_creation_date();
			}
		}

		if ( $purchase_time ) {
			$date = \Tribe__Date_Utils::build_date_object( $purchase_time );

			$date->setTimezone( new \DateTimeZone( 'UTC' ) );

			$order_creation_timestamp = $date->getTimestamp();

			/**
			 * Filters the amount of time a part of the stock will be reserved by a pending Order.
			 *
			 * The time applies from the Order creation time.
			 * In the unlikely scenario that an Order goes from Completed to Pending then, if the
			 * reservation time allows it, a part of the stock will be reserved for it.
			 *
			 * @since 4.7
			 *
			 * @param int                                     $pending_stock_reservation_time  The amount of seconds, from the Order creation time,
			 *                                                                                 part of the stock will be reserved for the Order;
			 *                                                                                 defaults to 30 minutes.
			 * @param array                                   $attendee                        An array of data defining the current Attendee
			 * @param \Tribe__Tickets__Commerce__PayPal__Order $order                           The object representing the Order that generated
			 *                                                                                 the Attendee
			 */
			$pending_stock_reservation_time = (int) apply_filters( 'tec_tickets_commerce_pending_stock_reserve_time', 30 * 60, $attendee, $order );

			return time() <= ( $order_creation_timestamp + $pending_stock_reservation_time );
		}

		return Order_Statuses::$completed === $order_status;
	}

	/**
	 * Whether the Pending Order stock reservation logic should be ignored or
	 * not, no matter the Settings.
	 *
	 * This is useful when trying to get the "true" inventory of a ticket.
	 *
	 * @since TBD
	 *
	 * @see \Tribe__Tickets__Commerce__PayPal__Main::attendee_decreases_inventory
	 *
	 * @param bool $ignore_pending_stock_logic
	 *
	 */
	public function ignore_pending_stock_logic( $ignore_pending_stock_logic ) {
		$this->ignore_pending_stock_logic = (bool) $ignore_pending_stock_logic;
	}

	/**
	 * All the methods below here were created merely as a backwards compatibility piece for our old Code that
	 * depends so much on the concept of a Main class handling all kinds of integration pieces.
	 *
	 * ! DO NOT INTRODUCE MORE LOGIC OR COMPLEXITY ON THESE METHODS !
	 *
	 * The methods are all focused on routing functionality to their correct handlers.
	 */

	/**
	 * {@inheritdoc}
	 */
	public function get_attendee( $attendee, $post_id = 0 ) {
		return tec_tc_get_attendee( $attendee, ARRAY_A );
	}

	/**
	 * Event Tickets Plus Admin Reports page will use this data from this method.
	 *
	 * @since TBD
	 *
	 *
	 * @param string|int $order_id
	 *
	 * @return array
	 */
	public function get_order_data( $order_id ) {
		return tec_tc_get_order( $order_id, ARRAY_A );
	}

	/**
	 * Renders the advanced fields in the new/edit ticket form.
	 * Using the method, providers can add as many fields as
	 * they want, specific to their implementation.
	 *
	 * @since TBD
	 *
	 * @param int $post_id
	 * @param int $ticket_id
	 */
	public function do_metabox_capacity_options( $post_id, $ticket_id ) {
		tribe( Editor\Metabox::class )->do_metabox_capacity_options( $post_id, $ticket_id );
	}

	/**
	 * Maps to the Cart Class method to get the cart.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_cart_url() {
		return tribe( Cart::class )->get_url();
	}

	/**
	 * Generate and store all the attendees information for a new order.
	 *
	 * @since TBD
	 *
	 * @param string $payment_status The tickets payment status, defaults to completed.
	 * @param bool   $redirect       Whether the client should be redirected or not.
	 */
	public function generate_tickets( $payment_status = 'completed', $redirect = true ) {
		tribe( Order::class )->generate_order( $payment_status, $redirect );
	}

	/**
	 * Gets an individual ticket.
	 *
	 * @since TBD
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return null|\Tribe__Tickets__Ticket_Object
	 */
	public function get_ticket( $event_id, $ticket_id ) {
		return tribe( Ticket::class )->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * Saves a Tickets Commerce ticket.
	 *
	 * @since TBD
	 *
	 * @param int                           $post_id  Post ID.
	 * @param \Tribe__Tickets__Ticket_Object $ticket   Ticket object.
	 * @param array                         $raw_data Ticket data.
	 *
	 * @return int|false The updated/created ticket post ID or false if no ticket ID.
	 */
	public function save_ticket( $post_id, $ticket, $raw_data = [] ) {
		// Run anything we might need on parent method.
		parent::save_ticket( $post_id, $ticket, $raw_data );

		/**
		 * Important, do not add anything above this method.
		 * Our goal is to reduce the amount of load on the `Module`, relegate these behaviors to the correct models.
		 */
		return tribe( Ticket::class )->save( $post_id, $ticket, $raw_data );
	}

	/**
	 * Deletes a ticket.
	 *
	 * @since TBD
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return bool
	 */
	public function delete_ticket( $event_id, $ticket_id ) {
		/**
		 * Important, do not add anything above this method.
		 * Our goal is to reduce the amount of load on the `Module`, relegate these behaviors to the correct models.
		 */
		$deleted = tribe( Ticket::class )->delete( $event_id, $ticket_id );

		if ( ! $deleted ) {
			return $deleted;
		}

		// Run anything we might need on parent method.
		parent::delete_ticket( $event_id, $ticket_id );

		return $deleted;
	}

		/**
		 * Return whether we're currently on the checkout page for Tickets Commerce.
		 *
		 * @since TBD
		 *
		 * @return bool
		 */
		public function is_checkout_page() {
			return tribe( Checkout::class )->is_current_page();
		}
}
