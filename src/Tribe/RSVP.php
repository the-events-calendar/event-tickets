<?php

class Tribe__Tickets__RSVP extends Tribe__Tickets__Tickets {

	/**
	 * Name of the CPT that holds Attendees (tickets holders)
	 * @var string
	 */
	public $attendee_object = 'tribe_rsvp_attendees';

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
	 * Meta key that relates Attendees and Products
	 * @var string
	 */
	public $atendee_product_key = '_tribe_rsvp_product';

	/**
	 * Meta key that ties attendees together by order
	 * @var string
	 */
	public $order_key = '_tribe_rsvp_order';
	/**
	 * Meta key that relates Attendees and Events
	 * @var string
	 */
	public $atendee_event_key = '_tribe_rsvp_event';

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 * @var string
	 */
	public $security_code = '_tribe_rsvp_security_code';

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
	 * @return Tribe__Tickets__Woo__Main
	 */
	public static function get_instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
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

		$this->hooks();
	}

	/**
	 * Registers all actions/filters
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_resources' ), 11 );
	}

	/**
	 * Hooked to the init action
	 */
	public function init() {
		$this->register_resources();
		$this->register_types();
		$this->generate_tickets();
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
			'tribe-tickets-rsvp',
			$stylesheet_url,
			array(),
			apply_filters( 'tribe_tickets_rsvp_css_version', Tribe__Tickets__Main::VERSION )
		);

		$js_url = $main->plugin_url . 'src/resources/js/rsvp.js';
		$js_url = Tribe__Template_Factory::getMinFile( $js_url, true );
		$js_url = apply_filters( 'tribe_tickets_rsvp_js_url', $js_url );

		wp_register_script(
			'tribe-tickets-rsvp',
			$js_url,
			array( 'jquery', 'jquery-ui-datepicker' ),
			apply_filters( 'tribe_tickets_rsvp_js_version', Tribe__Tickets__Main::VERSION ),
			true
		);

		wp_localize_script( 'tribe-tickets-rsvp', 'tribe_tickets_rsvp_strings', array(
			'attendee' => _x( 'Attendee %1$s', 'Attendee number', 'tribe-tickets' ),
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

		wp_enqueue_style( 'tribe-tickets-rsvp' );
		wp_enqueue_script( 'tribe-tickets-rsvp' );

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


		register_post_type( $this->attendee_object, array(
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

		if ( ! $attendee_email || ! $attendee_full_name ) {
			$url = get_permalink( $event_id );
			$url = add_query_arg( 'rsvp_error', 1, $url );
			wp_redirect( esc_url_raw( $url ) );
			die;
		}

		// Iterate over each product
		foreach ( (array) $_POST['product_id'] as $product_id ) {

			// Get the event this tickets is for
			$event_id = get_post_meta( $product_id, $this->event_key, true );

			if ( empty( $event_id ) ) {
				continue;
			}

			$qty = ! empty( $_POST[ 'quantity_' . $product_id ] ) ? intval( $_POST[ 'quantity_' . $product_id ] ) : 1;

			$has_tickets = true;

			// Iterate over all the amount of tickets purchased (for this product)
			for ( $i = 0; $i < $qty; $i ++ ) {

				$attendee = array(
					'post_status' => 'publish',
					'post_title'  => $attendee_full_name . ' | ' . ( $i + 1 ),
					'post_type'   => $this->attendee_object,
					'ping_status' => 'closed'
				);

				// Insert individual ticket purchased
				$attendee_id = wp_insert_post( $attendee );

				update_post_meta( $attendee_id, $this->atendee_product_key, $product_id );
				update_post_meta( $attendee_id, $this->atendee_event_key, $event_id );
				update_post_meta( $attendee_id, $this->security_code, $this->generate_security_code( $attendee_id ) );
				update_post_meta( $attendee_id, $this->order_key, $order_id );
				update_post_meta( $attendee_id, $this->full_name, $attendee_full_name );
				update_post_meta( $attendee_id, $this->email, $attendee_email );
			}
		}
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
			sprintf( __( 'Your tickets from %s', 'tribe-tickets' ), get_bloginfo( 'name' ) ) );

		wp_mail( $to, $subject, $content, $headers, $attachments );
	}

	protected function get_attendees_by_transaction( $order_id ) {
		$attendees = array();
		$query     = new WP_Query( array(
			'post_type'      => $this->attendee_object,
			'meta_key'       => $this->order_key,
			'meta_value'     => $order_id,
			'posts_per_page' => - 1
		) );

		foreach ( $query->posts as $post ) {
			$attendees[] = array(
				'event_id'      => get_post_meta( $post->ID, $this->atendee_product_key, true ),
				'ticket_name'   => get_post( get_post_meta( $post->ID, $this->atendee_product_key, true ) )->post_title,
				'holder_name'   => get_post_meta( $post->ID, $this->full_name, true ),
				'holder_email'  => get_post_meta( $post->ID, $this->email, true ),
				'order_id'      => $order_id,
				'ticket_id'     => $post->ID,
				'security_code' => get_post_meta( $post->ID, $this->security_code, true )
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

		if ( empty( $ticket->ID ) ) {
			/* Create main product post */
			$args = array(
				'post_status'  => 'publish',
				'post_type'    => $this->ticket_object,
				'post_author'  => get_current_user_id(),
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name
			);

			$ticket->ID = wp_insert_post( $args );

			// Relate event <---> ticket
			add_post_meta( $ticket->ID, $this->event_key, $event_id );

		} else {
			$args = array(
				'ID'           => $ticket->ID,
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name
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
			$event_id = get_post_meta( $ticket_id, $this->atendee_event_key, true );
		}
		$product_id = get_post_meta( $ticket_id, $this->atendee_product_key, true );

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

		$tickets = self::get_tickets( $post->ID );

		if ( empty( $tickets ) ) {
			return;
		}

		$rsvp_sent = empty( $_GET['rsvp_sent'] ) ? false : true;
		$rsvp_error = empty( $_GET['rsvp_error'] ) ? false : true;

		if ( $rsvp_sent ) {
			$this->add_message( __( 'Your RSVP has been received! Check your email for your RSVP confirmation.', 'tribe-tickets' ), 'success' );
		}

		if ( $rsvp_error ) {
			$this->add_message( __( 'In order to RSVP, you must enter your name and a valid email address.', 'tribe-tickets' ), 'error' );
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
		$qty    = get_post_meta( $ticket_id, 'total_sales', true );

		$return->description    = $product->post_excerpt;
		$return->frontend_link  = get_permalink( $ticket_id );
		$return->ID             = $ticket_id;
		$return->name           = $product->post_title;
		$return->price          = get_post_meta( $ticket_id, '_price', true );
		$return->provider_class = get_class( $this );
		$return->admin_link     = '';
		$return->stock          = get_post_meta( $ticket_id, '_stock', true );;
		$return->start_date     = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$return->end_date       = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$return->qty_sold       = $qty ? $qty : 0;

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

		if ( '' === ( $event = get_post_meta( $ticket_product, $this->event_key, true ) ) ) {
			return false;
		}

		if ( in_array( get_post_type( $event ), Tribe__Tickets__Main::instance()->post_types() ) ) {
			return get_post( $event );
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
			'post_type'      => $this->attendee_object,
			'meta_key'       => $this->atendee_event_key,
			'meta_value'     => $event_id,
			'orderby'        => 'ID',
			'order'          => 'DESC'
		) );

		if ( ! $attendees_query->have_posts() ) {
			return array();
		}

		$attendees = array();

		foreach ( $attendees_query->posts as $attendee ) {
			$checkin    = get_post_meta( $attendee->ID, $this->checkin_key, true );
			$security   = get_post_meta( $attendee->ID, $this->security_code, true );
			$product_id = get_post_meta( $attendee->ID, $this->atendee_product_key, true );
			$name       = get_post_meta( $attendee->ID, 'name', true );
			$email      = get_post_meta( $attendee->ID, 'email', true );

			if ( empty( $product_id ) ) {
				continue;
			}

			$product       = get_post( $product_id );
			$product_title = ( ! empty( $product ) ) ? $product->post_title : get_post_meta( $attendee->ID,
					$this->deleted_product, true ) . ' ' . __( '(deleted)', 'wootickets' );

			$attendees[] = array(
				'order_id'        => '',
				'purchaser_name'  => $name,
				'purchaser_email' => $email,
				'ticket'          => $product_title,
				'attendee_id'     => $attendee->ID,
				'security'        => $security,
				'product_id'      => $product_id,
				'check_in'        => $checkin,
				'provider'        => __CLASS__
			);
		}

		return $attendees;
	}

	/**
	 * Marks an attendee as checked in for an event
	 *
	 * @param $attendee_id
	 *
	 * @return bool
	 */
	public function checkin( $attendee_id ) {
		update_post_meta( $attendee_id, $this->checkin_key, 1 );
		do_action( 'rsvp_checkin', $attendee_id );

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
				$stock = $ticket->stock;
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
}
