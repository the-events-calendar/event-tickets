<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Main
 *
 * Logic for tribe commerce PayPal tickets
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Main extends Tribe__Tickets__Tickets {
	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 *
	 * @var string
	 */
	const ATTENDEE_OBJECT = 'tribe_tpp_attendees';

	/**
	 * Name of the CPT that holds Orders
	 */
	const ORDER_OBJECT = 'tribe_tpp_attendees';

	/**
	 * Meta key that relates Attendees and Events.
	 *
	 * @var string
	 */
	const ATTENDEE_EVENT_KEY = '_tribe_tpp_event';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 * @var string
	 */
	const ATTENDEE_PRODUCT_KEY = '_tribe_tpp_product';

	/**
	 * Currently unused for this provider, but defined per the Tribe__Tickets__Tickets spec.
	 *
	 * @var string
	 */
	const ATTENDEE_ORDER_KEY = '';

	/**
	 * Indicates if a ticket for this attendee was sent out via email.
	 *
	 * @var boolean
	 */
	public $attendee_ticket_sent = '_tribe_tpp_attendee_ticket_sent';

	/**
	 * Meta key that if this attendee wants to show on the attendee list
	 *
	 * @var string
	 */
	public $attendee_optout_key = '_tribe_tpp_attendee_optout';

	/**
	 * Meta key that if this attendee PayPal status
	 *
	 * @var string
	 */
	public $attendee_tpp_key = '_tribe_tpp_status';

	/**
	 *Name of the CPT that holds Tickets
	 *
	 * @var string
	 */
	public $ticket_object = 'tribe_tpp_tickets';

	/**
	 * Meta key that relates Products and Events
	 * @var string
	 */
	public $event_key = '_tribe_tpp_for_event';

	/**
	 * Meta key that stores if an attendee has checked in to an event
	 * @var string
	 */
	public $checkin_key = '_tribe_tpp_checkedin';

	/**
	 * Meta key that ties attendees together by order
	 * @var string
	 */
	public $order_key = '_tribe_tpp_order';

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 * @var string
	 */
	public $security_code = '_tribe_tpp_security_code';

	/**
	 * Meta key that holds the full name of the tickets PayPal "buyer"
	 *
	 * @var string
	 */
	public $full_name = '_tribe_tpp_full_name';

	/**
	 * Meta key that holds the email of the tickets PayPal "buyer"
	 *
	 * @var string
	 */
	public $email = '_tribe_tpp_email';

	/**
	 * Meta key that holds the name of a ticket to be used in reports if the Product is deleted
	 * @var string
	 */
	public $deleted_product = '_tribe_deleted_product_name';

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Attendance_Totals
	 */
	protected $attendance_totals;

	/**
	 * Messages for submission
	 */
	protected static $messages = array();

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Tickets_View
	 */
	protected $tickets_view;

	/**
	 * Creates a Variable to prevent Double FE forms
	 * @var boolean
	 */
	private $is_frontend_tickets_form_done = false;

	/**
	 * A variable holder if PayPal is loaded
	 * @var boolean
	 */
	protected $is_loaded = false;

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @since TBD
	 *
	 * @static
	 * @return Tribe__Tickets__Commerce__PayPal__Main
	 */
	public static function get_instance() {
		return tribe( 'tickets.commerce.paypal' );
	}

	/**
	 * Class constructor
	 *
	 * @since TBD
	 */
	public function __construct() {
		$main = Tribe__Tickets__Main::instance();

		/* Set up some parent's vars */
		$this->plugin_name = _x( 'Tribe Commerce', 'ticket provider', 'event-tickets' );
		$this->plugin_path = $main->plugin_path;
		$this->plugin_url  = $main->plugin_url;

		// mirror some properties from the class constants
		$this->attendee_event_key   = self::ATTENDEE_EVENT_KEY;
		$this->attendee_product_key = self::ATTENDEE_PRODUCT_KEY;
		$this->attendee_object      = self::ATTENDEE_OBJECT;

		parent::__construct();

		$this->bind_implementations();

		$this->tickets_view = tribe( 'tickets.commerce.paypal.view' );

		$this->register_resources();
		$this->hooks();

		$this->is_loaded = true;
	}

	/**
	 * Registers the implementations in the container
	 *
	 * @since TBD
	 */
	public function bind_implementations() {
		tribe_singleton( 'tickets.commerce.paypal.view', 'Tribe__Tickets__Commerce__PayPal__Tickets_View' );
		tribe_singleton( 'tickets.commerce.paypal.handler.ipn', 'Tribe__Tickets__Commerce__PayPal__Handler__IPN', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.handler.pdt', 'Tribe__Tickets__Commerce__PayPal__Handler__PDT', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.gateway', 'Tribe__Tickets__Commerce__PayPal__Gateway', array( 'hook', 'build_handler' ) );
		tribe_singleton( 'tickets.commerce.paypal.notices', 'Tribe__Tickets__Commerce__PayPal__Notices' );
		tribe_singleton( 'tickets.commerce.paypal.endpoints', 'Tribe__Tickets__Commerce__PayPal__Endpoints' );
		tribe_singleton( 'tickets.commerce.paypal.endpoints.templates.success', 'Tribe__Tickets__Commerce__PayPal__Endpoints__Success_Template' );
		tribe_singleton( 'tickets.commerce.paypal.orders.tabbed-view', 'Tribe__Tickets__Commerce__Orders_Tabbed_View' );
		tribe_singleton( 'tickets.commerce.paypal.orders.report', 'Tribe__Tickets__Commerce__PayPal__Orders__Report', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.orders.sales', 'Tribe__Tickets__Commerce__PayPal__Orders__Sales' );
		tribe_singleton( 'ticket.commerce.paypal.screen-options', 'Tribe__Tickets__Commerce__PayPal__Screen_Options', array( 'hook' ) );

		tribe()->tag( array(
			'tickets.commerce.paypal.shortcodes.tpp-success' => 'Tribe__Tickets__Commerce__PayPal__Shortcodes__Success',
		), 'tpp-shortcodes' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Shortcodes__Interface $shortcode */
		foreach ( tribe()->tagged( 'tpp-shortcodes' ) as $shortcode ) {
			add_shortcode( $shortcode->tag(), array( $shortcode, 'render' ) );
		}

		tribe( 'tickets.commerce.paypal.gateway' );
		tribe( 'tickets.commerce.paypal.orders.report' );
		tribe( 'ticket.commerce.paypal.screen-options' );
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

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_resources' ), 11 );
		add_action( 'trashed_post', array( $this, 'maybe_redirect_to_attendees_report' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_action( 'tpp_checkin', array( $this, 'purge_attendees_transient' ) );
		add_action( 'tpp_uncheckin', array( $this, 'purge_attendees_transient' ) );
		add_action( 'tribe_events_tickets_attendees_event_details_top', array( $this, 'setup_attendance_totals' ) );

		add_action( 'init', array( $this, 'init' ) );

		add_action( 'event_tickets_attendee_update', array( $this, 'update_attendee_data' ), 10, 3 );
		add_action( 'event_tickets_after_attendees_update', array( $this, 'maybe_send_tickets_after_status_change' ) );
		add_filter(
			'event_tickets_attendees_tpp_checkin_stati',
			array( $this, 'filter_event_tickets_attendees_tpp_checkin_stati' )
		);

		add_action( 'init', array( tribe( 'tickets.commerce.paypal.notices' ), 'hook' ) );
		add_action( 'tribe_tickets_attendees_page_inside', tribe_callback( 'tickets.commerce.paypal.orders.tabbed-view', 'render' ) );
		add_action( 'tribe_events_tickets_metabox_edit_advanced', array( $this, 'do_metabox_advanced_options' ), 10, 2 );
	}

	/**
	 * Hooked to the init action
	 *
	 * @since TBD
	 */
	public function init() {
		$this->register_types();
	}

	/**
	 * registers resources
	 *
	 * @since TBD
	 */
	public function register_resources() {
		$main = Tribe__Tickets__Main::instance();

		tribe_assets(
			$main,
			array(
				array(
					'event-tickets-tpp-css',
					'tpp.css',
				),
				array(
					'event-tickets-tpp-js',
					'tpp.js',
					array(
						'jquery',
						'jquery-ui-datepicker',
					),
				),
			),
			null,
			array(
				'localize' => array(
					'event-tickets-tpp-js',
					'tribe_tickets_tpp_strings',
					array(
						'attendee' => _x( 'Attendee %1$s', 'Attendee number', 'event-tickets' ),
					),
				),
			)
		);
	}

	/**
	 * Enqueue the plugin stylesheet(s).
	 *
	 * @since  TBD
	 */
	public function enqueue_resources() {
		$post_types = Tribe__Tickets__Main::instance()->post_types();

		if ( ! is_singular( $post_types ) ) {
			return;
		}

		wp_enqueue_style( 'event-tickets-tpp-css' );
		wp_enqueue_script( 'event-tickets-tpp-js' );

		// Check for override stylesheet
		$user_stylesheet_url = Tribe__Templates::locate_stylesheet( 'tribe-events/tickets/tpp.css' );

		// If override stylesheet exists, then enqueue it
		if ( $user_stylesheet_url ) {
			wp_enqueue_style( 'tribe-events-tickets-tpp-override-style', $user_stylesheet_url );
		}
	}

	/**
	 * Register our custom post type
	 *
	 * @since TBD
	 */
	public function register_types() {

		$ticket_post_args = array(
			'label'           => 'Tickets',
			'labels'          => array(
				'name'          => __( 'Tribe Commerce Tickets', 'event-tickets' ),
				'singular_name' => __( 'Tribe Commerce Ticket', 'event-tickets' ),
			),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => true,
		);

		$attendee_post_args = array(
			'label'           => 'Attendees',
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => true,
		);

		/**
		 * Filter the arguments that craft the ticket post type.
		 *
		 * @since TBD
		 *
		 * @see register_post_type
		 *
		 * @param array $ticket_post_args Post type arguments, passed to register_post_type()
		 */
		$ticket_post_args = apply_filters( 'tribe_tickets_register_ticket_post_type_args', $ticket_post_args );

		register_post_type( $this->ticket_object, $ticket_post_args );

		/**
		 * Filter the arguments that craft the attendee post type.
		 *
		 * @since TBD
		 *
		 * @see register_post_type
		 *
		 * @param array $attendee_post_args Post type arguments, passed to register_post_type()
		 */
		$attendee_post_args = apply_filters( 'tribe_tickets_register_attendee_post_type_args', $attendee_post_args );

		register_post_type( self::ATTENDEE_OBJECT, $attendee_post_args );
	}

	/**
	 * Adds Tribe Commerce attendance totals to the summary box of the attendance
	 * screen.
	 *
	 * Expects to fire during 'tribe_tickets_attendees_page_inside', ie
	 * before the attendee screen is rendered.
	 *
	 * @since TBD
	 */
	public function setup_attendance_totals() {
		$this->attendance_totals()->integrate_with_attendee_screen();
	}

	/**
	 * @since TBD
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Attendance_Totals
	 */
	public function attendance_totals() {
		if ( empty( $this->attendance_totals ) ) {
			$this->attendance_totals = new Tribe__Tickets__Commerce__PayPal__Attendance_Totals;
		}

		return $this->attendance_totals;
	}

	/**
	 * Update the PayPalTicket values for this user.
	 *
	 * Note that, within this method, $order_id refers to the attendee or ticket ID
	 * (it does not refer to an "order" in the sense of a transaction that may include
	 * multiple tickets, as is the case in some other methods for instance).
	 *
	 * @since TBD
	 *
	 * @param array $data
	 * @param int   $order_id
	 * @param int   $event_id
	 */
	public function update_attendee_data( $data, $order_id, $event_id ) {
		$user_id = get_current_user_id();

		$ticket_orders    = $this->tickets_view->get_post_ticket_attendees( $event_id, $user_id );
		$ticket_order_ids = wp_list_pluck( $ticket_orders, 'order_id' );

		// This makes sure we don't save attendees for orders that are not from this current user and event
		if ( ! in_array( $order_id, $ticket_order_ids ) ) {
			return;
		}

		$attendee = array();

		// Get the Attendee Data, it's important for testing
		foreach ( $ticket_orders as $test_attendee ) {
			if ( $order_id !== $test_attendee['order_id'] ) {
				continue;
			}

			$attendee = $test_attendee;
		}

		$attendee_email        = empty( $data['email'] ) ? null : sanitize_email( $data['email'] );
		$attendee_email        = is_email( $attendee_email ) ? $attendee_email : null;
		$attendee_full_name    = empty( $data['full_name'] ) ? null : sanitize_text_field( $data['full_name'] );
		$attendee_optout       = empty( $data['optout'] ) ? false : (bool) $data['optout'];

		$product_id  = $attendee['product_id'];

		update_post_meta( $order_id, $this->attendee_optout_key, (bool) $attendee_optout );

		if ( ! is_null( $attendee_full_name ) ) {
			update_post_meta( $order_id, $this->full_name, $attendee_full_name );
		}

		if ( ! is_null( $attendee_email ) ) {
			update_post_meta( $order_id, $this->email, $attendee_email );
		}
	}

	/**
	 * Triggers the sending of ticket emails after PayPal Ticket information is updated.
	 *
	 * This is useful if a user initially suggests they will not be attending
	 * an event (in which case we do not send tickets out) but where they
	 * incrementally amend the status of one or more of those tickets to
	 * attending, at which point we should send tickets out for any of those
	 * newly attending persons.
	 *
	 * @since TBD
	 *
	 * @param $event_id
	 */
	public function maybe_send_tickets_after_status_change( $event_id ) {
		$transaction_ids = array();

		foreach ( $this->get_event_attendees( $event_id ) as $attendee ) {
			$transaction = get_post_meta( $attendee[ 'attendee_id' ], $this->order_key, true );

			if ( ! empty( $transaction ) ) {
				$transaction_ids[ $transaction ] = $transaction;
			}
		}

		foreach ( $transaction_ids as $transaction ) {
			// This method takes care of intelligently sending out emails only when
			// required, for attendees that have not yet received their tickets
			$this->send_tickets_email( $transaction );
		}
	}

	/**
	 * Generate and store all the attendees information for a new order.
	 *
	 * @since TBD
	 */
	public function generate_tickets() {
		$transaction_data = tribe( 'tickets.commerce.paypal.gateway' )->get_transaction_data();

		if ( empty( $transaction_data ) || empty( $transaction_data['items'] ) ) {
			return;
		}

		$has_tickets = $post_id = false;

		/**
		 * PayPal Ticket specific action fired just before a PayPalTicket-driven attendee tickets for an order are generated
		 *
		 * @since TBD
		 *
		 * @param array $transaction_data PayPal payment data
		 */
		do_action( 'tribe_tickets_tpp_before_order_processing', $transaction_data );

		$order_id = $transaction_data['txn_id'];

		$custom      = Tribe__Tickets__Commerce__PayPal__Custom_Argument::decode( $transaction_data['custom'], true );
		$attendee_id = empty( $custom['user_id'] ) ? null : absint( $custom['user_id'] );

		$attendee_full_name = empty( $transaction_data['first_name'] ) && empty( $transaction_data['last_name'] )
			? ''
			: sanitize_text_field( "{$transaction_data['first_name']} {$transaction_data['last_name']}" );

		if ( empty( $attendee_id ) ) {
			$attendee_email = empty( $transaction_data['payer_email'] ) ? null : sanitize_email( $transaction_data['payer_email'] );
			$attendee_email = is_email( $attendee_email ) ? $attendee_email : null;
		} else {
			$attendee       = get_user_by( 'ID', $attendee_id );
			$attendee_email = $attendee->user_email;
			$user_full_name = trim( "{$attendee->first_name} {$attendee->last_name}" );
			if ( ! empty( $user_full_name ) ) {
				$attendee_full_name = $user_full_name;
			}
		}

		// @TODO: figure out how to handle optout
		$attendee_optout = empty( $transaction_data['optout'] ) ? false : (bool) $transaction_data['optout'];

		if ( ! $attendee_email || ! $attendee_full_name ) {
			$url = get_permalink( $post_id );
			$url = add_query_arg( 'tpp_error', 1, $url );
			wp_redirect( esc_url_raw( $url ) );
			tribe_exit();
		}

		// Iterate over each product
		foreach ( (array) $transaction_data['items'] as $item ) {
			$order_attendee_id = 0;

			if ( empty( $item['ticket'] ) ) {
				continue;
			}

			$ticket_type = $item['ticket'];
			$product_id  = $ticket_type->ID;

			// Get the event this tickets is for
			$post = $ticket_type->get_event();

			if ( empty( $post ) ) {
				continue;
			}

			$post_id = $post->ID;

			// if there were no PayPal tickets for the product added to the cart, continue
			if ( empty( $item['quantity'] ) ) {
				continue;
			}

			// get the PayPal status `decrease_stock_by` value
			$status_stock_size = 1;

			$ticket_qty = (int) $item['quantity'];

			// to avoid tickets from not being created on a status stock size of 0
			// let's take the status stock size into account and create a number of tickets
			// at least equal to the number of tickets the user requested
			$ticket_qty = $status_stock_size < 1 ? $ticket_qty : $status_stock_size * $ticket_qty;

			$qty = max( $ticket_qty, 0 );

			// Throw an error if Qty is bigger then Remaining
			if ( $ticket_type->managing_stock() && $qty > $ticket_type->remaining() ) {
				$url = add_query_arg( 'tpp_error', 2, get_permalink( $post_id ) );
				wp_redirect( esc_url_raw( $url ) );
				tribe_exit();
			}

			$has_tickets = true;

			/**
			 * PayPal specific action fired just before a PayPal-driven attendee ticket for an event is generated
			 *
			 * @since TBD
			 *
			 * @param int $post_id ID of event
			 * @param string $ticket_type Ticket Type object for the product
			 * @param array $data Parsed PayPal transaction data
			 */
			do_action( 'tribe_tickets_tpp_before_attendee_ticket_creation', $post_id, $ticket_type, $transaction_data );

			// Iterate over all the amount of tickets purchased (for this product)
			for ( $i = 0; $i < $qty; $i ++ ) {

				$attendee = array(
					'post_status' => 'publish',
					'post_title'  => $attendee_full_name . ' | ' . ( $i + 1 ),
					'post_type'   => $this->attendee_object,
					'ping_status' => 'closed',
				);

				// Insert individual ticket purchased
				$attendee_id = wp_insert_post( $attendee );

				if ( $status_stock_size > 0 ) {
					$sales = (int) get_post_meta( $product_id, 'total_sales', true );
					update_post_meta( $product_id, 'total_sales', ++ $sales );
				}

				$attendee_order_status = 'completed';

				update_post_meta( $attendee_id, $this->attendee_product_key, $product_id );
				update_post_meta( $attendee_id, $this->attendee_event_key, $post_id );
				update_post_meta( $attendee_id, $this->attendee_tpp_key, $attendee_order_status );
				update_post_meta( $attendee_id, $this->security_code, $this->generate_security_code( $order_id, $attendee_id ) );
				update_post_meta( $attendee_id, $this->order_key, $order_id );
				update_post_meta( $attendee_id, $this->attendee_optout_key, (bool) $attendee_optout );
				update_post_meta( $attendee_id, $this->full_name, $attendee_full_name );
				update_post_meta( $attendee_id, $this->email, $attendee_email );

				/**
				 * PayPal specific action fired when a PayPal-driven attendee ticket for an event is generated
				 *
				 * @since TBD
				 *
				 * @param int $attendee_id ID of attendee ticket
				 * @param int $post_id ID of event
				 * @param int $order_id PayPal order ID
				 * @param int $product_id PayPal product ID
				 */
				do_action( 'event_tickets_tpp_attendee_created', $attendee_id, $post_id, $order_id );

				/**
				 * Action fired when an PayPal attendee ticket is created
				 *
				 * @since TBD
				 *
				 * @param int $attendee_id ID of the attendee post
				 * @param int $post_id Event post ID
				 * @param int $product_id PayPal ticket post ID
				 * @param int $order_attendee_id Attendee # for order
				 */
				do_action( 'event_tickets_tpp_ticket_created', $attendee_id, $post_id, $product_id, $order_attendee_id );

				$this->record_attendee_user_id( $attendee_id );
				$order_attendee_id++;
			}

			/**
			 * Action fired when a PayPal has had attendee tickets generated for it
			 *
			 * @since TBD
			 *
			 * @param int $product_id PayPal ticket post ID
			 * @param int $order_id ID of the PayPal order
			 * @param int $qty Quantity ordered
			 */
			do_action( 'event_tickets_tpp_tickets_generated_for_product', $product_id, $order_id, $qty );


			// After Adding the Values we Update the Transient
			Tribe__Post_Transient::instance()->delete( $post_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
		}

		/**
		 * Fires when an PayPal attendee tickets have been generated.
		 *
		 * @since TBD
		 *
		 * @param int    $order_id              ID of the PayPal order
		 * @param int    $post_id               ID of the post the order was placed for
		 */
		do_action( 'event_tickets_tpp_tickets_generated', $order_id, $post_id );

		$send_mail_statuses = array( 'yes' );

		/**
		 * Filters whether a confirmation email should be sent or not for PayPal tickets.
		 *
		 * This applies to attendance and non attendance emails.
		 *
		 * @since TBD
		 *
		 * @param bool $send_mail Defaults to `true`.
		 */
		$send_mail = apply_filters( 'tribe_tickets_tpp_send_mail', true );

		if ( $send_mail && $has_tickets ) {
			$this->send_tickets_email( $order_id );
		}

		// Redirect to the same page to prevent double purchase on refresh
		if ( ! empty( $post_id ) ) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Endpoints $endpoints */
			$endpoints = tribe( 'tickets.commerce.paypal.endpoints' );
			$url       = $endpoints->success_url( $order_id );
			wp_redirect( esc_url_raw( $url ) );
			tribe_exit();
		}
	}

	/**
	 * Sends ticket email
	 *
	 * @param int $order_id Order post ID
	 */
	public function send_tickets_email( $order_id ) {
		$all_attendees = $this->get_attendees_by_id( $order_id );

		$to_send = array();

		if ( empty( $all_attendees ) ) {
			return;
		}

		// Look at each attendee and check if a ticket was sent: in each case where a ticket
		// has not yet been sent we should a) send the ticket out by email and b) record the
		// fact it was sent
		foreach ( $all_attendees as $single_attendee ) {
			// Only add those attendees/tickets that haven't already been sent
			if ( ! empty( $single_attendee[ 'ticket_sent' ] ) ) {
				continue;
			}

			$to_send[] = $single_attendee;
			update_post_meta( $single_attendee[ 'qr_ticket_id' ], $this->attendee_ticket_sent, true );
		}

		/**
		 * Controls the list of tickets which will be emailed out.
		 *
		 * @since TBD
		 *
		 * @param array $to_send        list of tickets to be sent out by email
		 * @param array $all_attendees  list of all attendees/tickets, including those already sent out
		 * @param int   $order_id
		 *
		 */
		$to_send = (array) apply_filters( 'tribe_tickets_tpp_tickets_to_send', $to_send, $all_attendees, $order_id );

		if ( empty( $to_send ) ) {
			return;
		}

		// For now all ticket holders in an order share the same email
		$to = $all_attendees['0']['holder_email'];

		if ( ! is_email( $to ) ) {
			return;
		}

		$content     = apply_filters( 'tribe_tpp_email_content', $this->generate_tickets_email_content( $to_send ) );
		$headers     = apply_filters( 'tribe_tpp_email_headers', array( 'Content-type: text/html' ) );
		$attachments = apply_filters( 'tribe_tpp_email_attachments', array() );
		$to          = apply_filters( 'tribe_tpp_email_recipient', $to );
		$subject     = apply_filters( 'tribe_tpp_email_subject',
			sprintf( __( 'Your tickets from %s', 'event-tickets' ), stripslashes_deep( html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) ) );

		wp_mail( $to, $subject, $content, $headers, $attachments );
	}

	/**
	 * Saves a ticket
	 *
	 * @since TBD
	 *
	 * @param int                           $post_id
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 * @param array                         $raw_data
	 *
	 * @return int The updated/created ticket post ID
	 */
	public function save_ticket( $post_id, $ticket, $raw_data = array() ) {
		// assume we are updating until we find out otherwise
		$save_type = 'update';

		if ( empty( $ticket->ID ) ) {
			$save_type = 'create';

			/* Create main product post */
			$args = array(
				'post_status'  => 'publish',
				'post_type'    => $this->ticket_object,
				'post_author'  => get_current_user_id(),
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name,
				'menu_order'   => tribe_get_request_var( 'menu_order', -1 ),
			);

			$ticket->ID = wp_insert_post( $args );

			// Relate event <---> ticket
			add_post_meta( $ticket->ID, $this->event_key, $post_id );

		} else {
			$args = array(
				'ID'           => $ticket->ID,
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name,
			);

			$ticket->ID = wp_update_post( $args );
		}

		if ( ! $ticket->ID ) {
			return false;
		}

		update_post_meta( $ticket->ID, '_price', $ticket->price );

		$ticket_data = Tribe__Utils__Array::get( $raw_data, 'tribe-ticket', array() );
		$this->update_capacity( $ticket, $ticket_data, $save_type );

		if ( isset( $ticket->start_date ) ) {
			update_post_meta( $ticket->ID, '_ticket_start_date', $ticket->start_date );
		} else {
			delete_post_meta( $ticket->ID, '_ticket_start_date' );
		}

		if ( isset( $ticket->end_date ) ) {
			update_post_meta( $ticket->ID, '_ticket_end_date', $ticket->end_date );
		} else {
			delete_post_meta( $ticket->ID, '_ticket_end_date' );
		}

		/**
		 * Toggle filter to allow skipping the automatic SKU generation.
		 *
		 * @param bool $should_default_ticket_sku
		 */
		$should_default_ticket_sku = apply_filters( 'tribe_tickets_should_default_ticket_sku', true );
		if ( $should_default_ticket_sku ) {
			// make sure the SKU is set to the correct value
			if ( ! empty( $raw_data['ticket_sku'] ) ) {
				$sku = $raw_data['ticket_sku'];
			} else {
				$post_author            = get_post( $ticket->ID )->post_author;
				$sku                    = "{$ticket->ID}-{$post_author}-" . sanitize_title( $ticket->name );
				$raw_data['ticket_sku'] = $sku;
			}
			update_post_meta( $ticket->ID, '_sku', $sku );
		}

		// Fetches all Ticket Form Datas
		$data = Tribe__Utils__Array::get( $raw_data, 'tribe-ticket', array() );

		// Fetch the Global stock Instance for this Event
		$event_stock = new Tribe__Tickets__Global_Stock( $post_id );

		// Only need to do this if we haven't already set one - they shouldn't be able to edit it from here otherwise
		if ( ! $event_stock->is_enabled() ) {
			if ( isset( $data['event_capacity'] ) ) {
				$data['event_capacity'] = trim( filter_var( $data['event_capacity'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH ) );

				// If empty we need to modify to -1
				if ( '' === $data['event_capacity'] ) {
					$data['event_capacity'] = -1;
				}

				// Makes sure it's an Int after this point
				$data['event_capacity'] = (int) $data['event_capacity'];

				// We need to update event post meta - if we've set a global stock
				$event_stock->enable();
				$event_stock->set_stock_level( $data['event_capacity'] );

				// Update Event capacity
				update_post_meta( $post_id, tribe( 'tickets.handler' )->key_capacity, $data['event_capacity'] );
			}
		} else {
			// If the Global Stock is configured we pull it from the Event
			$data['event_capacity'] = tribe_tickets_get_capacity( $post_id );
		}

		// Default Capacity will be 0
		$default_capacity = 0;
		$is_capacity_passed = true;

		// If we have Event Global stock we fetch that Stock
		if ( $event_stock->is_enabled() ) {
			$default_capacity = $data['event_capacity'];
		}

		// Fetch capacity field, if we don't have it use default (defined above)
		$data['capacity'] = trim( Tribe__Utils__Array::get( $data, 'capacity', $default_capacity ) );

		// If empty we need to modify to the default
		if ( '' !== $data['capacity'] ) {
			// Makes sure it's an Int after this point
			$data['capacity'] = (int) $data['capacity'];

			// The only available value lower than zero is -1 which is unlimited
			if ( 0 > $data['capacity'] ) {
				$data['capacity'] = -1;
			}

			$default_capacity = $data['capacity'];
		}

		// Fetch the stock if defined, otherwise use Capacity field
		$data['stock'] = trim( Tribe__Utils__Array::get( $data, 'stock', $default_capacity ) );

		// If empty we need to modify to what every capacity was
		if ( '' === $data['stock'] ) {
			$data['stock'] = $default_capacity;
		}

		// Makes sure it's an Int after this point
		$data['stock'] = (int) $data['stock'];

		if ( '' !== $data['mode'] ) {
			if ( 'update' === $save_type ) {
				$totals = tribe( 'tickets.handler' )->get_ticket_totals( $ticket->ID );
				$data['stock'] -= $totals['pending'] + $totals['sold'];
			}

			// In here is safe to check because we don't have unlimted = -1
			$status = ( 0 < $data['stock'] ) ? 'instock' : 'outofstock';

			update_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, $data['mode'] );
			update_post_meta( $ticket->ID, '_stock', $data['stock'] );
			update_post_meta( $ticket->ID, '_stock_status', $status );
			update_post_meta( $ticket->ID, '_backorders', 'no' );
			update_post_meta( $ticket->ID, '_manage_stock', 'yes' );

			// Prevent Ticket Capacity from going higher then Event Capacity
			if (
				$event_stock->is_enabled()
				&& Tribe__Tickets__Global_Stock::OWN_STOCK_MODE !== $data['mode']
				&& '' !== $data['capacity']
				&& $data['capacity'] > $data['event_capacity']
			) {
				$data['capacity'] = $data['event_capacity'];
			}
		} else {
			// Unlimited Tickets
			// Besides setting _manage_stock to "no" we should remove the associated stock fields if set previously
			update_post_meta( $ticket->ID, '_manage_stock', 'no' );
			delete_post_meta( $ticket->ID, '_stock_status' );
			delete_post_meta( $ticket->ID, '_stock' );
			delete_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP );
			delete_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE );

			// Set Capacity -1 when we don't have a stock mode, which means unlimited
			$data['capacity'] = -1;
		}

		if ( '' !== $data['capacity'] ) {
			// Update Ticket capacity
			update_post_meta( $ticket->ID, tribe( 'tickets.handler' )->key_capacity, $data['capacity'] );
		}

		// Default Purchase Limit
		if ( ! isset( $ticket->purchase_limit ) ) {
			$ticket->purchase_limit = '';
		}

		$ticket->purchase_limit = trim( Tribe__Utils__Array::get( $raw_data, 'ticket_purchase_limit', $ticket->purchase_limit ) );

		if ( '' !== $ticket->purchase_limit ) {
			update_post_meta( $ticket->ID, '_ticket_purchase_limit', absint( $ticket->purchase_limit ) );
		} else {
			delete_post_meta( $ticket->ID, '_ticket_purchase_limit' );
		}

		/**
		 * Generic action fired after saving a ticket (by type)
		 *
		 * @since TBD
		 *
		 * @param int                           $post_id  Post ID of post the ticket is tied to
		 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket that was just saved
		 * @param array                         $raw_data Ticket data
		 * @param string                        $class    Commerce engine class
		 */
		do_action( 'event_tickets_after_' . $save_type . '_ticket', $post_id, $ticket, $raw_data, __CLASS__ );

		/**
		 * Generic action fired after saving a ticket
		 *
		 * @since TBD
		 *
		 * @param int                           $post_id  Post ID of post the ticket is tied to
		 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket that was just saved
		 * @param array                         $raw_data Ticket data
		 * @param string                        $class    Commerce engine class
		 */
		do_action( 'event_tickets_after_save_ticket', $post_id, $ticket, $raw_data, __CLASS__ );

		return $ticket->ID;
	}

	/**
	 * Deletes a ticket
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return bool
	 */
	public function delete_ticket( $event_id, $ticket_id ) {
		// Ensure we know the event and product IDs (the event ID may not have been passed in)
		if ( empty( $event_id ) ) {
			$event_id = get_post_meta( $ticket_id, $this->attendee_event_key, true );
		}

		// Additional check (in case we were passed an invalid ticket ID and still can't determine the event)
		if ( empty( $event_id ) ) {
			return false;
		}

		$product_id = get_post_meta( $ticket_id, $this->attendee_product_key, true );

		// For attendees whose status ('going' or 'not going') for whom a stock adjustment is required?
		$attendee_status = get_post_meta( $ticket_id, $this->attendee_tpp_key, true );

		$sales = (int) get_post_meta( $product_id, 'total_sales', true );
		update_post_meta( $product_id, 'total_sales', $sales );

		// Store name so we can still show it in the attendee list
		$attendees      = $this->get_attendees_by_id( $event_id );
		$post_to_delete = get_post( $ticket_id );

		foreach ( (array) $attendees as $attendee ) {
			if ( $attendee['product_id'] == $ticket_id ) {
				update_post_meta( $attendee['attendee_id'], $this->deleted_product,
					esc_html( $post_to_delete->post_title ) );
			}
		}

		// Try to kill the actual ticket/attendee post
		$delete = wp_delete_post( $ticket_id, true );
		if ( is_wp_error( $delete ) ) {
			return false;
		}

		Tribe__Tickets__Attendance::instance( $event_id )->increment_deleted_attendees_count();
		do_action( 'tickets_tpp_ticket_deleted', $ticket_id, $event_id, $product_id );
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );

		return true;
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
		if ( $this->is_frontend_tickets_form_done ) {
			return $content;
		}

		$post = $GLOBALS['post'];

		// For recurring events (child instances only), default to loading tickets for the parent event
		if ( ! empty( $post->post_parent ) && function_exists( 'tribe_is_recurring_event' ) && tribe_is_recurring_event( $post->ID ) ) {
			$post = get_post( $post->post_parent );
		}

		$tickets = $this->get_tickets( $post->ID );

		if ( empty( $tickets ) ) {
			return;
		}

		Tribe__Tickets__Tickets::add_frontend_stock_data( $tickets );

		$ticket_sent = empty( $_GET['tpp_sent'] ) ? false : true;

		if ( $ticket_sent ) {
			$this->add_message( __( 'Your PayPal Ticket has been received! Check your email for your PayPal Ticket confirmation.', 'event-tickets' ), 'success' );
		}

		$ticket_error = empty( $_GET['tpp_error'] ) ? false : (int) $_GET['tpp_error'];

		if ( $ticket_error ) {
			$this->add_error_message( $ticket_error );
		}

		$must_login = ! is_user_logged_in() && $this->login_required();
		$can_login = true;

		include $this->getTemplateHierarchy( 'tickets/tpp' );

		// It's only done when it's included
		$this->is_frontend_tickets_form_done = true;
	}

	/**
	 * Indicates if we currently require users to be logged in before they can obtain
	 * tickets.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	protected function login_required() {
		$requirements = (array) tribe_get_option( 'ticket-authentication-requirements', array() );
		return in_array( 'event-tickets_all', $requirements, true );
	}

	/**
	 * Gets an individual ticket
	 *
	 * @since TBD
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return null|Tribe__Tickets__Ticket_Object
	 */
	public function get_ticket( $event_id, $ticket_id ) {
		$product = get_post( $ticket_id );

		if ( ! $product ) {
			return null;
		}

		$return = new Tribe__Tickets__Ticket_Object();

		$qty_sold = get_post_meta( $ticket_id, 'total_sales', true );

		$return->description    = $product->post_excerpt;
		$return->ID             = $ticket_id;
		$return->name           = $product->post_title;
		$return->price          = get_post_meta( $ticket_id, '_price', true );
		$return->provider_class = get_class( $this );
		$return->admin_link     = '';
		$return->start_date     = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$return->end_date       = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$return->sku            = get_post_meta( $ticket_id, 'sku', true );

		// If the quantity sold wasn't set, default to zero
		$qty_sold = $qty_sold ? $qty_sold : 0;

		// Ticket stock is a simple reflection of remaining inventory for this item...
		$stock = (int) get_post_meta( $ticket_id, '_stock', true );

		// If we don't have a stock value, then stock should be considered 'unlimited'
		if ( null === $stock ) {
			$stock = - 1;
		}

		$return->manage_stock( 'yes' === get_post_meta( $ticket_id, '_manage_stock', true ) );
		$return->stock( $stock );
		$return->global_stock_mode( get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true ) );
		$capped = get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true );

		if ( '' !== $capped ) {
			$return->global_stock_cap( $capped );
		}

		$return->qty_sold( $qty_sold );

		// review this when/if the Cancelled ticket status is supported in PayPal tickets
		$return->qty_cancelled( 0 );

		// review this when/if the Pending ticket status is supported in PayPal tickets
		$return->qty_pending( 0 );

		if ( empty( $return->purchase_limit ) && 0 !== (int) $return->purchase_limit ) {
			/**
			 * Filter the default purchase limit for the ticket
			 *
			 * @since TBD
			 *
			 * @param int
			 *
			 * @return int
			 */
			$return->purchase_limit = apply_filters( 'tribe_tickets_default_purchase_limit', 0 );
		}

		/**
		 * Use this Filter to change any information you want about this ticket
		 *
		 * @since TBD
		 *
		 * @param object $ticket
		 * @param int    $post_id
		 * @param int    $ticket_id
		 */
		$ticket = apply_filters( 'tribe_tickets_tpp_get_ticket', $return, $event_id, $ticket_id );

		return $return;
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

		// PayPal Ticket Orders are a unique hash
		if ( ! is_numeric( $post_id ) ) {
			$post_type = 'tpp_order_hash';
		}

		if ( ! $post_type ) {
			$post_type = get_post_type( $post_id );
		}

		switch ( $post_type ) {

			case $this->attendee_object :

				return $this->get_attendees_by_attendee_id( $post_id );

				break;

			case 'tpp_order_hash' :

				return $this->get_attendees_by_order_id( $post_id );

				break;
			default :

				return $this->get_attendees_by_post_id( $post_id );

				break;
		}

	}

	/**
	 * Get attendees by order id
	 *
	 * @since TBD
	 *
	 * @param $order_id
	 *
	 * @return array
	 */
	protected function get_attendees_by_order_id( $order_id ) {

		$attendees_query = new WP_Query( array(
			'posts_per_page' => - 1,
			'post_type'      => $this->attendee_object,
			'meta_key'       => $this->order_key,
			'meta_value'     => esc_attr( $order_id ),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		if ( ! $attendees_query->have_posts() ) {
			return array();
		}

		return $this->get_attendees( $attendees_query, $order_id );

	}

	/**
	 * Get all the attendees for post type. It returns an array with the
	 * following fields:
	 *
	 *     order_id
	 *     purchaser_name
	 *     purchaser_email
	 *     optout
	 *     ticket
	 *     attendee_id
	 *     security
	 *     product_id
	 *     check_in
	 *     provider
	 *
	 * @since TBD
	 *
	 * @param $attendees_query
	 * @param $post_id
	 *
	 * @return array
	 */
	protected function get_attendees( $attendees_query, $post_id ) {

		$attendees = array();

		foreach ( $attendees_query->posts as $attendee ) {
			$checkin      = get_post_meta( $attendee->ID, $this->checkin_key, true );
			$security     = get_post_meta( $attendee->ID, $this->security_code, true );
			$product_id   = get_post_meta( $attendee->ID, $this->attendee_product_key, true );
			$optout       = (bool) get_post_meta( $attendee->ID, $this->attendee_optout_key, true );
			$status       = get_post_meta( $attendee->ID, $this->attendee_tpp_key, true );
			$user_id      = get_post_meta( $attendee->ID, $this->attendee_user_id, true );
			$ticket_sent  = (bool) get_post_meta( $attendee->ID, $this->attendee_ticket_sent, true );

			if ( empty( $product_id ) ) {
				continue;
			}

			$product       = get_post( $product_id );
			$product_title = ( ! empty( $product ) ) ? $product->post_title : get_post_meta( $attendee->ID, $this->deleted_product, true ) . ' ' . __( '(deleted)', 'event-tickets' );

			$ticket_unique_id = get_post_meta( $attendee->ID, '_unique_id', true );
			$ticket_unique_id = $ticket_unique_id === '' ? $attendee->ID : $ticket_unique_id;

			$meta = '';
			if ( class_exists( 'Tribe__Tickets_Plus__Meta' ) ) {
				$meta = get_post_meta( $attendee->ID, Tribe__Tickets_Plus__Meta::META_KEY, true );

				// Process Meta to include value, slug, and label
				if ( ! empty( $meta ) ) {
					$meta = $this->process_attendee_meta( $product_id, $meta );
				}
			}

			$attendee_data = array_merge( $this->get_order_data( $attendee->ID ), array(
				'optout'             => $optout,
				'ticket'             => $product_title,
				'attendee_id'        => $attendee->ID,
				'security'           => $security,
				'product_id'         => $product_id,
				'check_in'           => $checkin,
				'order_status'       => $status,
				'user_id'            => $user_id,
				'ticket_sent'        => $ticket_sent,

				// Fields for Email Tickets
				'event_id'      => get_post_meta( $attendee->ID, $this->attendee_event_key, true ),
				'ticket_name'   => ! empty( $product ) ? $product->post_title : false,
				'holder_name'   => get_post_meta( $attendee->ID, $this->full_name, true ),
				'holder_email'  => get_post_meta( $attendee->ID, $this->email, true ),
				'order_id'      => $attendee->ID,
				'ticket_id'     => $ticket_unique_id,
				'qr_ticket_id'  => $attendee->ID,
				'security_code' => $security,

				// Attendee Meta
				'attendee_meta' => $meta,
			) );

			/**
			 * Allow users to filter the Attendee Data
			 *
			 * @since TBD
			 *
			 * @param array   $attendee_data An associative array with the Information of the Attendee
			 * @param string  $provider      What Provider is been used
			 * @param WP_Post $attendee      Attendee Object
			 * @param int     $post_id       Post ID
			 *
			 */
			$attendee_data = apply_filters( 'tribe_tickets_attendee_data', $attendee_data, 'tpp', $attendee, $post_id );

			$attendees[] = $attendee_data;
		}

		return $attendees;
	}

	/**
	 * Retreive only order related information
	 * Important: On PayPal Ticket the order is the Attendee Object
	 *
	 *     order_id
	 *     purchaser_name
	 *     purchaser_email
	 *     provider
	 *     provider_slug
	 *
	 * @since TBD
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function get_order_data( $order_id ) {
		$name       = get_post_meta( $order_id, $this->full_name, true );
		$email      = get_post_meta( $order_id, $this->email, true );

		$data = array(
			'order_id'        => $order_id,
			'purchaser_name'  => $name,
			'purchaser_email' => $email,
			'provider'        => __CLASS__,
			'provider_slug'   => 'tpp',
			'purchase_time'   => get_post_time( Tribe__Date_Utils::DBDATETIMEFORMAT, false, $order_id ),
		);

		/**
		 * Allow users to filter the Order Data
		 *
		 * @since TBD
		 *
		 * @param array  $data     An associative array with the Information of the Order
		 * @param string $provider What Provider is been used
		 * @param int    $order_id Order ID
		 *
		 */
		$data = apply_filters( 'tribe_tickets_order_data', $data, 'tpp', $order_id );

		return $data;
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
	 * Add the sku field in the admin's new/edit ticket metabox
	 *
	 * @since TBD
	 *
	 * @param $post_id int id of the event post
	 * @param int $ticket_id (null) id of the ticket
	 *
	 * @return void
	 */
	public function do_metabox_sku_options( $post_id, $ticket_id = null ) {
		$sku = '';
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $post_id, $this );

		if ( ! empty( $ticket_id ) ) {
			$ticket = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $ticket_id, $this );

			if ( ! empty( $ticket ) ) {
				$sku = get_post_meta( $ticket_id, '_sku', true );
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/tpp-metabox-sku.php';
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
	public function do_metabox_advanced_options( $post_id, $ticket_id ) {
		$provider = __CLASS__;

		echo '<div id="' . sanitize_html_class( $provider ) . '_advanced" class="tribe-dependent" data-depends="#' . sanitize_html_class( $provider ) . '_radio" data-condition-is-checked>';

		if ( ! tribe_is_frontend() ) {
			$this->do_metabox_sku_options( $post_id, $ticket_id );
		}

		/**
		 * Allows for the insertion of additional content into the ticket edit form - advanced section
		 *
		 * @since 4.6
		 *
		 * @param int Post ID
		 * @param string the provider class name
		 */
		do_action( 'tribe_events_tickets_metabox_edit_ajax_advanced', $post_id, $provider );

		echo '</div>';
	}

	/**
	 * Gets ticket messages
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_messages() {
		return self::$messages;
	}

	/**
	 * Adds a submission message
	 *
	 * @since TBD
	 *
	 * @param        $message
	 * @param string $type
	 */
	public function add_message( $message, $type = 'update' ) {
		$message = apply_filters( 'tribe_tpp_submission_message', $message, $type );
		self::$messages[] = (object) array( 'message' => $message, 'type' => $type );
	}

	/**
	 * If the post that was moved to the trash was an PayPal Ticket attendee post type, redirect to
	 * the Attendees Report rather than the PayPal Ticket attendees post list (because that's kind of
	 * confusing)
	 *
	 * @since TBD
	 *
	 * @param int $post_id WP_Post ID
	 */
	public function maybe_redirect_to_attendees_report( $post_id ) {
		$post = get_post( $post_id );

		if ( $this->attendee_object !== $post->post_type ) {
			return;
		}

		$args = array(
			'post_type' => 'tribe_events',
			'page' => Tribe__Tickets__Tickets_Handler::$attendees_slug,
			'event_id' => get_post_meta( $post_id, '_tribe_tpp_event', true ),
		);

		$url = add_query_arg( $args, admin_url( 'edit.php' ) );
		$url = esc_url_raw( $url );

		wp_redirect( $url );
		tribe_exit();
	}

	/**
	 * Filters the post_updated_messages array for attendees
	 *
	 * @since TBD
	 *
	 * @param array $messages Array of update messages
	 *
	 * @return array
	 */
	public function updated_messages( $messages ) {
		$ticket_post = get_post();

		if ( ! $ticket_post ) {
			return $messages;
		}

		$post_type = get_post_type( $ticket_post );

		if ( $this->attendee_object !== $post_type ) {
			return $messages;
		}

		$event = $this->get_event_for_ticket( $ticket_post );

		$attendees_report_url = add_query_arg(
			array(
				'post_type' => $event->post_type,
				'page' => Tribe__Tickets__Tickets_Handler::$attendees_slug,
				'event_id' => $event->ID,
			),
			admin_url( 'edit.php' )
		);

		$return_link = sprintf(
			esc_html__( 'Return to the %1$sAttendees Report%2$s.', 'event-tickets' ),
			"<a href='" . esc_url( $attendees_report_url ) . "'>",
			'</a>'
		);

		$messages[ $this->attendee_object ] = $messages['post'];
		$messages[ $this->attendee_object ][1] = sprintf(
			esc_html__( 'Post updated. %1$s', 'event-tickets' ),
			$return_link
		);
		$messages[ $this->attendee_object ][6] = sprintf(
			esc_html__( 'Post published. %1$s', 'event-tickets' ),
			$return_link
		);
		$messages[ $this->attendee_object ][8] = esc_html__( 'Post submitted.', 'event-tickets' );
		$messages[ $this->attendee_object ][9] = esc_html__( 'Post scheduled.', 'event-tickets' );
		$messages[ $this->attendee_object ][10] = esc_html__( 'Post draft updated.', 'event-tickets' );

		return $messages;
	}

	/**
	 * Set the tickets view
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Commerce__PayPal__Tickets_View $tickets_view
	 *
	 * @internal Used for dependency injection.
	 */
	public function set_tickets_view( Tribe__Tickets__Commerce__PayPal__Tickets_View $tickets_view ) {
		$this->tickets_view = $tickets_view;
	}

	/**
	 * Get's the product price html
	 *
	 * @since TBD
	 *
	 * @param int|object $product
	 * @param array $attendee
	 *
	 * @return string
	 */
	public function get_price_html( $product, $attendee = false ) {
		$product_id = $product;

		if ( $product instanceof WP_Post ) {
			$product_id = $product->ID;
		} elseif ( is_numeric( $product_id ) ) {
			$product = get_post( $product_id );
		} else {
			return '';
		}

		$price = get_post_meta( $product_id, '_price', true );
		$price = tribe_format_currency( $price, $product_id );

		$price_html = '<span class="tribe-tickets-price-amount amount">' . $price . '</span>';

		/**
		 * Allow filtering of the Price HTML
		 *
		 * @since TBD
		 *
		 * @param string $price_html
		 * @param mixed  $product
		 * @param mixed  $attendee
		 *
		 */
		return apply_filters( 'tribe_tickets_tpp_ticket_price_html', $price_html, $product, $attendee );
	}

	/**
	 * Filters the array of statuses that will mark an ticket attendee as eligible for check-in.
	 *
	 * @since TBD
	 *
	 * @param array $statuses An array of statuses that should mark an ticket attendee as
	 *                     available for check-in.
	 *
	 * @return array The original array plus the 'yes' status.
	 */
	public function filter_event_tickets_attendees_tpp_checkin_stati( array $statuses = array() ) {
		$statuses[] = 'completed';

		return array_unique( $statuses );
	}

	/**
	 * Gets the cart URL
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_cart_url() {
		return tribe( 'tickets.commerce.paypal.gateway' )->get_cart_url();
	}

	/**
	 * Gets a transaction URL
	 *
	 * @since TBD
	 *
	 * @param $transaction
	 *
	 * @return string
	 */
	public function get_transaction_url( $transaction ) {
		return tribe( 'tickets.commerce.paypal.gateway' )->get_transaction_url( $transaction );
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

		$constant_map = array(
			'attendee_event_key'   => $instance->attendee_event_key,
			'attendee_product_key' => $instance->attendee_product_key,
			'attendee_order_key'   => $instance->attendee_order_key,
			'attendee_optout_key'  => $instance->attendee_optout_key,
			'attendee_tpp_key'     => $instance->attendee_tpp_key,
			'event_key'            => $instance->event_key,
			'checkin_key'          => $instance->checkin_key,
			'order_key'            => $instance->order_key,
		);

		return Tribe__Utils__Array::get( $constant_map, $key, '' );
	}

	/**
	 * Returns the ID of the post associated with a PayPal order if any.
	 *
	 * @since TBD
	 *
	 * @param string $order The alphanumeric order identification string.
	 *
	 * @return int|false Either the ID of the post associated with the order or `false` on failure.
	 */
	public function get_post_id_from_order( $order ) {
		if ( empty( $order ) ) {
			return false;
		}

		global $wpdb;

		$post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT m2.meta_value
			FROM {$wpdb->postmeta} m1
			JOIN {$wpdb->postmeta} m2
			ON m1.post_id = m2.post_id
			WHERE m1.meta_key = %s
			AND m1.meta_value = %s
			AND m2.meta_key = %s",
			$this->order_key, $order, $this->attendee_event_key )
		);

		return empty( $post_id ) ? false : $post_id;
	}

	/**
	 * Returns a list of attendees for an order.
	 *
	 * @since TBD
	 *
	 * @param string $order The alphanumeric order identification string.
	 *
	 * @return array An array of WP_Post attendee objects.
	 */
	public function get_attendees_by_order( $order ) {
		if ( empty( $order ) ) {
			return false;
		}

		global $wpdb;

		$attendees = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT( m.post_id )
			FROM {$wpdb->postmeta} m
			WHERE m.meta_key = %s
			AND m.meta_value = %s",
			$this->order_key, $order )
		);

		return empty( $attendees ) ? array() : array_map( 'get_post', $attendees );
	}

	/**
	 * Whether the ticket is a PayPal one or not.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return bool
	 */
	public function is_paypal_ticket( Tribe__Tickets__Ticket_Object $ticket ) {
		return $ticket->provider_class === __CLASS__;
	}

	/**
	 * Returns a list of attendees grouped by order.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id
	 * @param array $ticket_ids An optional array of ticket IDs to limit the orders by.
	 *
	 * @return array An associative array in the format [ <order_number> => <order_details> ]
	 */
	public function get_orders_by_post_id( $post_id, array $ticket_ids = null ) {
		$attendees = $this->get_attendees_by_id( $post_id );

		if ( empty( $attendees ) ) {
			return array();
		}

		$statuses  = $this->get_order_statuses();
		$undefined = Tribe__Utils__Array::get( $statuses, 'undefined', reset( $statuses ) );
		/** @var Tribe__Tickets__Commerce__PayPal__Orders__Sales $sales */
		$sales = tribe( 'tickets.commerce.paypal.orders.sales' );

		$orders = array();

		foreach ( $attendees as $attendee ) {
			if ( ! empty( $ticket_ids ) && ! in_array( $attendee['product_id'], $ticket_ids ) ) {
				continue;
			}

			$order_number = get_post_meta( $attendee['attendee_id'], $this->order_key, true );

			if ( ! isset( $orders[ $order_number ] ) ) {
				$orders[ $order_number ] = array(
					'url'             => $this->get_transaction_url( $order_number ),
					'number'          => $order_number,
					'status'          => $attendee['order_status'],
					'status_label'    => Tribe__Utils__Array::get( $statuses, $attendee['order_status'], $undefined ),
					'purchaser_name'  => $attendee['purchaser_name'],
					'purchaser_email' => $attendee['purchaser_email'],
					'purchase_time'   => $attendee['purchase_time'],
					'attendees'       => array( $attendee ),
				);
			} else {
				$orders[ $order_number ]['attendees'][] = $attendee;
			}
		}

		foreach ( $orders as &$order ) {
			$order['line_total'] = $sales->get_revenue_for_attendees( $order['attendees'] );
		}

		return $orders;
	}

	/**
	 * Returns the list of PayPal tickets order stati.
	 *
	 * @since TBD
	 *
	 * @return array An associative array in the [ <slug> => <label> ] format.
	 */
	public function get_order_statuses() {
		$order_statuses = array(
			'undefined'     => _x( 'Undefined', 'a PayPal ticket order status', 'event-tickets' ),
			'completed'     => _x( 'Completed', 'a PayPal ticket order status', 'event-tickets' ),
			'not-completed' => _x( 'Not Completed', 'a PayPal ticket order status', 'event-tickets' ),
		);

		/**
		 * Filters the list of PayPal tickets order stati.
		 *
		 * @since TBD
		 *
		 * @param array $order_statuses
		 *
		 * @return array An associative array in the [ <slug> => <label> ] format.
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_order_stati', $order_statuses );
	}

	/**
	 * Returns all the tickets for an event
	 *
	 * @since TBD
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	protected function get_tickets( $post_id ) {
		$ticket_ids = $this->get_tickets_ids( $post_id );

		if ( ! $ticket_ids ) {
			return array();
		}

		$tickets = array();

		foreach ( $ticket_ids as $post ) {
			$tickets[] = $this->get_ticket( $post_id, $post );
		}

		return $tickets;
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
	 *
	 * @return mixed
	 */
	public function do_metabox_capacity_options( $post_id, $ticket_id ) {
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $post_id, $this );

		$url               = '';
		$stock             = '';
		$global_stock_mode = tribe( 'tickets.handler' )->get_default_capacity_mode();
		$global_stock_cap  = 0;
		$ticket_capacity   = null;
		$post_capacity     = null;

		$stock_object = new Tribe__Tickets__Global_Stock( $post_id );

		if ( $stock_object->is_enabled() ) {
			$post_capacity = tribe_tickets_get_capacity( $post_id );
		}

		/**
		 * Filter the default purchase limit for the ticket
		 *
		 * @param int
		 *
		 * @return int
		 */
		$purchase_limit = apply_filters( 'tribe_tickets_default_purchase_limit', 0 );

		if ( ! empty( $ticket_id ) ) {
			$ticket              = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $ticket_id, $this );

			if ( ! empty( $ticket ) ) {
				$stock             = $ticket->managing_stock() ? $ticket->stock() : '';
				$ticket_capacity   = tribe_tickets_get_capacity( $ticket->ID );
				$global_stock_mode = ( method_exists( $ticket, 'global_stock_mode' ) ) ? $ticket->global_stock_mode() : '';
				$global_stock_cap  = ( method_exists( $ticket, 'global_stock_cap' ) ) ? $ticket->global_stock_cap() : 0;

				if ( metadata_exists( 'post', $ticket->ID, '_ticket_purchase_limit' ) ) {
					$purchase_limit = get_post_meta( $ticket->ID, '_ticket_purchase_limit', true );
				}
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		$file = Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/tpp-metabox-capacity.php';

		/**
		 * Filters the absolute path to the file containing the metabox capacity HTML.
		 *
		 * @since TBD
		 *
		 * @param string     $file The absolute path to the file containing the metabox capacity HTML
		 * @param int|string $ticket_capacity
		 * @param int|string $post_capacity
		 */
		$file = apply_filters( 'tribe_tickets_tpp_metabox_capacity_file', $file, $ticket_capacity, $post_capacity );

		if ( file_exists( $file ) ) {
			include $file;
		}
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
		 * Allows the declaration of global stock support for WooCommerce tickets
		 * to be overridden.
		 *
		 * @param bool $enable_global_stock_support
		 */
		return (bool) apply_filters( 'tribe_tickets_tpp_enable_global_stock', true );
	}

	/**
	 * Gets the product price value
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post $product
	 *
	 * @return string
	 */
	public function get_price_value( $product ) {
		if ( ! $product instanceof WP_Post ) {
			$product = get_post( $product );
		}

		if ( ! $product instanceof WP_Post ) {
			return false;
		}

		return get_post_meta( $product->ID, '_price', true );
	}

	/**
	 * Displays a localized error message for the specified error code.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_error
	 */
	protected function add_error_message( $ticket_error ) {
		switch ( $ticket_error ) {
			case 3:
				$this->add_message( __( 'You should add at least one ticket.', 'event-tickets' ), 'error' );
				break;

			case 2:
				$this->add_message( __( 'You can\'t add more tickets than the total remaining tickets.', 'event-tickets' ), 'error' );
				break;

			case 1:
			default:
				$this->add_message( __( 'In order to purchase tickets, you must enter your name and a valid email address.', 'event-tickets' ),
					'error' );
				break;
		}
	}
}
