<?php

if ( ! class_exists( 'Tribe__Tickets__Tickets' ) ) {
	/**
	 * Abstract class with the API definition and common functionality
	 * for Tribe Tickets Pro. Providers for this functionality need to
	 * extend this class. For a functional example of how this works
	 * see Tribe WooTickets.
	 *
	 * The relationship between orders, attendees and event posts is
	 * maintained through post meta fields set for the attendee object.
	 * Implementing classes are expected to provide the following class
	 * constants detailing those meta keys:
	 *
	 *     ATTENDEE_ORDER_KEY
	 *     ATTENDEE_EVENT_KEY
	 *     ATTENDEE_PRODUCT_KEY
	 *
	 * The post type name used for the attendee object should also be
	 * made available via:
	 *
	 *     ATTENDEE_OBJECT
	 */
	abstract class Tribe__Tickets__Tickets {

		/**
		 * All Tribe__Tickets__Tickets api consumers. It's static, so it's shared across all child.
		 *
		 * @var array
		 */
		protected static $active_modules = array();

		/**
		 * Indicates if the frontend ticket form script has already been enqueued (or not).
		 *
		 * @var bool
		 */
		protected static $frontend_script_enqueued = false;

		/**
		 * Collection of ticket objects for which we wish to make global stock data available
		 * on the frontend.
		 *
		 * @var array
		 */
		protected static $frontend_ticket_data = array();

		/**
		 * Name of this class. Note that it refers to the child class.
		 * @var string
		 */
		public $className;

		/**
		 * Path of the parent class
		 * @var string
		 */
		private $parentPath;

		/**
		 * URL of the parent class
		 * @var string
		 */
		private $parentUrl;

		// start API Definitions
		// Child classes must implement all these functions / properties

		/**
		 * Name of the provider
		 * @var
		 */
		public $pluginName;

		/**
		 * Path of the child class
		 * @var
		 */
		protected $pluginPath;

		/**
		 * URL of the child class
		 * @var
		 */
		protected $pluginUrl;

		/**
		 * Constant with the Transient Key for Attendees Cache
		 */
		const ATTENDEES_CACHE = 'tribe_attendees';

		/**
		 * Returns link to the report interface for sales for an event or
		 * null if the provider doesn't have reporting capabilities.
		 * @abstract
		 *
		 * @param $event_id
		 *
		 * @return mixed
		 */
		abstract public function get_event_reports_link( $event_id );

		/**
		 * Returns link to the report interface for sales for a single ticket or
		 * null if the provider doesn't have reporting capabilities.
		 *
		 * @abstract
		 *
		 * @param $event_id
		 * @param $ticket_id
		 *
		 * @return mixed
		 */
		abstract public function get_ticket_reports_link( $event_id, $ticket_id );

		/**
		 * Returns a single ticket
		 *
		 * @abstract
		 *
		 * @param $event_id
		 * @param $ticket_id
		 *
		 * @return mixed
		 */
		abstract public function get_ticket( $event_id, $ticket_id );

		/**
		 * Returns the event post corresponding to the possible ticket object/ticket ID.
		 *
		 * This is used to help differentiate between products which act as tickets for an
		 * event and those which do not. If $possible_ticket is not related to any events
		 * then boolean false will be returned.
		 *
		 * This stub method should be treated as if it were an abstract method - ie, the
		 * concrete class ought to provide the implementation.
		 *
		 * @todo convert to abstract method in 4.0
		 *
		 * @param $possible_ticket
		 *
		 * @return bool|WP_Post
		 */
		public function get_event_for_ticket( $possible_ticket ) {
			return false;
		}

		/**
		 * Deletes a ticket
		 *
		 * @abstract
		 *
		 * @param $event_id
		 * @param $ticket_id
		 *
		 * @return mixed
		 */
		abstract public function delete_ticket( $event_id, $ticket_id );

		/**
		 * Saves a ticket
		 *
		 * @abstract
		 *
		 * @param int   $event_id
		 * @param int   $ticket
		 * @param array $raw_data
		 *
		 * @return mixed
		 */
		abstract public function save_ticket( $event_id, $ticket, $raw_data = array() );

		/**
		 * Get all the tickets for an event
		 *
		 * @abstract
		 *
		 * @param int $event_id
		 *
		 * @return array mixed
		 */
		abstract protected function get_tickets( $event_id );

		/**
		 * Get all the attendees (sold tickets) for an event
		 * @abstract
		 *
		 * @param $event_id
		 *
		 * @return mixed
		 */
		abstract protected function get_attendees( $event_id );

		/**
		 * Mark an attendee as checked in
		 *
		 * @abstract
		 *
		 * @param $attendee_id
		 * @param $qr true if from QR checkin process
		 *
		 * @return mixed
		 */
		abstract public function checkin( $attendee_id );

		/**
		 * Mark an attendee as not checked in
		 *
		 * @abstract
		 *
		 * @param $attendee_id
		 *
		 * @return mixed
		 */
		abstract public function uncheckin( $attendee_id );


		/**
		 * Renders the advanced fields in the new/edit ticket form.
		 * Using the method, providers can add as many fields as
		 * they want, specific to their implementation.
		 *
		 * @abstract
		 *
		 * @param $event_id
		 * @param $ticket_id
		 *
		 * @return mixed
		 */
		abstract public function do_metabox_advanced_options( $event_id, $ticket_id );

		/**
		 * Renders the front end form for selling tickets in the event single page
		 *
		 * @abstract
		 *
		 * @param $content
		 *
		 * @return mixed
		 */
		abstract public function front_end_tickets_form( $content );

		/**
		 * Returns the markup for the price field
		 * (it may contain the user selected currency, etc)
		 *
		 * @param object|int $product
		 *
		 * @return string
		 */
		public function get_price_html( $product ) {
			return '';
		}

		/**
		 * Indicates if the module/ticket provider supports a concept of global stock.
		 *
		 * For backward compatibility reasons this method has not been declared abstract but
		 * implementaions are still expected to override it.
		 *
		 * @return bool
		 */
		public function supports_global_stock() {
			return false;
		}

		/**
		 * Returns instance of the child class (singleton)
		 *
		 * @static
		 * @abstract
		 * @return mixed
		 */
		public static function get_instance() {}

		// end API Definitions

		/**
		 *
		 */
		public function __construct() {

			// Start the singleton with the generic functionality to all providers.
			Tribe__Tickets__Tickets_Handler::instance();

			// As this is an abstract class, we want to know which child instantiated it
			$this->className = get_class( $this );

			$this->parentPath = trailingslashit( dirname( dirname( dirname( __FILE__ ) ) ) );
			$this->parentUrl  = trailingslashit( plugins_url( '', $this->parentPath ) );

			// Register all Tribe__Tickets__Tickets api consumers
			self::$active_modules[ $this->className ] = $this->pluginName;

			add_filter( 'tribe_events_tickets_modules', array( $this, 'modules' ) );
			add_action( 'tribe_events_tickets_metabox_advanced', array( $this, 'do_metabox_advanced_options' ), 10, 2 );

			// Admin AJAX actions for each provider
			add_action( 'wp_ajax_tribe-ticket-add-' . $this->className, array( $this, 'ajax_handler_ticket_add' ) );
			add_action( 'wp_ajax_tribe-ticket-delete-' . $this->className, array( $this, 'ajax_handler_ticket_delete' ) );
			add_action( 'wp_ajax_tribe-ticket-edit-' . $this->className, array( $this, 'ajax_handler_ticket_edit' ) );
			add_action( 'wp_ajax_tribe-ticket-checkin-' . $this->className, array( $this, 'ajax_handler_attendee_checkin' ) );
			add_action( 'wp_ajax_tribe-ticket-uncheckin-' . $this->className, array( $this, 'ajax_handler_attendee_uncheckin' ) );

			// Front end
			add_action( 'tribe_events_single_event_after_the_meta', array( $this, 'front_end_tickets_form' ), 5 );
			add_filter( 'the_content', array( $this, 'front_end_tickets_form_in_content' ) );

			// Ensure ticket prices and event costs are linked
			add_filter( 'tribe_events_event_costs', array( $this, 'get_ticket_prices' ), 10, 2 );
		}


		public function has_permission( $post, $data, $nonce_action ) {
			if ( ! $post instanceof WP_Post ) {
				if ( ! is_numeric( $post ) ) {
					return false;
				}

				$post = get_post( $post );
			}

			return ! empty( $data['nonce'] ) && wp_verify_nonce( $data['nonce'], $nonce_action ) && current_user_can( get_post_type_object( $post->post_type )->cap->edit_posts );
		}

		/* AJAX Handlers */

		/**
		 *    Sanitizes the data for the new/edit ticket ajax call,
		 *  and calls the child save_ticket function.
		 */
		final public function ajax_handler_ticket_add() {

			if ( ! isset( $_POST['formdata'] ) ) {
				$this->ajax_error( 'Bad post' );
			}
			if ( ! isset( $_POST['post_ID'] ) )
				$this->ajax_error( 'Bad post' );

			/*
			 This is needed because a provider can implement a dynamic set of fields.
			 Each provider is responsible for sanitizing these values.
			*/
			$data = wp_parse_args( $_POST['formdata'] );

			$post_id = $_POST['post_ID'];

			if ( ! $this->has_permission( $post_id, $_POST, 'add_ticket_nonce' ) ) {
				$this->ajax_error( "Cheatin' huh?" );
			}

			if ( ! isset( $data['ticket_provider'] ) || ! $this->module_is_valid( $data['ticket_provider'] ) ) {
				$this->ajax_error( 'Bad module' );
			}

			$return = $this->ticket_add( $post_id, $data );

			// Successful?
			if ( $return ) {
				// Let's create a tickets list markup to return
				$tickets = $this->get_event_tickets( $post_id );
				$return  = Tribe__Tickets__Tickets_Handler::instance()->get_ticket_list_markup( $tickets );

				$return = $this->notice( esc_html__( 'Your ticket has been saved.', 'event-tickets' ) ) . $return;

				/**
				 * Fire action when a ticket has been added
				 *
				 * @param $post_id
				 */
				do_action( 'tribe_tickets_ticket_added', $post_id );
			}

			$return = array( 'html' => $return );

			/**
			 * Filters the return data for ticket add
			 *
			 * @var array Array of data to return to the ajax call
			 */
			$return = apply_filters( 'event_tickets_ajax_ticket_add_data', $return, $post_id );

			$this->ajax_ok( $return );
		}

		/**
		 * Creates a ticket object and calls the child save_ticket function
		 *
		 * @param int $post_id WP_Post ID the ticket is being attached to
		 * @param array $data Raw post data
		 *
		 * @return boolean
		 */
		final public function ticket_add( $post_id, $data ) {
			$ticket = new Tribe__Tickets__Ticket_Object();

			$ticket->ID          = isset( $data['ticket_id'] ) ? absint( $data['ticket_id'] ) : null;
			$ticket->name        = isset( $data['ticket_name'] ) ? esc_html( $data['ticket_name'] ) : null;
			$ticket->description = isset( $data['ticket_description'] ) ? esc_html( $data['ticket_description'] ) : null;
			$ticket->price       = ! empty( $data['ticket_price'] ) ? trim( $data['ticket_price'] ) : 0;
			$ticket->purchase_limit = isset( $data['ticket_purchase_limit'] ) ? absint( $data['ticket_purchase_limit' ] ) : apply_filters( 'tribe_tickets_default_purchase_limit', 0, $ticket->ID );

			if ( ! empty( $ticket->price ) ) {
				// remove non-money characters
				$ticket->price = preg_replace( '/[^0-9\.\,]/Uis', '', $ticket->price );
			}

			if ( ! empty( $data['ticket_start_date'] ) ) {
				$meridian           = ! empty( $data['ticket_start_meridian'] ) ? ' ' . $data['ticket_start_meridian'] : '';
				$ticket->start_date = date( Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( $data['ticket_start_date'] . ' ' . $data['ticket_start_hour'] . ':' . $data['ticket_start_minute'] . ':00' . $meridian ) );
			}

			if ( ! empty( $data['ticket_end_date'] ) ) {
				$meridian         = ! empty( $data['ticket_end_meridian'] ) ? ' ' . $data['ticket_end_meridian'] : '';
				$ticket->end_date = date( Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( $data['ticket_end_date'] . ' ' . $data['ticket_end_hour'] . ':' . $data['ticket_end_minute'] . ':00' . $meridian ) );
			}

			$ticket->provider_class = $this->className;

			/**
			 * Fired once a ticket has been created and added to a post
			 *
			 * @var $post_id Post ID
			 * @var $ticket Ticket object
			 * @var $data Submitted post data
			 */
			do_action( 'tribe_tickets_ticket_add', $post_id, $ticket, $data );

			// Pass the control to the child object
			return $this->save_ticket( $post_id, $ticket, $data );
		}


		/**
		 * Handles the check-in ajax call, and calls the checkin method.
		 *
		 * @todo use of 'order_id' in this method is misleading (we're working with the attendee id)
		 *       we should consider revising in a back-compat minded way
		 */
		final public function ajax_handler_attendee_checkin() {

			if ( ! isset( $_POST['order_ID'] ) || intval( $_POST['order_ID'] ) == 0 ) {
				$this->ajax_error( 'Bad post' );
			}

			if ( ! isset( $_POST['provider'] ) || ! $this->module_is_valid( $_POST['provider'] ) ) {
				$this->ajax_error( 'Bad module' );
			}

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'checkin' ) || ! $this->user_can( 'edit_posts', $_POST['order_ID'] ) ) {
				$this->ajax_error( "Cheatin' huh?" );
			}

			$order_id = $_POST['order_ID'];

			// Pass the control to the child object
			$return = $this->checkin( $order_id );

			$this->ajax_ok( $return );
		}

		/**
		 * Handles the check-in ajax call, and calls the uncheckin method.
		 *
		 * @todo use of 'order_id' in this method is misleading (we're working with the attendee id)
		 *       we should consider revising in a back-compat minded way
		 */
		final public function ajax_handler_attendee_uncheckin() {

			if ( ! isset( $_POST['order_ID'] ) || intval( $_POST['order_ID'] ) == 0 ) {
				$this->ajax_error( 'Bad post' );
			}

			if ( ! isset( $_POST['provider'] ) || ! $this->module_is_valid( $_POST['provider'] ) ) {
				$this->ajax_error( 'Bad module' );
			}

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'uncheckin' ) || ! $this->user_can( 'edit_posts', $_POST['order_ID'] ) ) {
				$this->ajax_error( "Cheatin' huh?" );
			}

			$order_id = $_POST['order_ID'];

			// Pass the control to the child object
			$return = $this->uncheckin( $order_id );

			$this->ajax_ok( $return );
		}

		/**
		 * Sanitizes the data for the delete ticket ajax call, and calls the child delete_ticket
		 * function.
		 *
		 * @todo use of 'order_id' in this method is misleading (we're working with the attendee id)
		 *       we should consider revising in a back-compat minded way
		 */
		final public function ajax_handler_ticket_delete() {

			if ( ! isset( $_POST['post_ID'] ) ) {
				$this->ajax_error( 'Bad post' );
			}

			if ( ! isset( $_POST['ticket_id'] ) ) {
				$this->ajax_error( 'Bad post' );
			}

			$post_id = $_POST['post_ID'];

			if ( ! $this->has_permission( $post_id, $_POST, 'remove_ticket_nonce' ) ) {
				$this->ajax_error( "Cheatin' huh?" );
			}

			$ticket_id = $_POST['ticket_id'];

			// Pass the control to the child object
			$return = $this->delete_ticket( $post_id, $ticket_id );

			// Successfully deleted?
			if ( $return ) {
				// Let's create a tickets list markup to return
				$tickets = $this->get_event_tickets( $post_id );
				$return  = Tribe__Tickets__Tickets_Handler::instance()->get_ticket_list_markup( $tickets );

				$return = $this->notice( esc_html__( 'Your ticket has been deleted.', 'event-tickets' ) ) . $return;

				/**
				 * Fire action when a ticket has been deleted
				 *
				 * @param $post_id
				 */
				do_action( 'tribe_tickets_ticket_deleted', $post_id );
			}

			$this->ajax_ok( $return );
		}

		/**
		 * Returns the data from a single ticket to populate
		 * the edit form.
		 */
		final public function ajax_handler_ticket_edit() {

			if ( ! isset( $_POST['post_ID'] ) ) {
				$this->ajax_error( 'Bad post' );
			}

			if ( ! isset( $_POST['ticket_id'] ) ) {
				$this->ajax_error( 'Bad post' );
			}

			$post_id = $_POST['post_ID'];

			if ( ! $this->has_permission( $post_id, $_POST, 'edit_ticket_nonce' ) ) {
				$this->ajax_error( "Cheatin' huh?" );
			}

			$ticket_id = $_POST['ticket_id'];
			$ticket = $this->get_ticket( $post_id, $ticket_id );

			$return = get_object_vars( $ticket );
			/**
			 * Allow for the prevention of updating ticket price on update.
			 *
			 * @var boolean
			 * @var WP_Post
			 */
			$can_update_price = apply_filters( 'tribe_tickets_can_update_ticket_price', true, $ticket );

			$return['can_update_price'] = $can_update_price;

			if ( ! $can_update_price ) {
				/**
				 * Filter the no-update message that is displayed when updating the price is disallowed
				 *
				 * @var string
				 * @var WP_Post
				 */
				$return['disallow_update_price_message'] = apply_filters( 'tribe_tickets_disallow_update_ticket_price_message', esc_html__( 'Editing the ticket price is currently disallowed.', 'event-tickets' ), $ticket );
			}

			// Prevent HTML elements from been escaped
			$return['name'] = html_entity_decode( $return['name'], ENT_QUOTES );
			$return['name'] = htmlspecialchars_decode( $return['name'] );
			$return['description'] = html_entity_decode( $return['description'], ENT_QUOTES );
			$return['description'] = htmlspecialchars_decode( $return['description'] );

			ob_start();
			/**
			 * Fired to allow for the insertion of extra form data in the ticket admin form
			 *
			 * @var $post_id Post ID
			 * @var $ticket_id Ticket ID
			 */
			do_action( 'tribe_events_tickets_metabox_advanced', $post_id, $ticket_id );
			$extra = ob_get_contents();
			ob_end_clean();

			$return['advanced_fields'] = $extra;

			/**
			 * Provides an opportunity for final adjustments to the data used to populate
			 * the edit-ticket form.
			 *
			 * @var array $return data returned to the client
			 * @var Tribe__Events__Tickets $ticket_object
			 */
			$return = (array) apply_filters( 'tribe_events_tickets_ajax_ticket_edit', $return, $this );

			$this->ajax_ok( $return );
		}


		/**
		 * Returns the markup for a notice in the admin
		 *
		 * @param string $msg Text for the notice
		 *
		 * @return string Notice with markup
		 */
		protected function notice( $msg ) {
			return sprintf( '<div class="wrap"><div class="updated"><p>%s</p></div></div>', $msg );
		}


		// end AJAX Handlers

		// start Attendees

		/**
		 * Returns all the attendees for an event. Queries all registered providers.
		 *
		 * @static
		 *
		 * @param $event_id
		 *
		 * @return array
		 */
		public static function get_event_attendees( $event_id ) {
			$attendees = array();
			if ( ! is_admin() ) {
				$post_transient = Tribe__Post_Transient::instance();

				$attendees = $post_transient->get( $event_id, self::ATTENDEES_CACHE );
				if ( ! $attendees ) {
					$attendees = array();
				}

				if ( is_array( $attendees ) && count( $attendees ) > 0 ) {
					return $attendees;
				}
			}

			foreach ( self::$active_modules as $class => $module ) {
				$obj       = call_user_func( array( $class, 'get_instance' ) );
				$attendees = array_merge( $attendees, $obj->get_attendees( $event_id ) );
			}

			if ( ! is_admin() ) {
				$expire = apply_filters( 'tribe_tickets_attendees_expire', HOUR_IN_SECONDS );
				$post_transient->set( $event_id, self::ATTENDEES_CACHE, $attendees, $expire );
			}

			return $attendees;
		}

		/**
		 * Returns the total number of attendees for an event (regardless of provider).
		 *
		 * @param int $event_id
		 *
		 * @return int
		 */
		public static function get_event_attendees_count( $event_id ) {
			$attendees = self::get_event_attendees( $event_id );
			return count( $attendees );
		}

		/**
		 * Returns all tickets for an event (all providers are queried for this information).
		 *
		 * @param $event_id
		 *
		 * @return array
		 */
		public static function get_all_event_tickets( $event_id ) {
			$tickets = array();

			foreach ( self::$active_modules as $class => $module ) {
				$obj     = call_user_func( array( $class, 'get_instance' ) );
				$tickets = array_merge( $tickets, $obj->get_tickets( $event_id ) );
			}

			return $tickets;
		}

		/**
		 * Tests to see if the provided object/ID functions as a ticket for the event
		 * and returns the corresponding event if so (or else boolean false).
		 *
		 * All registered providers are asked to perform this test.
		 *
		 * @param $possible_ticket
		 * @return bool
		 */
		public static function find_matching_event( $possible_ticket ) {
			foreach ( self::$active_modules as $class => $module ) {
				$obj   = call_user_func( array( $class, 'get_instance' ) );
				$event = $obj->get_event_for_ticket( $possible_ticket );
				if ( false !== $event ) return $event;
			}

			return false;
		}

		/**
		 * Returns the sum of all checked-in attendees for an event. Queries all registered providers.
		 *
		 * @static
		 *
		 * @param $event_id
		 *
		 * @return mixed
		 */
		final public static function get_event_checkedin_attendees_count( $event_id ) {
			$checkedin = self::get_event_attendees( $event_id );

			return array_reduce( $checkedin, array( 'Tribe__Tickets__Tickets', '_checkedin_attendees_array_filter' ), 0 );
		}

		/**
		 * Internal function to use as a callback for array_reduce in
		 * get_event_checkedin_attendees_count. It increments the counter
		 * if the attendee is checked-in.
		 *
		 * @static
		 *
		 * @param $result
		 * @param $item
		 *
		 * @return mixed
		 */
		private static function _checkedin_attendees_array_filter( $result, $item ) {
			if ( ! empty( $item['check_in'] ) )
				return $result + 1;

			return $result;
		}


		// end Attendees

		// start Helpers

		/**
		 * Indicates if any of the currently available providers support global stock.
		 *
		 * @return bool
		 */
		public static function global_stock_available() {
			foreach ( self::$active_modules as $class => $module ) {
				$provider = call_user_func( array( $class, 'get_instance' ) );

				if ( method_exists( $provider, 'supports_global_stock' ) && $provider->supports_global_stock() ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns whether a class name is a valid active module/provider.
		 *
		 * @param $module
		 *
		 * @return bool
		 */
		private function module_is_valid( $module ) {
			return array_key_exists( $module, self::$active_modules );
		}

		/**
		 * Echos the class for the <tr> in the tickets list admin
		 */
		protected function tr_class() {
			echo 'ticket_advanced ticket_advanced_' . $this->className;
		}

		/**
		 * Generates a select element listing the available global stock mode options.
		 *
		 * @param string $current_option
		 *
		 * @return string
		 */
		protected function global_stock_mode_selector( $current_option = '' ) {
			$output = "<select id='ticket_global_stock' name='ticket_global_stock' class='ticket_field'>\n";

			// Default to using own stock unless the user explicitly specifies otherwise (important
			// to avoid assuming global stock mode if global stock is enabled/disabled accidentally etc)
			if ( empty( $current_option ) ) {
				$current_option = Tribe__Tickets__Global_Stock::OWN_STOCK_MODE;
			}

			foreach ( $this->global_stock_mode_options() as $identifier => $name ) {
				$identifier = esc_html( $identifier );
				$name = esc_html( $name );
				$selected = selected( $identifier === $current_option, true, false );
				$output .= "\t<option value='$identifier' $selected> $name </option>\n";
			}

			return "$output</select>";
		}

		/**
		 * Returns an array of standard global stock mode options that can be
		 * reused by implementations.
		 *
		 * Format is: [ 'identifier' => 'Localized name', ... ]
		 *
		 * @return array
		 */
		protected function global_stock_mode_options() {
			return array(
				Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE => __( 'Use global stock', 'event-tickets' ),
				Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE => __( 'Use global stock but cap sales', 'event-tickets' ),
				Tribe__Tickets__Global_Stock::OWN_STOCK_MODE    => __( 'Independent (do not use global stock)', 'event-tickets' ),
			);
		}

		/**
		 * Tries to make data about global stock levels and global stock-enabled ticket objects
		 * available to frontend scripts.
		 *
		 * @param array $tickets
		 */
		public static function add_frontend_stock_data( array $tickets ) {
			// Add the frontend ticket form script as needed (we do this lazily since right now
			// it's only required for certain combinations of event/ticket
			if ( ! self::$frontend_script_enqueued ) {
				$url = Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/js/frontend-ticket-form.js';
				$url = Tribe__Template_Factory::getMinFile( $url, true );

				wp_enqueue_script( 'tribe_tickets_frontend_tickets', $url, array( 'jquery' ), Tribe__Tickets__Main::VERSION, true );
				add_action( 'wp_footer', array( __CLASS__, 'enqueue_frontend_stock_data' ), 1 );
			}

			self::$frontend_ticket_data += $tickets;
		}

		/**
		 * Takes any global stock data and makes it available via a wp_localize_script() call.
		 */
		public static function enqueue_frontend_stock_data() {
			$data = array(
				'tickets'  => array(),
				'events'   => array(),
			);

			foreach ( self::$frontend_ticket_data as $ticket ) {
				/**
				 * @var Tribe__Tickets__Ticket_Object $ticket
				 */
				$event_id = $ticket->get_event()->ID;
				$global_stock = new Tribe__Tickets__Global_Stock( $event_id );
				$stock_mode = $ticket->global_stock_mode();

				$data[ 'tickets' ][ $ticket->ID ] = array(
					'event_id' => $event_id,
					'mode' => $stock_mode,
				);

				if ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $stock_mode ) {
					$data[ 'tickets' ][ $ticket->ID ][ 'cap' ] = $ticket->global_stock_cap();
				}

				if (
					Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === $stock_mode
					&& $ticket->managing_stock()
				) {
					$data[ 'tickets' ][ $ticket->ID ][ 'stock' ] = $ticket->stock();
				}

				$data[ 'events' ][ $event_id ] = array(
					'stock' => $global_stock->get_stock_level()
				);
			}

			wp_localize_script( 'tribe_tickets_frontend_tickets', 'tribe_tickets_stock_data', $data );
		}

		/**
		 * Returns the array of active modules/providers.
		 *
		 * @static
		 * @return array
		 */
		public static function modules() {
			return self::$active_modules;
		}

		/**
		 * Get all the tickets for an event. Queries all active modules/providers.
		 *
		 * @static
		 *
		 * @param $event_id
		 *
		 * @return array
		 */
		final public static function get_event_tickets( $event_id ) {

			$tickets = array();

			foreach ( self::$active_modules as $class => $module ) {
				$obj     = call_user_func( array( $class, 'get_instance' ) );
				$tickets = array_merge( $tickets, $obj->get_tickets( $event_id ) );
			}

			return $tickets;
		}

		/**
		 * Sets an AJAX error, returns a JSON array and ends the execution.
		 *
		 * @param string $message
		 */
		final protected function ajax_error( $message = '' ) {
			header( 'Content-type: application/json' );

			echo json_encode(
				array(
					'success' => false,
					'message' => $message,
				)
			);
			exit;
		}

		/**
		 * Sets an AJAX response, returns a JSON array and ends the execution.
		 *
		 * @param $data
		 */
		final protected function ajax_ok( $data ) {
			$return = array();
			if ( is_object( $data ) ) {
				$return = get_object_vars( $data );
			} elseif ( is_array( $data ) || is_string( $data ) ) {
				$return = $data;
			} elseif ( is_bool( $data ) && ! $data ) {
				$this->ajax_error( 'Something went wrong' );
			}

			header( 'Content-type: application/json' );
			echo json_encode(
				array(
					'success' => true,
					'data'    => $return,
				)
			);
			exit;
		}

		/**
		 * Generates and returns the email template for a group of attendees.
		 *
		 * @param $tickets
		 *
		 * @return string
		 */
		public function generate_tickets_email_content( $tickets ) {
			ob_start();
			$file = $this->getTemplateHierarchy( 'tickets/email.php' );

			if ( ! file_exists( $file ) ) {
				$file = Tribe__Tickets__Main::instance()->plugin_path . 'src/views/tickets/email.php';
			}

			include $file;

			return ob_get_clean();
		}

		/**
		 * Gets the view from the plugin's folder, or from the user's theme if found.
		 *
		 * @param $template
		 *
		 * @return mixed|void
		 */
		public function getTemplateHierarchy( $template ) {

			if ( substr( $template, - 4 ) != '.php' ) {
				$template .= '.php';
			}

			if ( $theme_file = locate_template( array( 'tribe-events/' . $template ) ) ) {
				$file = $theme_file;
			} else {
				$file = $this->pluginPath . 'src/views/' . $template;
			}

			return apply_filters( 'tribe_events_tickets_template_' . $template, $file );
		}

		/**
		 * Queries ticketing providers to establish the range of tickets/pricepoints for the specified
		 * event and ensures those costs are included in the $costs array.
		 *
		 * @param  array $prices
		 * @param  int   $event_id
		 *
		 * @return array
		 */
		public function get_ticket_prices( array $prices, $event_id ) {
			// Iterate through all tickets from all providers
			foreach ( self::get_all_event_tickets( $event_id ) as $ticket ) {
				// No need to add the pricepoint if it is already in the array
				if ( in_array( $ticket->price, $prices ) ) {
					continue;
				}


				// An empty price property can be ignored (but do add if the price is explicitly set to zero)
				elseif ( isset( $ticket->price ) && is_numeric( $ticket->price ) ) {
					$prices[] = $ticket->price;
				}
			}

			return $prices;
		}

		/**
		 * Tests if the user has the specified capability in relation to whatever post type
		 * the attendee object relates to.
		 *
		 * For example, if the attendee was generated for a ticket set up in relation to a
		 * post of the banana type, the generic capability "edit_posts" will be mapped to
		 * "edit_bananas" or whatever is appropriate.
		 *
		 * @internal for internal plugin use only (in spite of having public visibility)
		 * @see Tribe__Tickets__Tickets_Handler::user_can()
		 *
		 * @param  string $generic_cap
		 * @param  int    $attendee_id
		 * @return boolean
		 */
		public function user_can( $generic_cap, $attendee_id ) {
			$event_id = $this->get_event_id_from_attendee_id( $attendee_id );

			if ( empty( $event_id ) ) {
				return false;
			}

			return Tribe__Tickets__Tickets_Handler::instance()->user_can( $generic_cap, $event_id );
		}

		/**
		 * Given a valid attendee ID, returns the event ID it relates to or else boolean false
		 * if it cannot be determined.
		 *
		 * @param  int   $attendee_id
		 * @return mixed int|bool
		 */
		public function get_event_id_from_attendee_id( $attendee_id ) {
			$provider_class     = new ReflectionClass( $this );
			$attendee_event_key = $this->get_attendee_event_key( $provider_class );

			if ( empty( $attendee_event_key ) ) {
				return false;
			}

			$event_id = get_post_meta( $attendee_id, $attendee_event_key, true );

			if ( empty( $event_id ) ) {
				return false;
			}

			return (int) $event_id;
		}

		/**
		 * Given a valid order ID, returns the event ID it relates to or else boolean false
		 * if it cannot be determined.
		 *
		 * @param  int   $order_id
		 * @return mixed int|bool
		 */
		public function get_event_id_from_order_id( $order_id ) {
			$provider_class     = new ReflectionClass( $this );
			$attendee_order_key = $this->get_attendee_order_key( $provider_class );
			$attendee_event_key = $this->get_attendee_event_key( $provider_class );
			$attendee_object    = $this->get_attendee_object( $provider_class );

			if ( empty( $attendee_order_key ) || empty( $attendee_event_key ) || empty( $attendee_object ) ) {
				return false;
			}

			$first_matched_attendee = get_posts( array(
				'post_type'  => $attendee_object,
				'meta_key'   => $attendee_order_key,
				'meta_value' => $order_id,
				'posts_per_page' => 1,
			) );

			if ( empty( $first_matched_attendee ) ) {
				return false;
			}

			return $this->get_event_id_from_attendee_id( $first_matched_attendee[0]->ID );
		}

		/**
		 * Returns the meta key used to link attendees with orders.
		 *
		 * This method provides backwards compatibility with older ticketing providers
		 * that do not define the expected class constants. Once a decent period has
		 * elapsed we can kill this method and access the class constants directly.
		 *
		 * @param  ReflectionClass $provider_class representing the concrete ticket provider
		 * @return string
		 */
		protected function get_attendee_order_key( $provider_class ) {
			$attendee_order_key = $provider_class->getConstant( 'ATTENDEE_ORDER_KEY' );

			if ( empty( $attendee_order_key ) ) {
				switch( $this->className ) {
					case 'Tribe__Events__Tickets__Woo__Main':   return '_tribe_wooticket_order';   break;
					case 'Tribe__Events__Tickets__EDD__Main':   return '_tribe_eddticket_order';   break;
					case 'Tribe__Events__Tickets__Shopp__Main': return '_tribe_shoppticket_order'; break;
					case 'Tribe__Events__Tickets__Wpec__Main':  return '_tribe_wpecticket_order';  break;
				}
			}

			return (string) $attendee_order_key;
		}

		/**
		 * Returns the attendee object post type.
		 *
		 * This method provides backwards compatibility with older ticketing providers
		 * that do not define the expected class constants. Once a decent period has
		 * elapsed we can kill this method and access the class constants directly.
		 *
		 * @param  ReflectionClass $provider_class representing the concrete ticket provider
		 * @return string
		 */
		protected function get_attendee_object( $provider_class ) {
			$attendee_object = $provider_class->getConstant( 'ATTENDEE_OBJECT' );

			if ( empty( $attendee_order_key ) ) {
				switch( $this->className ) {
					case 'Tribe__Events__Tickets__Woo__Main':   return 'tribe_wooticket';   break;
					case 'Tribe__Events__Tickets__EDD__Main':   return 'tribe_eddticket';   break;
					case 'Tribe__Events__Tickets__Shopp__Main': return 'tribe_shoppticket'; break;
					case 'Tribe__Events__Tickets__Wpec__Main':  return 'tribe_wpecticket';  break;
				}
			}

			return (string) $attendee_object;
		}

		/**
		 * Returns the meta key used to link attendees with the base event.
		 *
		 * This method provides backwards compatibility with older ticketing providers
		 * that do not define the expected class constants. Once a decent period has
		 * elapsed we can kill this method and access the class constants directly.
		 *
		 * If the meta key cannot be determined the returned string will be empty.
		 *
		 * @param  ReflectionClass $provider_class representing the concrete ticket provider
		 * @return string
		 */
		protected function get_attendee_event_key( $provider_class ) {
			$attendee_event_key = $provider_class->getConstant( 'ATTENDEE_EVENT_KEY' );

			if ( empty( $attendee_event_key ) ) {
				switch( $this->className ) {
					case 'Tribe__Events__Tickets__Woo__Main':   return '_tribe_wooticket_event';   break;
					case 'Tribe__Events__Tickets__EDD__Main':   return '_tribe_eddticket_event';   break;
					case 'Tribe__Events__Tickets__Shopp__Main': return '_tribe_shoppticket_event'; break;
					case 'Tribe__Events__Tickets__Wpec__Main':  return '_tribe_wpecticket_event';  break;
				}
			}

			return (string) $attendee_event_key;
		}

		// end Helpers

		public function front_end_tickets_form_in_content( $content ) {
			global $post;

			if ( is_admin() ) {
				return $content;
			}

			// if this isn't a post for some reason, bail
			if ( ! $post instanceof WP_Post ) {
				return $content;
			}

			// if this isn't a supported post type, bail
			if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
				return $content;
			}

			// if this is a tribe_events post, let's bail because those post types are handled with a different hook
			if ( 'tribe_events' === $post->post_type ) {
				return $content;
			}

			// if there aren't any tickets, bail
			$tickets = $this->get_tickets( $post->ID );
			if ( empty( $tickets ) ) {
				return $content;
			}

			ob_start();
			$this->front_end_tickets_form( $content );
			$form = ob_get_clean();

			$content .= $form;

			return $content;
		}
	}
}
