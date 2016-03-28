<?php

class Tribe__Tickets__RSVP extends Tribe__Tickets__Tickets {
	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 *
	 * @var string
	 */
	const ATTENDEE_OBJECT   = 'tribe_rsvp_attendees';

	/**
	 * Meta key that relates Attendees and Events.
	 *
	 * @var string
	 */
	const ATTENDEE_EVENT_KEY = '_tribe_rsvp_event';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 * @var string
	 */
	const ATTENDEE_PRODUCT_KEY = '_tribe_rsvp_product';

	/**
	 * Currently unused for this provider, but defined per the Tribe__Tickets__Tickets spec.
	 *
	 * @var string
	 */
	const ATTENDEE_ORDER_KEY = '';

	/**
	 *Name of the CPT that holds Tickets
	 *
	 * @var string
	 */
	public $ticket_object = 'tribe_rsvp_tickets';

	/**
	 * Meta key that relates Products and Events
	 * @var string
	 */
	public $event_key = '_tribe_rsvp_for_event';

	/**
	 * Meta key that stores if an attendee has checked in to an event
	 * @var string
	 */
	public $checkin_key = '_tribe_rsvp_checkedin';

	/**
	 * Meta key that ties attendees together by order
	 * @var string
	 */
	public $order_key = '_tribe_rsvp_order';

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 * @var string
	 */
	public $security_code = '_tribe_rsvp_security_code';

	/**
	 * Meta key that if this attendee wants to show on the attendee list
	 *
	 * @var string
	 */
	const ATTENDEE_OPTOUT_KEY = '_tribe_rsvp_attendee_optout';

	/**
	 * Meta key that holds the full name of the tickets RSVP "buyer"
	 *
	 * @var string
	 */
	public $full_name = '_tribe_rsvp_full_name';

	/**
	 * Meta key that holds the email of the tickets RSVP "buyer"
	 *
	 * @var string
	 */
	public $email = '_tribe_rsvp_email';

	/**
	 * Meta key that holds the name of a ticket to be used in reports if the Product is deleted
	 * @var string
	 */
	public $deleted_product = '_tribe_deleted_product_name';

	/**
	 * Messages for submission
	 */
	protected static $messages = array();

	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;

	/**
	 * Creates the instance of the class
	 *
	 * @static
	 * @return void
	 */
	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return Tribe__Tickets__RSVP
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$main = Tribe__Tickets__Main::instance();

		/* Set up some parent's vars */
		$this->pluginName = 'RSVP';
		$this->pluginPath = $main->plugin_path;
		$this->pluginUrl = $main->plugin_url;

		parent::__construct();

		add_action( 'init', array( $this, 'init' ) );

