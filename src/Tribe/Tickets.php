<?php

if ( ! class_exists( 'Tribe__Tickets__Tickets' ) ) {
	/**
	 * Class with the API definition and common functionality
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
	 *
	 *
	 * @since  4.5.0.1 Due to a fatal between Event Ticket Plus extending commerces and this class,
	 *                 we changed this from an Abstract to a normal parent Class
	 */
	class Tribe__Tickets__Tickets {

		/**
		 * Flag used to track if the registration form link has been displayed or not.
		 *
		 * @var boolean
		 */
		private static $have_displayed_reg_link = false;

		/**
		 * All Tribe__Tickets__Tickets api consumers. It's static, so it's shared across all children.
		 *
		 * @var array
		 */
		protected static $active_modules = array();

		/**
		 * Default Tribe__Tickets__Tickets ecommerce module.
		 * It's static, so it's shared across all children.
		 *
		 * @var string
		 */
		protected static $default_module = 'Tribe__Tickets__RSVP';

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
		 *
		 * @var string
		 */
		public $class_name;

		/**
		 * Path of the parent class
		 *
		 * @var string
		 */
		private $parent_path;

		/**
		 * URL of the parent class
		 *
		 * @var string
		 */
		private $parent_url;

		/**
		 * Records batches of tickets that are currently unavailable (used for
		 * displaying the correct "tickets are unavailable" message).
		 *
		 * @var array
		 */
		protected static $currently_unavailable_tickets = array();

		/**
		 * Records posts for which tickets *are* available (used to determine if
		 * a "tickets are unavailable" message should even display).
		 *
		 * @var array
		 */
		protected static $posts_with_available_tickets = array();

		// start API Definitions
		// Child classes must implement all these functions / properties

		/**
		 * Name of the provider
		 *
		 * @var string
		 */
		public $plugin_name;

		/**
		 * Path of the child class
		 *
		 * @var string
		 */
		protected $plugin_path;

		/**
		 * URL of the child class
		 *
		 * @var string
		 */
		protected $plugin_url;

		/**
		 * The name of the post type representing a ticket.
		 *
		 * @var string
		 */
		public $ticket_object = '';

		/* Deprecated vars */

		/**
		 * Name of this class. Note that it refers to the child class.
		 * deprecated - use $class_name
		 *
		 * @deprecated TBD
		 *
		 * @var string
		 */
		public $className;

		/**
		 * Path of the parent class
		 * deprecated - use $parent_path
		 *
		 * @deprecated TBD
		 *
		 * @var string
		 */
		private $parentPath;

		/**
		 * URL of the parent class
		 * deprecated - use $parent_url
		 *
		 * @deprecated TBD
		 *
		 * @var string
		 */
		private $parentUrl;

		/**
		 * Name of the provider
		 * deprecated - use $plugin_name
		 *
		 * @deprecated TBD
		 *
		 * @var string
		 */
		public $pluginName;

		/**
		 * Path of the child class
		 * deprecated - use $plugin_path
		 *
		 * @deprecated TBD
		 *
		 * @var string
		 */
		protected $pluginPath;

		/**
		 * URL of the child class
		 * deprecated - use $plugin_url
		 *
		 * @deprecated TBD
		 *
		 * @var string
		 */
		protected $pluginUrl;

		/**
		 * Constant with the Transient Key for Attendees Cache
		 */
		const ATTENDEES_CACHE = 'tribe_attendees';

		const ATTENDEE_USER_ID = '_tribe_tickets_attendee_user_id';

		/**
		 * Returns link to the report interface for sales for an event or
		 * null if the provider doesn't have reporting capabilities.
		 *
		 * @abstract
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return mixed
		 */
		public function get_event_reports_link( $post_id ) {

		}

		/**
		 * Returns link to the report interface for sales for a single ticket or
		 * null if the provider doesn't have reporting capabilities.
		 * As of TBD we reversed the params and deprecated $post_id as it was never used
		 *
		 * @abstract
		 *
		 * @param deprecated $post_id ID of parent "event" post
		 * @param int $ticket_id ID of ticket post
		 * @return mixed
		 */
		public function get_ticket_reports_link( $post_id_deprecated, $ticket_id ) {

		}

		/**
		 * Returns a single ticket
		 *
		 * @abstract
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param int $ticket_id ID of ticket post
		 * @return mixed
		 */
		public function get_ticket( $post_id, $ticket_id ) {

		}

		/**
		 * Returns the html for the delete ticket link
		 *
		 * @since TBD
		 *
		 * @param object $ticket Ticket object
		 * @return string HTMl link
		 */
		public function get_ticket_delete_link( $ticket = null ) {
			if ( empty( $ticket ) ) {
				return;
			}

			$button_text = ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) ? __( 'Delete RSVP', 'event-tickets' ) : __( 'Delete Ticket', 'event-tickets' ) ;

			/**
			 * Allows for the filtering and testing if a user can delete tickets
			 *
			 * @since TBD
			 *
			 * @param bool true
			 * @param int ticket post ID
			 * @return string HTML link | void HTML link
			 */
			if ( apply_filters( 'tribe_tickets_current_user_can_delete_ticket', true, $ticket->ID, $ticket->provider_class ) ) {
				$delete_link = sprintf(
					'<span><a href="#" attr-provider="%1$s" attr-ticket-id="%2$s" id="ticket_delete_%2$s" class="ticket_delete">%3$s</a></span>',
					$ticket->provider_class,
					$ticket->ID,
					esc_html( $button_text )
				);

				return $delete_link;
			}

			$delete_link = sprintf(
				'<span><a href="#" attr-provider="%1$s" attr-ticket-id="%2$s" id="ticket_delete_%2$s" class="ticket_delete">%3$s</a></span>',
				$ticket->provider_class,
				$ticket->ID,
				esc_html__( $button_text )
			);

			return $delete_link;
		}

		/**
		 * Returns the url for the move ticket link
		 *
		 * @since TBD
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param object $ticket Ticket object
		 * @return string HTML link | void HTML link
		 */
		public function get_ticket_move_url( $post_id, $ticket = null ) {
			if ( empty( $ticket ) || empty( $post_id ) ) {
				return;
			}

			$post_url = get_edit_post_link( $post_id, 'admin' );

			$move_type_url = add_query_arg(
				array(
					'dialog'         => Tribe__Tickets__Main::instance()->move_ticket_types()->dialog_name(),
					'ticket_type_id' => $ticket->ID,
					'check'          => wp_create_nonce( 'move_tickets' ),
					'TB_iframe'      => 'true',
				),
				$post_url
			);

			return $move_type_url;
		}

		/**
		 * Returns the html for the move ticket link
		 *
		 * @since TBD
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param object $ticket Ticket object
		 * @return string HTML link | void HTML link
		 */
		public function get_ticket_move_link( $post_id, $ticket = null ) {
			if ( empty( $ticket ) ) {
				return;
			}

			$button_text = ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) ? __( 'Move RSVP', 'event-tickets' ) : __( 'Move Ticket', 'event-tickets' ) ;

			$move_url = $this->get_ticket_move_url( $post_id, $ticket );

			if ( empty( $move_url ) ) {
				return;
			}

			$move_link = sprintf( '<a href="%1$s" class="thickbox">' . esc_html( $button_text ) . '</a>', $move_url );

			return $move_link;
		}

		/**
		 * Get the controls (move, delete) as a string and add to our ajax return
		 *
		 * @since TBD
		 *
		 * @param array $return the ajax return data
		 * @return array $return modified data
		 */
		public function ajax_ticket_edit_controls( $return ) {
			$ticket = $this->get_ticket( $return['post_id'], $return['ID'] );

			if ( empty( $ticket ) ) {
				return $return;
			}

			$controls   = array();
			$controls[] = $this->get_ticket_move_link( $return['post_id'], $ticket );
			$controls[] = $this->get_ticket_delete_link( $ticket );

			if ( ! empty( $controls ) ) {
				$return['controls'] = join( '  |  ', $controls );
			}

			return $return;
		}

		/**
		 * Attempts to load the specified ticket type post object.
		 *
		 * @param int $ticket_id ID of ticket post
		 * @return Tribe__Tickets__Ticket_Object|null
		 */
		public static function load_ticket_object( $ticket_id ) {
			foreach ( self::modules() as $provider_class => $name ) {
				$provider = call_user_func( array( $provider_class, 'get_instance' ) );
				$event    = $provider->get_event_for_ticket( $ticket_id );

				if ( ! $event ) {
					continue;
				}

				$ticket_object = $provider->get_ticket( $event->ID, $ticket_id );

				if ( $ticket_object ) {
					return $ticket_object;
				}
			}

			return null;
		}

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
		 * @param int $post_id ID of parent "event" post
		 * @param int $ticket_id ID of ticket post
		 * @return mixed
		 */
		public function delete_ticket( $post_id, $ticket_id ) {

		}

		/**
		 * Saves a ticket
		 *
		 * @abstract
		 *
		 * @param int   $post_id
		 * @param int   $ticket
		 * @param array $raw_data
		 * @return mixed
		 */
		public function save_ticket( $post_id, $ticket, $raw_data = array() ) {

		}

		/**
		 * Get all the tickets for an event
		 *
		 * @abstract
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return array mixed
		 */
		protected function get_tickets( $post_id ) {

		}

		/**
		 * Get attendees by id and associated post type
		 * or default to using $post_id
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param null $post_type
		 * @return array|mixed
		 */
		public function get_attendees_by_id( $post_id ) {

		}

		/**
		 * Get all the attendees (sold tickets) for an event
		 *
		 * @abstract
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return mixed
		 */
		protected function get_attendees_by_post_id( $post_id ) {

		}

		/**
		 * Get Attendees by ticket/attendee ID
		 *
		 * @param int $attendee_id
		 * @return array
		 */
		protected function get_attendees_by_attendee_id( $attendee_id ) {

		}

		/**
		 * Get attendees by order id
		 *
		 * @param int $order_id
		 * @return array
		 */
		protected function get_attendees_by_order_id( $order_id ) {

		}

		/**
		 * Get attendees from provided query
		 *
		 * @param WP_Query $attendees_query
		 * @param int $post_id ID of parent "event" post
		 * @return mixed
		 */
		protected function get_attendees( $attendees_query, $post_id ) {

		}

		/**
		 * Mark an attendee as checked in
		 *
		 * @abstract
		 *
		 * @param int $attendee_id
		 * @param $qr true if from QR checkin process
		 * @return mixed
		 */
		public function checkin( $attendee_id ) {

		}

		/**
		 * Mark an attendee as not checked in
		 *
		 * @abstract
		 *
		 * @param int $attendee_id
		 * @return mixed
		 */
		public function uncheckin( $attendee_id ) {

		}

		/**
		 * Renders the advanced fields in the new/edit ticket form.
		 * Using the method, providers can add as many fields as
		 * they want, specific to their implementation.
		 *
		 * @abstract
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param int $ticket_id ID of ticket post
		 * @return mixed
		 */
		public function do_metabox_capacity_options( $post_id, $ticket_id ) {

		}

		/**
		 * Renders the front end form for selling tickets in the event single page
		 *
		 * @abstract
		 *
		 * @param $content
		 * @return mixed
		 */
		public function front_end_tickets_form( $content ) {

		}

		/**
		 * Returns the markup for the price field
		 * (it may contain the user selected currency, etc)
		 *
		 * @param object|int $product
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
			$this->class_name = $this->className = get_class( $this );

			$this->parent_path = $this->parentPath = trailingslashit( dirname( dirname( dirname( __FILE__ ) ) ) );
			$this->parent_url  = $this->parentUrl  = trailingslashit( plugins_url( '', $this->parent_path ) );

			// Register all Tribe__Tickets__Tickets api consumers
			self::$active_modules[ $this->class_name ] = $this->plugin_name;

			add_filter( 'tribe_events_tickets_modules', array( $this, 'modules' ) );
			/**
			 * Priority set to 11 to force a specific display order
			 *
			 * @since TBD
			 */
			add_action( 'tribe_events_tickets_metabox_edit_main', array( $this, 'do_metabox_capacity_options' ), 11, 2 );
			add_filter( 'tribe_events_tickets_ajax_ticket_edit', array( $this, 'ajax_ticket_edit_controls' ) );
			add_filter( 'wp_ajax_tribe-events-edit-global-capacity', array( $this, 'edit_global_capacity_level' ) );
			add_action( 'wp_ajax_tribe-ticket-refresh-panels', array( $this, 'ajax_refresh_panels' ) );

			// Admin AJAX actions for each provider
			add_action( 'wp_ajax_tribe-ticket-add-' . $this->class_name, array( $this, 'ajax_handler_ticket_add' ) );
			add_action( 'wp_ajax_tribe-ticket-delete-' . $this->class_name, array( $this, 'ajax_handler_ticket_delete' ) );
			add_action( 'wp_ajax_tribe-ticket-edit-' . $this->class_name, array( $this, 'ajax_handler_ticket_edit' ) );
			add_action( 'wp_ajax_tribe-ticket-checkin-' . $this->class_name, array( $this, 'ajax_handler_attendee_checkin' ) );
			add_action( 'wp_ajax_tribe-ticket-uncheckin-' . $this->class_name, array( $this, 'ajax_handler_attendee_uncheckin' ) );

			// Front end
			$ticket_form_hook = $this->get_ticket_form_hook();

			if ( ! empty( $ticket_form_hook ) ) {
				add_action( $ticket_form_hook, array( $this, 'front_end_tickets_form' ), 5 );
			}

			add_action( 'tribe_events_single_event_after_the_meta', array( $this, 'show_tickets_unavailable_message' ), 6 );
			add_filter( 'the_content', array( $this, 'front_end_tickets_form_in_content' ), 11 );
			add_filter( 'the_content', array( $this, 'show_tickets_unavailable_message_in_content' ), 12 );

			// Ensure ticket prices and event costs are linked
			add_filter( 'tribe_events_event_costs', array( $this, 'get_ticket_prices' ), 10, 2 );
		}


		/**
		 * test if the nonce is correct and the current user has the correct permissions
		 * @param  WP_Post  $post
		 * @param  array  $data
		 * @param  string  $nonce_action
		 * @return boolean
		 */
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
		 * Refreshes panels after ajax calls that change data
		 *
		 * @since TBD
		 *
		 * @return string html content of the panels
		 */
		public function ajax_refresh_panels() {
			// Didn't get a post id to work with - bail
			if ( empty( $_POST['post_ID'] ) ) {
				return;
			}
			$post_id = $_POST['post_ID'];

			// Save ticket order
			if ( ! empty( $_POST['ticket_order'] ) ) {
				$ticket_order = $_POST['ticket_order'];
				update_post_meta( $post_id, '_tribe_tickets_order', $ticket_order );

				$ticket_order = explode( ',', $ticket_order );
				$ticket_order = array_flip( $ticket_order );

				foreach ( $ticket_order as $id => $order ) {
					wp_update_post( array(
						'ID'         => absint( $id ),
						'menu_order' => absint( $order ),
					) );
				}
			}

			// Let's create tickets list markup to return
			$tickets = $this->get_event_tickets( $post_id );

			$notice_type  = ! empty( $_POST['notice'] ) ? $_POST['notice'] : '';
			$ticket_table = '';

			switch ( $notice_type ) {
				case 'settings' :
					$ticket_table .= $this->notice( esc_html__( 'Your settings have been saved.', 'event-tickets' ) );
					break;
				case 'ticket':
					$ticket_table .= $this->notice( esc_html__( 'Your ticket has been saved.', 'event-tickets' ) );
					break;
				case 'delete':
					$ticket_table .= $this->notice( esc_html__( 'Your ticket has been deleted.', 'event-tickets' ) );
					break;
				default:
					// Don't add a notice if we didn't get a notice type
			}

			$ticket_table .= Tribe__Tickets__Tickets_Handler::instance()->get_ticket_list_markup( $tickets );

			$return['ticket_table'] = $ticket_table;

			/**
			 * Allows filtering the data by other plugins/ecommerce solutions©
			 *
			 * @since TBD
			 *
			 * @param array the return data
			 * @param int the post/event id
			 */
			$return = apply_filters( 'tribe_tickets_ajax_refresh_tables', $return, $post_id );

			$this->ajax_ok( $return );
		}

		/**
		 * Sanitizes the data for the new/edit ticket ajax call,
		 * and calls the child save_ticket function.
		 */
		final public function ajax_handler_ticket_add() {
			if ( ! isset( $_POST['formdata'] ) || ! isset( $_POST['post_ID'] ) ) {
				$this->ajax_error( 'Bad post' );
			}

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

			$ticket_id = $this->ticket_add( $post_id, $data );

			// Successful?
			if ( $ticket_id ) {
				/**
				 * Fire action when a ticket has been added
				 *
				 * @param int $post_id ID of parent "event" post
				 */
				do_action( 'tribe_tickets_ticket_added', $post_id );
			}

			parse_str ( $_POST['formdata'], $post_data );

			$ticket = $this->get_ticket( $post_id, $ticket_id );

			$post_data['ticket_stock'] = $ticket->stock;
			$post_data['ticket_capacity'] = $ticket->original_stock();

			$return = array(
				'data' => json_encode( $post_data, JSON_FORCE_OBJECT ),
			);

			if ( ! empty( $html ) ) {
				$return['html'] = $html;
			}

			/**
			 * Filters the return data for ticket add
			 *
			 * @param array $return Array of data to return to the ajax call
			 * @param int $post_id ID of parent "event" post
			 */
			$return = apply_filters( 'event_tickets_ajax_ticket_add_data', $return, $post_id );

			$this->ajax_ok( $return );
		}

		/**
		 * Creates a ticket object and calls the child save_ticket function
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param array $data Raw post data
		 * @return boolean
		 */
		final public function ticket_add( $post_id, $data ) {
			$ticket                   = new Tribe__Tickets__Ticket_Object();
			$ticket->ID               = isset( $data['ticket_id'] ) ? absint( $data['ticket_id'] ) : null;
			$ticket->name             = isset( $data['ticket_name'] ) ? esc_html( $data['ticket_name'] ) : null;
			$ticket->description      = isset( $data['ticket_description'] ) ? esc_html( $data['ticket_description'] ) : null;
			$ticket->price            = ! empty( $data['ticket_price'] ) ? filter_var( trim( $data['ticket_price'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND ) : 0;
			$ticket->purchase_limit   = isset( $data['ticket_purchase_limit'] ) ? absint( $data['ticket_purchase_limit'] ) : apply_filters( 'tribe_tickets_default_purchase_limit', 0, $ticket->ID );

			$show_description = isset( $data['ticket_show_description'] ) ? absint( $data['ticket_show_description'] ) : 0;
			$ticket->show_description = $ticket->show_description();

			if ( ! empty( $ticket->price ) ) {
				// remove non-money characters
				$ticket->price = preg_replace( '/[^0-9\.\,]/Uis', '', $ticket->price );
			}

			if ( ! empty( $data['ticket_start_date'] ) ) {
				$start_datetime = sprintf(
					'%s %s',
					$data['ticket_start_date'],
					$data['ticket_start_time']
				);
				$ticket->start_date = date( Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( $start_datetime ) );
			}

			if ( ! empty( $data['ticket_end_date'] ) ) {
				$end_datetime = sprintf(
					'%s %s',
					$data['ticket_end_date'],
					$data['ticket_end_time']
				);
				$ticket->end_date = date( Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( $end_datetime ) );
			}

			$ticket->provider_class = $this->class_name;

			/**
			 * Fired once a ticket has been created and added to a post
			 *
			 * @param int $post_id ID of parent "event" post
			 * @param Tribe__Tickets__Ticket_Object $ticket Ticket object
			 * @param array $data Submitted post data
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
			$did_checkin = $this->checkin( $order_id );

			$this->maybe_update_attendees_cache( $did_checkin );

			$this->ajax_ok( $did_checkin );
		}

		/**
		 * Handles the check-in ajax call, and calls the uncheckin method.
		 *
		 * @TODO use of 'order_id' in this method is misleading (we're working with the attendee id)
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
			$did_uncheckin = $this->uncheckin( $order_id );

			if ( class_exists( 'Tribe__Events__Main' ) ) {
				$this->maybe_update_attendees_cache( $did_uncheckin );
			}

			$this->ajax_ok( $did_uncheckin );
		}

		/**
		 * Sanitizes the data for the delete ticket ajax call, and calls the child delete_ticket
		 * function.
		 *
		 * @TODO use of 'order_id' in this method is misleading (we're working with the attendee id)
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
				 * @param int $post_id ID of parent "event" post
				 */
				do_action( 'tribe_tickets_ticket_deleted', $post_id );
			}

			$this->ajax_ok( $return );
		}

		/**
		 * Returns the data from a single ticket to populate
		 * the edit form.
		 *
		 * @return array $return array of ticket data
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
			$return['post_id'] = $post_id;
			/**
			 * Allow for the prevention of updating ticket price on update.
			 *
			 * @param boolean
			 * @param WP_Post
			 */
			$can_update_price = apply_filters( 'tribe_tickets_can_update_ticket_price', true, $ticket );

			$return['can_update_price'] = $can_update_price;

			if ( ! $can_update_price ) {
				/**
				 * Filter the no-update message that is displayed when updating the price is disallowed
				 *
				 * @param string
				 * @param WP_Post
				 */
				$return['disallow_update_price_message'] = apply_filters( 'tribe_tickets_disallow_update_ticket_price_message', esc_html__( 'Editing the ticket price is currently disallowed.', 'event-tickets' ), $ticket );
			}

			// Prevent HTML elements from being escaped
			$return['name']        = html_entity_decode( $return['name'], ENT_QUOTES );
			$return['name']        = htmlspecialchars_decode( $return['name'] );
			$return['description'] = html_entity_decode( $return['description'], ENT_QUOTES );
			$return['description'] = htmlspecialchars_decode( $return['description'] );

			ob_start();
			/**
			 * Fired to allow for the insertion of extra form data in the ticket int admin form
			 *
			 * @param int $post_id ID of parent "event" post
			 * @param int $ticket_id ID of ticket post
			 */
			do_action( 'tribe_events_tickets_metabox_edit_advanced', $post_id, $ticket_id );

			$extra = ob_get_contents();
			ob_end_clean();

			$return['advanced_fields']   = $extra;

			/**
			 * Allows for the insertion of the attendee meta fields into the ticket admin form
			 *
			 * @since TBD
			 *
			 * @param int $post_id ID of parent "event" post
			 * @param int $ticket_id ID of ticket post
			 */
			$return['attendee_fields']   = apply_filters( 'tribe_events_tickets_metabox_edit_attendee', $post_id, $ticket_id );

			$return['stock']             = $ticket->stock;
			$return['original_stock']    = $ticket->original_stock();
			$global_stock_mode           = ( isset( $ticket ) ) ? $ticket->global_stock_mode() : '';
			$return['global_stock_mode'] = $global_stock_mode;
			$return['show_description']  = $ticket->show_description;

			if ( Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $global_stock_mode || Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $global_stock_mode ) {
				$global_stock_cap             = get_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true );
				$return['global_stock_cap']   = $global_stock_cap;
				$return['total_global_stock'] = $this->global_stock_level( $return['post_id'] );
			}

			/**
			 * Provides an opportunity for final adjustments to the data used to populate
			 * the edit-ticket form.
			 *
			 * @param array $return data returned to the client
			 * @param Tribe__Events__Tickets $ticket_object
			 */
			$return = (array) apply_filters( 'tribe_events_tickets_ajax_ticket_edit', $return, $this );
			$this->ajax_ok( $return );
		}

		/**
		 * Returns the markup for a notice in the admin
		 *
		 * @param string $msg Text for the notice
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
		 * @param int $post_id ID of parent "event" post
		 * @return array
		 */
		public static function get_event_attendees( $post_id ) {
			$attendees_from_cache = false;
			$attendees            = array();

			if ( ! is_admin() ) {
				$post_transient = Tribe__Post_Transient::instance();

				$attendees_from_cache = $post_transient->get( $post_id, self::ATTENDEES_CACHE );

				// if there is a valid transient, we'll use the value from that and note
				// that we have fetched from cache
				if ( false !== $attendees_from_cache ) {
					$attendees            = empty( $attendees_from_cache ) ? array() : $attendees_from_cache;
					$attendees_from_cache = true;
				}
			}

			// if we haven't grabbed attendees from cache, then attempt to fetch attendees
			if ( false === $attendees_from_cache && empty( $attendees ) ) {
				foreach ( self::modules() as $class => $module ) {
					$obj       = call_user_func( array( $class, 'get_instance' ) );
					$attendees = array_merge( $attendees, $obj->get_attendees_by_post_id( $post_id ) );
				}

				// Set the `ticket_exists` flag on attendees if the ticket they are associated with
				// does not exist.
				foreach ( $attendees as &$attendee ) {
					$attendee['ticket_exists'] = ! empty( $attendee['product_id'] ) && get_post( $attendee['product_id'] );
				}

				if ( ! is_admin() ) {
					$expire = apply_filters( 'tribe_tickets_attendees_expire', HOUR_IN_SECONDS );
					$post_transient->set( $post_id, self::ATTENDEES_CACHE, $attendees, $expire );
				}
			}

			/**
			 * Filters the return data for event attendees.
			 *
			 * @since 4.4
			 *
			 * @param array $attendees Array of event attendees.
			 * @param int $post_id ID of parent "event" post
			 */
			return apply_filters( 'tribe_tickets_event_attendees', $attendees, $post_id );
		}

		/**
		 * Returns an array of attendees for the specified event, in relation to
		 * this ticketing provider.
		 *
		 * Implementation note: this is just a public wrapper around the get_attendees() method.
		 * The reason we don't simply make that same method public is to avoid breakages in other
		 * ticket provider plugins which have already implemented that method with protected
		 * accessibility.
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return array
		 */
		public function get_attendees_array( $post_id ) {
			return $this->get_attendees_by_post_id( $post_id );
		}

		/**
		 * Returns the total number of attendees for an event (regardless of provider).
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return int
		 */
		public static function get_event_attendees_count( $post_id ) {
			$attendees = self::get_event_attendees( $post_id );
			return count( $attendees );
		}

		/**
		 * Returns all tickets for an event (all providers are queried for this information).
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return array
		 */
		public static function get_all_event_tickets( $post_id ) {
			$tickets = array();

			$modules = self::modules();

			foreach ( $modules as $class => $module ) {
				$obj     = call_user_func( array( $class, 'get_instance' ) );
				$tickets = array_merge( $tickets, $obj->get_tickets( $post_id ) );
			}

			return $tickets;
		}

		/**
		 * Tests to see if the provided object/ID functions as a ticket for the event
		 * and returns the corresponding event if so (or else boolean false).
		 *
		 * All registered providers are asked to perform this test.
		 *
		 * @param object|int $possible_ticket
		 * @return bool
		 */
		public static function find_matching_event( $possible_ticket ) {
			foreach ( self::modules() as $class => $module ) {
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
		 * @param int $post_id ID of parent "event" post
		 * @return mixed
		 */
		final public static function get_event_checkedin_attendees_count( $post_id ) {
			$checkedin = self::get_event_attendees( $post_id );

			return array_reduce( $checkedin, array( 'Tribe__Tickets__Tickets', '_checkedin_attendees_array_filter' ), 0 );
		}

		/**
		 * Internal function to use as a callback for array_reduce in
		 * get_event_checkedin_attendees_count. It increments the counter
		 * if the attendee is checked-in.
		 *
		 * @static
		 *
		 * @param int $result
		 * @param array $item
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
			foreach ( self::modules() as $class => $module ) {
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
		 * @param string $module class name of module
		 * @return bool
		 */
		private function module_is_valid( $module ) {
			return array_key_exists( $module, self::modules() );
		}

		/**
		 * Echos the class for the <tr> in the tickets list admin
		 */
		protected function tr_class() {
			echo 'ticket_advanced_' . sanitize_html_class( $this->class_name );
		}

		/**
		 * Generates a set of radio buttons listing the available global stock mode options.
		 *
		 * @param string (empty string) $current_option
		 * @return string
		 */
		protected function global_stock_mode_selector( $current_option = '' ) {
			$output = "<fieldset id='ticket_global_stock' class='input_block' >";
			$output .= "<legend class='ticket_form_label'>Capacity:</legend>";

			// Default to using own stock unless the user explicitly specifies otherwise (important
			// to avoid assuming global stock mode if global stock is enabled/disabled accidentally etc)
			if ( empty( $current_option ) ) {
				$current_option = Tribe__Tickets__Global_Stock::OWN_STOCK_MODE;
			}

			foreach ( $this->global_stock_mode_options() as $identifier => $name ) {
				$output .= '<label for="' . esc_attr( $identifier ) . '" class="ticket_field"><input type="radio" id="' . esc_attr( $identifier ) . '" class=" name="ticket_global_stock" value="' . esc_attr( $identifier ) . '" ' . selected( $identifier === $current_option ) . '> ' . esc_html( $name ) . " </label>\n";
			}

			return $output;
		}

		/**
		 * Returns an array of standard stock mode options that can be
		 * reused by implementations.
		 *
		 * Format is: ['identifier' => 'Localized name', ... ]
		 *
		 * @return array
		 */
		protected function global_stock_mode_options() {
			return array(
				Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE => __( 'Shared capacity with other tickets', 'event-tickets' ),
				Tribe__Tickets__Global_Stock::OWN_STOCK_MODE    => __( 'Set capacity for this ticket only', 'event-tickets' ),
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
		 * Check and set global capacity options for the "event" post
		 *
		 * @return object ajax success object
		 */
		public function edit_global_capacity_level() {
			$capacity = tribe_get_request_var( 'capacity' );
			$post_id  = tribe_get_request_var( 'post_ID' );

			if ( empty( $post_id ) ) {
				return $this->ajax_error( 'Missing required post ID.' );
			}

			if ( empty( $capacity ) ) {
				return $this->ajax_error( 'Missing required capacity.' );
			}

			if ( ! is_numeric( $capacity ) ) {
				if ( 'unlimited' !== strtolower( trim( $capacity ) ) ) {
					return $this->ajax_error( 'Bad capacity data.' );
				}

				$capacity = -1;
			}

			// We ensure that GLOBAL_STOCK_ENABLED is set as well since we've removed the checkbox
			$meta = update_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
			$meta = update_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL, $capacity );

			return $this->ajax_ok( 'Global capacity updated. New level set to ' . $capacity );
		}

		/**
		 * Returns Ticket and RSVP Count for an Event
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return array
		 */
		public static function get_ticket_counts( $post_id ) {
			$tickets = self::get_all_event_tickets( $post_id );

			// if no tickets or rsvp return empty array
			if ( ! $tickets ) {
				return array();
			}

			/**
			 * This order is important so that tickets overwrite RSVP on
			 * the Buy Now Button on the front-end
			 */
			$types['rsvp']    = array(
				'count'     => 0,
				'stock'     => 0,
				'unlimited' => 0,
				'available' => 0,
			);
			$types['tickets'] = array(
				'count'     => 0, // count of tickets currently for sale
				'stock'     => 0, // current stock of tickets available for sale
				'global'    => 0, // global stock ticket
				'unlimited' => 0, // unlimited stock tickets
				'available' => 0, // are tickets available for sale right now
			);

			foreach ( $tickets as $ticket ) {
				// If a ticket is not current for sale do not count it
				if ( ! tribe_events_ticket_is_on_sale( $ticket ) ) {
					continue;
				}

				// if ticket and not rsvp add to ticket array
				if ( 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
					$types['tickets']['count'] ++;

					$global_stock_mode = $ticket->global_stock_mode();

					if ( $global_stock_mode === Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE && 0 === $types['tickets']['global'] ) {
						$types['tickets']['global'] ++;
					} elseif ( $global_stock_mode === Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE && 1 === $types['tickets']['global'] ) {
						continue;
					}

					if ( Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $global_stock_mode ) {
						continue;
					}

					$stock_level = Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $global_stock_mode ? $ticket->global_stock_cap : $ticket->stock;

					$types['tickets']['stock'] += $stock_level;

					if ( 0 !== $types['tickets']['stock'] ) {
						$types['tickets']['available'] ++;
					}

					if ( ! $ticket->manage_stock() ) {
						$types['tickets']['unlimited'] ++;
						$types['tickets']['available'] ++;
					}
				} else {
					$types['rsvp']['count'] ++;

					$types['rsvp']['stock'] += $ticket->stock;
					if ( 0 !== $types['rsvp']['stock'] ) {
						$types['rsvp']['available'] ++;
					}

					if ( ! $ticket->manage_stock() ) {
						$types['rsvp']['unlimited'] ++;
						$types['rsvp']['available'] ++;
					}
				}
			}

			$global_stock = new Tribe__Tickets__Global_Stock( $post_id );
			$global_stock = $global_stock->is_enabled() ? $global_stock->get_stock_level() : 0;

			$types['tickets']['available'] += $global_stock;
			$types['tickets']['stock'] += $global_stock;

			return $types;
		}

		/**
		 * Takes any global stock data and makes it available via a wp_localize_script() call.
		 */
		public static function enqueue_frontend_stock_data() {
			$data = array(
				'tickets' => array(),
				'events'  => array(),
			);

			foreach ( self::$frontend_ticket_data as $ticket ) {
				/**
				 * @param Tribe__Tickets__Ticket_Object $ticket
				 */
				$post_id = $ticket->get_event()->ID;
				$global_stock = new Tribe__Tickets__Global_Stock( $post_id );
				$stock_mode = $ticket->global_stock_mode();

				$data['tickets'][ $ticket->ID ] = array(
					'event_id' => $post_id,
					'mode'     => $stock_mode,
				);

				if ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $stock_mode ) {
					$data['tickets'][ $ticket->ID ]['cap'] = $ticket->global_stock_cap();
				}

				if (
					Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === $stock_mode
					&& $ticket->managing_stock()
				) {
					$data['tickets'][ $ticket->ID ]['stock'] = $ticket->stock();
				}

				$data['events'][ $post_id ] = array(
					'stock' => $global_stock->get_stock_level(),
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
			/**
			 * Filters the available tickets modules
			 *
			 * @param string[] ticket modules
			 */
			return apply_filters( 'tribe_tickets_get_modules', self::$active_modules );
		}

		/**
		 * Returns the class name of the default module/provider.
		 *
		 * @since TBD
		 *
		 * @return string
		 */
		public static function get_default_module() {
			$modules = array_keys( self::modules() );

			/**
			 * Filters the default tickets module class name
			 *
			 * @since TBD
			 *
			 * @param string default ticket module class name
			 * @param array array of ticket module class names
			 */
			return apply_filters( 'tribe_tickets_get_default_module', self::$default_module, $modules );
		}

		/**
		 * Get all the tickets for an event. Queries all active modules/providers.
		 *
		 * @static
		 *
		 * @param int $post_id ID of parent "event" post
		 * @return array
		 */
		final public static function get_event_tickets( $post_id ) {

			$tickets = array();

			foreach ( self::modules() as $class => $module ) {
				$obj     = call_user_func( array( $class, 'get_instance' ) );
				$tickets = array_merge( $tickets, $obj->get_tickets( $post_id ) );
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
		 * @param mixed $data
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
		 * @param array $tickets
		 * @return string
		 */
		public function generate_tickets_email_content( $tickets ) {
			return tribe_tickets_get_template_part( 'tickets/email', null, array( 'tickets' => $tickets ), false );
		}

		/**
		 * Gets the view from the plugin's folder, or from the user's theme if found.
		 *
		 * @param string $template
		 * @return mixed|void
		 */
		public function getTemplateHierarchy( $template ) {

			if ( substr( $template, - 4 ) != '.php' ) {
				$template .= '.php';
			}

			if ( $theme_file = locate_template( array( 'tribe-events/' . $template ) ) ) {
				$file = $theme_file;
			} else {
				$file = $this->plugin_path . 'src/views/' . $template;
			}

			return apply_filters( 'tribe_events_tickets_template_' . $template, $file );
		}

		/**
		 * Queries ticketing providers to establish the range of tickets/pricepoints for the specified
		 * event and ensures those costs are included in the $costs array.
		 *
		 * @param  array $prices
		 * @param  int   $post_id
		 * @return array
		 */
		public function get_ticket_prices( array $prices, $post_id ) {
			// Iterate through all tickets from all providers
			foreach ( self::get_all_event_tickets( $post_id ) as $ticket ) {
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
		 *
		 * @see Tribe__Tickets__Tickets_Handler::user_can()
		 *
		 * @param  string $generic_cap
		 * @param  int    $attendee_id
		 * @return boolean
		 */
		public function user_can( $generic_cap, $attendee_id ) {
			$post_id = $this->get_event_id_from_attendee_id( $attendee_id );

			if ( empty( $post_id ) ) {
				return false;
			}

			return Tribe__Tickets__Tickets_Handler::instance()->user_can( $generic_cap, $post_id );
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

			$post_id = get_post_meta( $attendee_id, $attendee_event_key, true );

			if ( empty( $post_id ) ) {
				return false;
			}

			return (int) $post_id;
		}

		/**
		 * Given a valid order ID, returns a single event ID it relates to or else boolean false
		 * if it cannot be determined.
		 *
		 * @see Use tribe_tickets_get_event_ids() to return an array of all event ids for an order
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
				switch ( $this->class_name ) {
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
				switch ( $this->class_name ) {
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
				switch ( $this->class_name ) {
					case 'Tribe__Events__Tickets__Woo__Main':   return '_tribe_wooticket_event';   break;
					case 'Tribe__Events__Tickets__EDD__Main':   return '_tribe_eddticket_event';   break;
					case 'Tribe__Events__Tickets__Shopp__Main': return '_tribe_shoppticket_event'; break;
					case 'Tribe__Events__Tickets__Wpec__Main':  return '_tribe_wpecticket_event';  break;
				}
			}

			return (string) $attendee_event_key;
		}

		/**
		 * Process the attendee meta into an array with value, slug, and label
		 *
		 * @param int $product_id
		 * @param array $meta
		 * @return array
		 */
		public function process_attendee_meta( $product_id, $meta ) {

			$meta_vals = array();

			if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
				return $meta_vals;
			}

			$meta_field_objs = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $product_id );

			foreach ( $meta_field_objs as $field ) {
				$value = null;

				if ( 'checkbox' === $field->type ) {
					$field_prefix = $field->slug . '_';
					$value        = array();

					foreach ( $meta as $full_key => $check_value ) {
						if ( 0 === strpos( $full_key, $field_prefix ) ) {
							$short_key           = substr( $full_key, strlen( $field_prefix ) );
							$value[ $short_key ] = $check_value;
						}
					}

					if ( empty( $value ) ) {
						$value = null;
					}
				} elseif ( isset( $meta[ $field->slug ] ) ) {
					$value = $meta[ $field->slug ];
				}

				$meta_vals[ $field->slug ] = array(
					'slug'  => $field->slug,
					'label' => $field->label,
					'value' => $value,
				);
			}

			return $meta_vals;

		}

		/**
		 * Returns the meta key used to link ticket types with the base event.
		 *
		 * If the meta key cannot be determined the returned string will be empty.
		 * Subclasses can override this if they use a key other than 'event_key'
		 * for this purpose.
		 *
		 * @internal
		 *
		 * @return string
		 */
		public function get_event_key() {
			if ( property_exists( $this, 'event_key' ) ) {
				// EDD module uses a static event_key so we need to check for it or we'll fatal
				$prop = new ReflectionProperty( $this, 'event_key' );
				if ( $prop->isStatic() ) {
					return $prop->get_value();
				}

				return $this->event_key;
			}

			return '';
		}

		/**
		 * Returns an availability slug based on all tickets in the provided collection
		 *
		 * The availability slug is used for CSS class names and filter helper strings
		 *
		 * @since 4.2
		 *
		 * @param array $tickets Collection of tickets
		 * @param string $datetime Datetime string
		 * @return string
		 */
		public function get_availability_slug_by_collection( $tickets, $datetime = null ) {
			if ( ! $tickets ) {
				return;
			}

			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} elseif ( $datetime ) {
				$timestamp = strtotime( $datetime );
			} else {
				$timestamp = current_time( 'timestamp' );
			}

			$collection_availability_slug = 'available';
			$tickets_available = false;
			$slugs = array();

			foreach ( $tickets as $ticket ) {
				$availability_slug = $ticket->availability_slug( $timestamp );

				// if any ticket is available for this event, consider the availability slug as 'available'
				if ( 'available' === $availability_slug ) {
					// reset the collected slugs to "available" only
					$slugs = array( 'available' );
					break;
				}

				// track unique availability slugs
				if ( ! in_array( $availability_slug, $slugs ) ) {
					$slugs[] = $availability_slug;
				}
			}

			if ( 1 === count( $slugs ) ) {
				$collection_availability_slug = $slugs[0];
			} else {
				$collection_availability_slug = 'availability-mixed';
			}

			/**
			 * Filters the availability slug for a collection of tickets
			 *
			 * @param string Availability slug
			 * @param array Collection of tickets
			 * @param string Datetime string
			 */
			return apply_filters( 'event_tickets_availability_slug_by_collection', $collection_availability_slug, $tickets, $datetime );
		}

		/**
		 * Returns a tickets unavailable message based on the availability slug of a collection of tickets
		 *
		 * @since 4.2
		 *
		 * @param array $tickets Collection of tickets
		 * @return string
		 */
		public function get_tickets_unavailable_message( $tickets ) {

			$availability_slug = $this->get_availability_slug_by_collection( $tickets );
			$message           = null;
			$post_type = get_post_type();

			if ( 'tribe_events' == $post_type && function_exists( 'tribe_is_past_event' ) && tribe_is_past_event() ) {
				$events_label_singular_lowercase = tribe_get_event_label_singular_lowercase();
				$message = sprintf( esc_html__( 'Tickets are not available as this %s has passed.', 'event-tickets' ), $events_label_singular_lowercase );
			} elseif ( 'availability-future' === $availability_slug ) {
				$message = __( 'Tickets are not yet available.', 'event-tickets' );
			} elseif ( 'availability-past' === $availability_slug ) {
				$message = __( 'Tickets are no longer available.', 'event-tickets' );
			} elseif ( 'availability-mixed' === $availability_slug ) {
				$message = __( 'There are no tickets available at this time.', 'event-tickets' );
			}

			/**
			 * Filters the unavailability message for a ticket collection
			 *
			 * @param string Unavailability message
			 * @param array Collection of tickets
			 */
			$message = apply_filters( 'event_tickets_unvailable_message', $message, $tickets );

			return $message;
		}

		/**
		 * Indicates that, from an individual ticket provider's perspective, the only tickets for the
		 * event are currently unavailable and unless a different ticket provider reports differently
		 * the "tickets unavailable" message should be displayed.
		 *
		 * @param array $tickets
		 * @param int $post_id ID of parent "event" post (defaults to the current post)
		 */
		public function maybe_show_tickets_unavailable_message( $tickets, $post_id = null ) {
			if ( null === $post_id ) {
				$post_id = get_the_ID();
			}

			$unavailable_tickets = self::$currently_unavailable_tickets;

			$existing_tickets = ! empty( $unavailable_tickets[ (int) $post_id ] )
				? $unavailable_tickets[ (int) $post_id ]
				: array();

			self::$currently_unavailable_tickets[ (int) $post_id ] = array_merge( $existing_tickets, $tickets );
		}

		/**
		 * Indicates that, from an individual ticket provider's perspective, the event does have some
		 * currently available tickets and so the "tickets unavailable" message should probably not
		 * be displayed.
		 *
		 * @param null $post_id
		 */
		public function do_not_show_tickets_unavailable_message( $post_id = null ) {
			if ( null === $post_id ) {
				$post_id = get_the_ID();
			}

			self::$posts_with_available_tickets[] = (int) $post_id;
		}

		/**
		 * If appropriate, displayed a "tickets unavailable" message.
		 */
		public function show_tickets_unavailable_message() {
			$post_id = (int) get_the_ID();

			// So long as at least one ticket provider has tickets available, do not show an unavailability message
			if ( in_array( $post_id, self::$posts_with_available_tickets ) ) {
				return;
			}

			// Bail if no ticket providers reported that all their tickets for the event were unavailable
			$unavailable_tickets = self::$currently_unavailable_tickets[ $post_id ];
			if ( empty( $unavailable_tickets ) ) {
				return;
			}

			// Prepare the message
			$message = '<div class="tickets-unavailable">'
				. $this->get_tickets_unavailable_message( self::$currently_unavailable_tickets[ $post_id ] )
				. '</div>';

			/**
			 * Sets the tickets unavailable message.
			 *
			 * @param string $message
			 * @param int    $post_id
			 * @param array  $unavailable_event_tickets
			 */
			echo apply_filters( 'tribe_tickets_unavailable_message', $message, $post_id, self::$currently_unavailable_tickets[ $post_id ] );

			// Remove the record of unavailable tickets to avoid duplicate messages being rendered for the same event
			unset( self::$currently_unavailable_tickets[ $post_id ] );
		}

		/**
		 * Takes care of adding a "tickets unavailable" message by injecting it into the post content
		 * (where the template settings require such an approach).
		 *
		 * @param string $content
		 * @return string
		 */
		public function show_tickets_unavailable_message_in_content( $content ) {
			if ( ! $this->should_inject_ticket_form_into_post_content() ) {
				return $content;
			}

			ob_start();
			$this->show_tickets_unavailable_message();
			$form = ob_get_clean();

			$content .= $form;

			return $content;
		}
		// end Helpers

		/**
		 * Associates an attendee record with a user, typically the purchaser.
		 *
		 * The $user_id param is optional and when not provided it will default to the current
		 * user ID.
		 *
		 *
		 * @param int $attendee_id
		 * @param int $user_id
		 */
		protected function record_attendee_user_id( $attendee_id, $user_id = null ) {
			if ( null === $user_id ) {
				$user_id = get_current_user_id();
			}

			update_post_meta( $attendee_id, self::ATTENDEE_USER_ID, (int) $user_id );
		}

		public function front_end_tickets_form_in_content( $content ) {
			if ( ! $this->should_inject_ticket_form_into_post_content() ) {
				return $content;
			}

			ob_start();
			$this->front_end_tickets_form( $content );
			$form = ob_get_clean();

			$content .= $form;

			return $content;
		}

		/**
		 * Determines if this is a suitable opportunity to inject ticket form content into a post.
		 * Expects to run within "the_content".
		 *
		 * @return bool
		 */
		protected function should_inject_ticket_form_into_post_content() {
			global $post;

			// Prevents firing more then it needs too outside of the loop
			$in_the_loop = isset( $GLOBALS['wp_query']->in_the_loop ) && $GLOBALS['wp_query']->in_the_loop;

			if ( is_admin() || ! $in_the_loop ) {
				return false;
			}

			// if this isn't a post for some reason, bail
			if ( ! $post instanceof WP_Post ) {
				return false;
			}

			// if this isn't a supported post type, bail
			if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
				return false;
			}

			// if this is a tribe_events post, let's bail because those post types are handled with a different hook
			if ( 'tribe_events' === $post->post_type ) {
				return false;
			}

			// if there aren't any tickets, bail
			$tickets = $this->get_tickets( $post->ID );
			if ( empty( $tickets ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Indicates if the user must be logged in in order to obtain tickets.
		 *
		 * This should be regarded as an abstract method to be overridden by subclasses:
		 * the reason it is not formally declared as abstract is to avoid breakages upon
		 * update (for example, where Event Tickets is updated first but a dependent plugin
		 * not yet implementing the abstract method remains at an earlier version).
		 *
		 * @return bool
		 */
		protected function login_required() {
			return false;
		}

		/**
		 * Provides a URL that can be used to direct users to the login form.
		 *
		 * @return string
		 */
		public static function get_login_url() {
			$post_id   = get_the_ID();
			$login_url = get_site_url( null, 'wp-login.php' );

			if ( $post_id ) {
				$login_url = add_query_arg( 'redirect_to', get_permalink( $post_id ), $login_url );
			}

			/**
			 * Provides an opportunity to modify the login URL used within frontend
			 * ticket forms (typically when they need to login before they can proceed).
			 *
			 * @param string $login_url
			 */
			return apply_filters( 'tribe_tickets_ticket_login_url', $login_url );
		}

		/**
		 * @param bool $operation_did_complete
		 */
		private function maybe_update_attendees_cache( $operation_did_complete ) {
			if ( $operation_did_complete && ! empty( $_POST['event_ID'] ) ) {
				$post_transient = Tribe__Post_Transient::instance();
				$post_transient->delete( $_POST['event_ID'], self::ATTENDEES_CACHE );
			}
		}

		/**
		 * Returns the action tag that should be used to print the front-end ticket form.
		 *
		 * This value is set in the Events > Settings > Tickets tab and is distinct between RSVP
		 * tickets and commerce provided tickets.
		 *
		 * @return string
		 */
		protected function get_ticket_form_hook() {
			if ( $this instanceof Tribe__Tickets__RSVP ) {
				$ticket_form_hook = Tribe__Settings_Manager::get_option( 'ticket-rsvp-form-location',
					'tribe_events_single_event_after_the_meta' );

				/**
				 * Filters the position of the RSVP tickets form.
				 *
				 * While this setting can be handled using the Events > Settings > Tickets > "Location of RSVP form"
				 * setting this filter allows developers to override the general setting in particular cases.
				 * Returning an empty value here will prevent the ticket form from printing on the page.
				 *
				 * @param string                  $ticket_form_hook The set action tag to print front-end RSVP tickets form.
				 * @param Tribe__Tickets__Tickets $this             The current instance of the class that's hooking its front-end ticket form.
				 */
				$ticket_form_hook = apply_filters( 'tribe_tickets_rsvp_tickets_form_hook', $ticket_form_hook, $this );
			} else {
				$ticket_form_hook = Tribe__Settings_Manager::get_option( 'ticket-commerce-form-location',
					'tribe_events_single_event_after_the_meta' );

				/**
				 * Filters the position of the commerce-provided tickets form.
				 *
				 * While this setting can be handled using the Events > Settings > Tickets > "Location of Tickets form"
				 * setting this filter allows developers to override the general setting in particular cases.
				 * Returning an empty value here will prevent the ticket form from printing on the page.
				 *
				 * @param string                  $ticket_form_hook The set action tag to print front-end commerce tickets form.
				 * @param Tribe__Tickets__Tickets $this             The current instance of the class that's hooking its front-end ticket form.
				 */
				$ticket_form_hook = apply_filters( 'tribe_tickets_commerce_tickets_form_hook', $ticket_form_hook, $this );
			}

			return $ticket_form_hook;
		}
	}
}