		/**
		 * Whenever we are dealing with Redirects we cannot do stuff on `init`
		 * Use: `template_redirect`
		 *
		 * Was running into an issue of `get_permalink( $event_id )` returning
		 * the wrong url because it was too early on the execution
		 */
		add_action( 'template_redirect', array( $this, 'generate_tickets' ) );
	}

	/**
	 * Registers all actions/filters
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_resources' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_resources' ), 11 );
		add_action( 'trashed_post', array( $this, 'maybe_redirect_to_attendees_report' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	/**
	 * Hooked to the init action
	 */
	public function init() {
		$this->register_types();
	}

	/**
	 * registers resources
	 */
	public function register_resources() {
		$main = Tribe__Tickets__Main::instance();

		$stylesheet_url = $main->plugin_url . 'src/resources/css/rsvp.css';
		$stylesheet_url = Tribe__Template_Factory::getMinFile( $stylesheet_url, true );

		// apply filters
		$stylesheet_url = apply_filters( 'tribe_tickets_rsvp_stylesheet_url', $stylesheet_url );

		wp_register_style(
			'event-tickets-rsvp',
			$stylesheet_url,
			array(),
			apply_filters( 'tribe_tickets_rsvp_css_version', Tribe__Tickets__Main::VERSION )
		);

		$js_url = $main->plugin_url . 'src/resources/js/rsvp.js';
		$js_url = Tribe__Template_Factory::getMinFile( $js_url, true );
		$js_url = apply_filters( 'tribe_tickets_rsvp_js_url', $js_url );

		wp_register_script(
			'event-tickets-rsvp',
			$js_url,
			array( 'jquery', 'jquery-ui-datepicker' ),
			apply_filters( 'tribe_tickets_rsvp_js_version', Tribe__Tickets__Main::VERSION ),
			true
		);

		wp_localize_script( 'event-tickets-rsvp', 'tribe_tickets_rsvp_strings', array(
			'attendee' => _x( 'Attendee %1$s', 'Attendee number', 'event-tickets' ),
		) );
	}

	/**
	 * Enqueue the plugin stylesheet(s).
	 *
	 * @author caseypicker
	 * @since  3.9
	 * @return void
	 */
	public function enqueue_resources() {
		$post_types = Tribe__Tickets__Main::instance()->post_types();

		if ( ! is_singular( $post_types ) ) {
			return;
		}

		wp_enqueue_style( 'event-tickets-rsvp' );
		wp_enqueue_script( 'event-tickets-rsvp' );

		// Check for override stylesheet
		$user_stylesheet_url = Tribe__Templates::locate_stylesheet( 'tribe-events/tickets/rsvp.css' );

		// If override stylesheet exists, then enqueue it
		if ( $user_stylesheet_url ) {
			wp_enqueue_style( 'tribe-events-tickets-rsvp-override-style', $user_stylesheet_url );
		}
	}

	/**
	 * Register our custom post type
	 */
	public function register_types() {

		register_post_type( $this->ticket_object, array(
			'label'           => 'Tickets',
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => true,
		) );


		register_post_type( self::ATTENDEE_OBJECT, array(
			'label'           => 'Attendees',
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => true,
		) );
	}

	/**
	 * Generate and store all the attendees information for a new order.
	 */
	public function generate_tickets( ) {
		if ( empty( $_POST['tickets_process'] ) || empty( $_POST['attendee'] ) || empty( $_POST['product_id'] ) ) {
			return;
		}

		$has_tickets = $event_id = false;

		$order_id = md5( time() . rand() );

		$attendee_email = empty( $_POST['attendee']['email'] ) ? null : sanitize_email( $_POST['attendee']['email'] );
		$attendee_email = is_email( $attendee_email ) ? $attendee_email : null;
		$attendee_full_name = empty( $_POST['attendee']['full_name'] ) ? null : sanitize_text_field( $_POST['attendee']['full_name'] );
		$attendee_optout = empty( $_POST['attendee']['optout'] ) ? false : (bool) $_POST['attendee']['optout'];

		if ( ! $attendee_email || ! $attendee_full_name ) {
			$url = get_permalink( $event_id );
			$url = add_query_arg( 'rsvp_error', 1, $url );
			wp_redirect( esc_url_raw( $url ) );
			die;
		}

		// Iterate over each product
		foreach ( (array) $_POST['product_id'] as $product_id ) {
			$order_attendee_id = 0;

			// Get the event this tickets is for
			$event_id = get_post_meta( $product_id, $this->event_key, true );

			if ( empty( $event_id ) ) {
				continue;
			}

			$ticket = $this->get_ticket( $event_id, $product_id );

			// if there were no RSVP tickets for the product added to the cart, continue
			if ( empty( $_POST[ "quantity_{$product_id}" ] ) ) {
				continue;
			}

			$qty = max( intval( $_POST[ "quantity_{$product_id}" ] ), 0 );

			// Throw an error if Qty is bigger then Remaining
			if ( $ticket->managing_stock() && $qty > $ticket->remaining() ) {
				$url = add_query_arg( 'rsvp_error', 2, get_permalink( $event_id ) );
				wp_redirect( esc_url_raw( $url ) );
				die;
			}

			$has_tickets = true;

			// Iterate over all the amount of tickets purchased (for this product)
			for ( $i = 0; $i < $qty; $i ++ ) {

				$attendee = array(
					'post_status' => 'publish',
					'post_title'  => $attendee_full_name . ' | ' . ( $i + 1 ),
					'post_type'   => self::ATTENDEE_OBJECT,
					'ping_status' => 'closed',
				);

				// Insert individual ticket purchased
				$attendee_id = wp_insert_post( $attendee );

				$sales = (int) get_post_meta( $product_id, 'total_sales', true );
				update_post_meta( $product_id, 'total_sales', ++ $sales );

				update_post_meta( $attendee_id, self::ATTENDEE_PRODUCT_KEY, $product_id );
				update_post_meta( $attendee_id, self::ATTENDEE_EVENT_KEY, $event_id );
				update_post_meta( $attendee_id, $this->security_code, $this->generate_security_code( $attendee_id ) );
				update_post_meta( $attendee_id, $this->order_key, $order_id );
				update_post_meta( $attendee_id, self::ATTENDEE_OPTOUT_KEY, (bool) $attendee_optout );
				update_post_meta( $attendee_id, $this->full_name, $attendee_full_name );
				update_post_meta( $attendee_id, $this->email, $attendee_email );

				/**
				 * Action fired when an RSVP attendee ticket is created
				 *
				 * @var $attendee_id ID of the attendee post
				 * @var $event_id Event post ID
				 * @var $product_id RSVP ticket post ID
				 * @var $order_attendee_id Attendee # for order
				 */
				do_action( 'event_tickets_rsvp_ticket_created', $attendee_id, $event_id, $product_id, $order_attendee_id );

				$order_attendee_id++;
			}

			/**
			 * Action fired when an RSVP has had attendee tickets generated for it
			 *
			 * @var $product_id RSVP ticket post ID
			 * @var $order_id ID of the RSVP order
			 * @var $qty Quantity ordered
			 */
			do_action( 'event_tickets_rsvp_tickets_generated_for_product', $product_id, $order_id, $qty );
		}

		/**
		 * Action fired when an RSVP attendee tickets have been generated
		 *
		 * @var $order_id ID of the RSVP order
		 */
		do_action( 'event_tickets_rsvp_tickets_generated', $order_id );

		if ( $has_tickets ) {
			$this->send_tickets_email( $order_id ) ;
		}

		// Redirect to the same page to prevent double purchase on refresh
		if ( ! empty( $event_id ) ) {
			$url = get_permalink( $event_id );
			$url = add_query_arg( 'rsvp_sent', 1, $url );
			wp_redirect( esc_url_raw( $url ) );
			die;
		}
	}

	public function send_tickets_email( $order_id ) {
		$attendees = $this->get_attendees_by_transaction( $order_id );

		if ( empty( $attendees ) ) {
			return;
		}

		// For now all ticket holders in an order share the same email
		$to = $attendees['0']['holder_email'];

		if ( ! is_email( $to ) ) {
			return;
		}

		$content     = apply_filters( 'tribe_rsvp_email_content', $this->generate_tickets_email_content( $attendees ) );
		$headers     = apply_filters( 'tribe_rsvp_email_headers', array( 'Content-type: text/html' ) );
		$attachments = apply_filters( 'tribe_rsvp_email_attachments', array() );
		$to          = apply_filters( 'tribe_rsvp_email_recipient', $to );
		$subject     = apply_filters( 'tribe_rsvp_email_subject',
			sprintf( __( 'Your tickets from %s', 'event-tickets' ), get_bloginfo( 'name' ) ) );

		wp_mail( $to, $subject, $content, $headers, $attachments );
	}

	protected function get_attendees_by_transaction( $order_id ) {
		$attendees = array();
		$query     = new WP_Query( array(
			'post_type'      => self::ATTENDEE_OBJECT,
			'meta_key'       => $this->order_key,
			'meta_value'     => $order_id,
			'posts_per_page' => - 1,
		) );

		foreach ( $query->posts as $post ) {
			$product = get_post( get_post_meta( $post->ID, self::ATTENDEE_PRODUCT_KEY, true ) );

			$attendees[] = array(
				'event_id'      => get_post_meta( $post->ID, self::ATTENDEE_EVENT_KEY, true ),
				'product_id'    => $product->ID,
				'ticket_name'   => $product->post_title,
				'holder_name'   => get_post_meta( $post->ID, $this->full_name, true ),
				'holder_email'  => get_post_meta( $post->ID, $this->email, true ),
				'order_id'      => $order_id,
				'ticket_id'     => $post->ID,
				'security_code' => get_post_meta( $post->ID, $this->security_code, true ),
				'optout'        => (bool) get_post_meta( $post->ID, self::ATTENDEE_OPTOUT_KEY, true ),
			);
		}

		return $attendees;
	}

	/**
	 * Generates the validation code that will be printed in the ticket.
	 * It purpose is to be used to validate the ticket at the door of an event.
	 *
	 * @param int $attendee_id
	 *
	 * @return string
	 */
	private function generate_security_code( $attendee_id ) {
		return substr( md5( rand() . '_' . $attendee_id ), 0, 10 );
	}

	/**
	 * Saves a given ticket (WooCommerce product)
	 *
	 * @param int                                   $event_id
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 * @param array                                 $raw_data
	 *
	 * @return bool
	 */
	public function save_ticket( $event_id, $ticket, $raw_data = array() ) {
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
			);

			$ticket->ID = wp_insert_post( $args );

			// Relate event <---> ticket
			add_post_meta( $ticket->ID, $this->event_key, $event_id );

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

		if ( trim( $raw_data['ticket_rsvp_stock'] ) !== '' ) {
			$stock = (int) $raw_data['ticket_rsvp_stock'];
			update_post_meta( $ticket->ID, '_stock', $stock );
			update_post_meta( $ticket->ID, '_manage_stock', 'yes' );
		} else {
			delete_post_meta( $ticket->ID, '_stock_status' );
			update_post_meta( $ticket->ID, '_manage_stock', 'no' );
		}

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
		 * Generic action fired after saving a ticket (by type)
		 *
		 * @var int Post ID of post the ticket is tied to
		 * @var Tribe__Tickets__Ticket_Object Ticket that was just saved
		 * @var array Ticket data
		 * @var string Commerce engine class
		 */
		do_action( 'event_tickets_after_' . $save_type . '_ticket', $event_id, $ticket, $raw_data, __CLASS__ );

		/**
		 * Generic action fired after saving a ticket
		 *
		 * @var int Post ID of post the ticket is tied to
		 * @var Tribe__Tickets__Ticket_Object Ticket that was just saved
		 * @var array Ticket data
		 * @var string Commerce engine class
		 */
		do_action( 'event_tickets_after_save_ticket', $event_id, $ticket, $raw_data, __CLASS__ );

		return true;
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
			$event_id = get_post_meta( $ticket_id, self::ATTENDEE_EVENT_KEY, true );
		}
		$product_id = get_post_meta( $ticket_id, self::ATTENDEE_PRODUCT_KEY, true );

		// Decrement the sales figure
		$sales = (int) get_post_meta( $product_id, 'total_sales', true );
		update_post_meta( $product_id, 'total_sales', -- $sales );

		//Store name so we can still show it in the attendee list
		$attendees      = $this->get_attendees( $event_id );
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

		do_action( 'tickets_rsvp_ticket_deleted', $ticket_id, $event_id, $product_id );

		return true;
	}

	/**
	 * Returns all the tickets for an event
	 *
	 * @param int $event_id
	 *
	 * @return array
	 */
	protected function get_tickets( $event_id ) {
		$ticket_ids = $this->get_tickets_ids( $event_id );

		if ( ! $ticket_ids ) {
			return array();
		}

		$tickets = array();

		foreach ( $ticket_ids as $post ) {
			$tickets[] = $this->get_ticket( $event_id, $post );
		}

		return $tickets;
	}

	/**
	 * Shows the tickets form in the front end
	 *
	 * @param $content
	 *
	 * @return void
	 */
	public function front_end_tickets_form( $content ) {
		static $done;

		if ( $done ) {
			return;
		}

		$done = true;

		$post = $GLOBALS['post'];

		if ( ! empty( $post->post_parent ) ) {
			$post = get_post( $post->post_parent );
		}

		$tickets = $this->get_tickets( $post->ID );

		if ( empty( $tickets ) ) {
			return;
		}

		$rsvp_sent = empty( $_GET['rsvp_sent'] ) ? false : true;
		$rsvp_error = empty( $_GET['rsvp_error'] ) ? false : intval( $_GET['rsvp_error'] );

		if ( $rsvp_sent ) {
			$this->add_message( __( 'Your RSVP has been received! Check your email for your RSVP confirmation.', 'event-tickets' ), 'success' );
		}

		if ( $rsvp_error ) {
			switch ( $rsvp_error ) {
				case 2:
					$this->add_message( __( 'You can\'t RSVP more than the total remaining tickets.', 'event-tickets' ), 'error' );
					break;

				case 1:
				default:
					$this->add_message( __( 'In order to RSVP, you must enter your name and a valid email address.', 'event-tickets' ), 'error' );
					break;
			}
		}

		include $this->getTemplateHierarchy( 'tickets/rsvp' );
	}

	/**
	 * Gets an individual ticket
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
		$qty    = (int) get_post_meta( $ticket_id, 'total_sales', true );

		$return->description    = $product->post_excerpt;
		$return->ID             = $ticket_id;
		$return->name           = $product->post_title;
		$return->price          = get_post_meta( $ticket_id, '_price', true );
		$return->provider_class = get_class( $this );
		$return->admin_link     = '';
		$return->start_date     = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$return->end_date       = get_post_meta( $ticket_id, '_ticket_end_date', true );

		$return->manage_stock( 'yes' === get_post_meta( $ticket_id, '_manage_stock', true ) );
		$return->stock( get_post_meta( $ticket_id, '_stock', true ) - $qty );
		$return->qty_sold( $qty );

		return $return;
	}

	/**
	 * Accepts a reference to a product (either an object or a numeric ID) and
	 * tests to see if it functions as a ticket: if so, the corresponding event
	 * object is returned. If not, boolean false is returned.
	 *
	 * @param $ticket_product
	 *
	 * @return bool|WP_Post
	 */
	public function get_event_for_ticket( $ticket_product ) {
		if ( is_object( $ticket_product ) && isset( $ticket_product->ID ) ) {
			$ticket_product = $ticket_product->ID;
		}

		if ( null === ( $product = get_post( $ticket_product ) ) ) {
			return false;
		}

		$event_id = get_post_meta( $ticket_product, $this->event_key, true );

		if ( ! $event_id && '' === ( $event_id = get_post_meta( $ticket_product, self::ATTENDEE_EVENT_KEY, true ) ) ) {
			return false;
		}

		if ( in_array( get_post_type( $event_id ), Tribe__Tickets__Main::instance()->post_types() ) ) {
			return get_post( $event_id );
		}

		return false;
	}

	/**
	 * Get all the attendees for an event. It returns an array with the
	 * following fields:
	 *
	 *     order_id
	 *     order_status
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
	 * @param $event_id
	 *
	 * @return array
	 */
	protected function get_attendees( $event_id ) {
		$attendees_query = new WP_Query( array(
			'posts_per_page' => - 1,
			'post_type'      => self::ATTENDEE_OBJECT,
			'meta_key'       => self::ATTENDEE_EVENT_KEY,
			'meta_value'     => $event_id,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		) );

		if ( ! $attendees_query->have_posts() ) {
			return array();
		}

		$attendees = array();

		foreach ( $attendees_query->posts as $attendee ) {
			$checkin    = get_post_meta( $attendee->ID, $this->checkin_key, true );
			$security   = get_post_meta( $attendee->ID, $this->security_code, true );
			$product_id = get_post_meta( $attendee->ID, self::ATTENDEE_PRODUCT_KEY, true );
			$optout     = (bool) get_post_meta( $attendee->ID, self::ATTENDEE_OPTOUT_KEY, true );

			if ( empty( $product_id ) ) {
				continue;
			}

			$product       = get_post( $product_id );
			$product_title = ( ! empty( $product ) ) ? $product->post_title : get_post_meta( $attendee->ID, $this->deleted_product,
					true ) . ' ' . __( '(deleted)', 'event-tickets' );

			$attendee_data = array_merge(
				$this->get_order_data( $attendee->ID ),
				array(
					'optout'          => $optout,
					'ticket'          => $product_title,
					'attendee_id'     => $attendee->ID,
					'security'        => $security,
					'product_id'      => $product_id,
					'check_in'        => $checkin,
				)
			);

			/**
			 * Allow users to filter the Attendee Data
			 *
			 * @var array An associative array with the Information of the Attendee
			 * @var string What Provider is been used
			 * @var WP_Post Attendee Object
			 * @var int Event ID
			 *
			 */
			$attendee_data = apply_filters( 'tribe_tickets_attendee_data', $attendee_data, 'rsvp', $attendee, $event_id );

			$attendees[] = $attendee_data;
		}

		return $attendees;
	}

	/**
	 * Retreive only order related information
	 * Important: On RSVP the order is the Attendee Object
	 *
	 *     order_id
	 *     purchaser_name
	 *     purchaser_email
	 *     provider
	 *     provider_slug
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
			'provider_slug'   => 'rsvp',
		);

		/**
		 * Allow users to filter the Order Data
		 *
		 * @var array An associative array with the Information of the Order
		 * @var string What Provider is been used
		 * @var int Order ID
		 *
		 */
		$data = apply_filters( 'tribe_tickets_order_data', $data, 'rsvp', $order_id );

		return $data;
	}


	/**
	 * Marks an attendee as checked in for an event
	 *
	 * Because we must still support our legacy ticket plugins, we cannot change the abstract
	 * checkin() method's signature. However, the QR checkin process needs to move forward
	 * so we get around that problem by leveraging func_get_arg() to pass a second argument.
	 *
	 * It is hacky, but we'll aim to resolve this issue when we end-of-life our legacy ticket plugins
	 * OR write around it in a future major release
	 *
	 * @param $attendee_id
	 * @param $qr true if from QR checkin process (NOTE: this is a param-less parameter for backward compatibility)
	 *
	 * @return bool
	 */
	public function checkin( $attendee_id ) {
		$qr = null;

		update_post_meta( $attendee_id, $this->checkin_key, 1 );

		if ( func_num_args() > 1 && $qr = func_get_arg( 1 ) ) {
			update_post_meta( $attendee_id, '_tribe_qr_status', 1 );
		}

		/**
		 * Fires a checkin action
		 *
		 * @var int $attendee_id
		 * @var bool|null $qr
		 */
		do_action( 'rsvp_checkin', $attendee_id, $qr );

		return true;
	}

	/**
	 * Marks an attendee as not checked in for an event
	 *
	 * @param $attendee_id
	 *
	 * @return bool
	 */
	public function uncheckin( $attendee_id ) {
		delete_post_meta( $attendee_id, $this->checkin_key );
		delete_post_meta( $attendee_id, '_tribe_qr_status' );
		do_action( 'rsvp_uncheckin', $attendee_id );

		return true;
	}

	/**
	 * Links to sales report for all tickets for this event.
	 *
	 * @param $event_id
	 *
	 * @return string
	 */
	public function get_event_reports_link( $event_id ) {
		return '';
	}

	/**
	 * Links to the sales report for this product.
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return string
	 */
	public function get_ticket_reports_link( $event_id, $ticket_id ) {
		return '';
	}

	public function get_tickets_ids( $event_id ) {
		if ( is_object( $event_id ) ) {
			$event_id = $event_id->ID;
		}

		$query = new WP_Query( array(
			'post_type'      => $this->ticket_object,
			'meta_key'       => $this->event_key,
			'meta_value'     => $event_id,
			'meta_compare'   => '=',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'post_status'    => 'publish',
		) );

		return $query->posts;
	}

	/**
	 * Renders the advanced fields in the new/edit ticket form.
	 * Using the method, providers can add as many fields as
	 * they want, specific to their implementation.
	 *
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return mixed
	 */
	public function do_metabox_advanced_options( $event_id, $ticket_id ) {


		$stock = '';

		if ( ! empty( $ticket_id ) ) {
			$ticket = $this->get_ticket( $event_id, $ticket_id );
			if ( ! empty( $ticket ) ) {
				$stock = $ticket->original_stock();
			}
		}

		include Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/rsvp-metabox-advanced.php';
	}

	public function get_messages() {
		return self::$messages;
	}

	public function add_message( $message, $type = 'update' ) {
		$message = apply_filters( 'tribe_rsvp_submission_message', $message, $type );
		self::$messages[] = (object) array( 'message' => $message, 'type' => $type );
	}

	/**
	 * If the post that was moved to the trash was an RSVP attendee post type, redirect to
	 * the Attendees Report rather than the RSVP attendees post list (because that's kind of
	 * confusing)
	 *
	 * @param int $post_id WP_Post ID
	 */
	public function maybe_redirect_to_attendees_report( $post_id ) {
		$post = get_post( $post_id );

		if ( self::ATTENDEE_OBJECT !== $post->post_type ) {
			return;
		}

		$args = array(
			'post_type' => 'tribe_events',
			'page' => Tribe__Tickets__Tickets_Handler::$attendees_slug,
			'event_id' => get_post_meta( $post_id, '_tribe_rsvp_event', true ),
		);

		$url = add_query_arg( $args, admin_url( 'edit.php' ) );
		$url = esc_url_raw( $url );

		wp_redirect( $url );
		die;
	}

	/**
	 * Filters the post_updated_messages array for attendees
	 *
	 * @param array $messages Array of update messages
	 */
	public function updated_messages( $messages ) {
		$ticket_post = get_post();

		if ( ! $ticket_post ) {
			return $messages;
		}

		$post_type = get_post_type( $ticket_post );

		if ( self::ATTENDEE_OBJECT !== $post_type ) {
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

		$messages[ self::ATTENDEE_OBJECT ] = $messages['post'];
		$messages[ self::ATTENDEE_OBJECT ][1] = sprintf(
			esc_html__( 'Post updated. %1$s', 'event-tickets' ),
			$return_link
		);
		$messages[ self::ATTENDEE_OBJECT ][6] = sprintf(
			esc_html__( 'Post published. %1$s', 'event-tickets' ),
			$return_link
		);
		$messages[ self::ATTENDEE_OBJECT ][8] = esc_html__( 'Post submitted.', 'event-tickets' );
		$messages[ self::ATTENDEE_OBJECT ][9] = esc_html__( 'Post scheduled.', 'event-tickets' );
		$messages[ self::ATTENDEE_OBJECT ][10] = esc_html__( 'Post draft updated.', 'event-tickets' );

		return $messages;
	}
}
