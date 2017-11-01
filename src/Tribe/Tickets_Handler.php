<?php

/**
 * Handles most actions related to a Ticket or Multiple ones
 */
class Tribe__Tickets__Tickets_Handler {
	/**
	 * Singleton instance of this class
	 *
	 * @var Tribe__Tickets__Tickets_Handler
	 * @static
	 */
	protected static $instance;

	/**
	 * Path to this plugin
	 * @var string
	 */
	protected $path;

	/**
	 * Post Meta key for the ticket header
	 * @var string
	 */
	protected $image_header_field = '_tribe_ticket_header';

	/**
	 * Post Meta key for the ticket order
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $tickets_order_field = '_tribe_tickets_order';

	/**
	 * Post Meta key for event ecommerce provider
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $key_provider_field = '_tribe_default_ticket_provider';

	/**
	 * Post meta key for the ticket capacty
	 *
	 * @since  TBD
	 *
	 * @var    string
	 */
	public $key_capacity = '_tribe_ticket_capacity';

	/**
	 * Post meta key for the ticket start date
	 *
	 * @since  TBD
	 *
	 * @var    string
	 */
	public $key_start_date = '_ticket_start_date';

	/**
	 * Post meta key for the ticket end date
	 *
	 * @since  TBD
	 *
	 * @var    string
	 */
	public $key_end_date = '_ticket_end_date';

	/**
	 * Post meta key for the manual updated meta keys
	 *
	 * @since  TBD
	 *
	 * @var    string
	 */
	public $key_manual_updated = '_tribe_ticket_manual_updated';

	/**
	 * Meta data key we store show_description under
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $key_show_description = '_tribe_ticket_show_description';

	/**
	 * Slug of the admin page for attendees
	 * @var string
	 */
	public static $attendees_slug = 'tickets-attendees';

	/**
	 * @var bool
	 */
	protected $should_render_title = true;

	/**
	 * Hook of the admin page for attendees
	 * @var
	 */
	private $attendees_page;

	/**
	 * WP_Post_List children for Attendees
	 * @var Tribe__Tickets__Attendees_Table
	 */
	private $attendees_table;

	/**
	 * String to represent unlimited tickets
	 * translated in the constructor
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $unlimited_term = 'Unlimited';

	/**
	 *    Class constructor.
	 */
	public function __construct() {
		$main = Tribe__Tickets__Main::instance();
		$this->unlimited_term = __( 'Unlimited', 'event-tickets' );

		foreach ( $main->post_types() as $post_type ) {
			add_action( 'save_post_' . $post_type, array( $this, 'save_image_header' ) );
			add_action( 'save_post_' . $post_type, array( $this, 'save_order' ) );
		}

		add_action( 'admin_menu', array( $this, 'attendees_page_register' ) );
		add_action( 'tribe_tickets_attendees_event_details_list_top', array( $this, 'event_details_top' ), 20 );
		add_action( 'tribe_tickets_plus_report_event_details_list_top', array( $this, 'event_details_top' ), 20 );
		add_action( 'tribe_tickets_attendees_event_details_list_top', array( $this, 'event_action_links' ), 25 );
		add_action( 'tribe_tickets_plus_report_event_details_list_top', array( $this, 'event_action_links' ), 25 );
		add_action( 'tribe_events_tickets_attendees_totals_top', array( $this, 'print_checkedin_totals' ), 0 );
		add_action( 'wp_ajax_tribe-ticket-save-settings', array( $this, 'ajax_handler_save_settings' ) );

		add_filter( 'post_row_actions', array( $this, 'attendees_row_action' ) );
		add_filter( 'page_row_actions', array( $this, 'attendees_row_action' ) );

		add_filter( 'get_post_metadata', array( $this, 'filter_capacity_support' ), 15, 3 );
		add_filter( 'updated_postmeta', array( $this, 'update_shared_tickets_capacity' ), 15, 4 );

		add_filter( 'updated_postmeta', array( $this, 'update_meta_date' ), 15, 4 );
		add_action( 'wp_insert_post', array( $this, 'update_start_date' ), 15, 3 );

		$this->path = trailingslashit(  dirname( dirname( dirname( __FILE__ ) ) ) );
	}

	/**
	 * On updating a few meta keys we flag that it was manually updated so we can do
	 * fancy matching for the updating of the event start and end date
	 *
	 * @since  TBD
	 *
	 * @param  int     $meta_id         MID
	 * @param  int     $object_id       Which Post we are dealing with
	 * @param  string  $meta_key        Which meta key we are fetching
	 * @param  int     $event_capacity  To which value the event Capacity was update to
	 *
	 * @return int
	 */
	public function flag_manual_update( $meta_id, $object_id, $meta_key, $date ) {
		$keys = array(
			$this->key_start_date,
			$this->key_end_date,
		);

		// Bail on not Date meta updates
		if ( ! in_array( $meta_key, $keys ) ) {
			return;
		}

		$updated = get_post_meta( $object_id, $this->key_manual_updated );

		// Bail if it was ever manually updated
		if ( in_array( $meta_key, $updated ) ) {
			return;
		}

		// the updated metakey to the list
		add_post_meta( $object_id, $this->key_manual_updated, $meta_key );

		return;
	}

	/**
	 * Verify if we have Manual Changes for a given Meta Key
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket  Which ticket/post we are dealing with here
	 * @param  string|null  $for     If we are looking for one specific key or any
	 *
	 * @return boolean
	 */
	public function has_manual_update( $ticket, $for = null ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( ! $ticket instanceof WP_Post ) {
			return false;
		}

		$updated = get_post_meta( $ticket->ID, $this->key_manual_updated );

		if ( is_null( $for ) ) {
			return ! empty( $updated );
		}

		return in_array( $for, $updated );
	}

	/**
	 * Allow us to Toggle flaging the update of Date Meta
	 *
	 * @since   TBD
	 *
	 * @param   boolean  $toggle  Should activate or not?
	 *
	 * @return  void
	 */
	public function toggle_manual_update_flag( $toggle = true ) {
		if ( true === (bool) $toggle ) {
			add_filter( 'updated_postmeta', array( $this, 'flag_manual_update' ), 15, 4 );
		} else {
			remove_filter( 'updated_postmeta', array( $this, 'flag_manual_update' ), 15 );
		}
	}

	/**
	 * On update of the Event End date we update the ticket end date
	 * if it wasn't manually updated
	 *
	 * @since  TBD
	 *
	 * @param  int     $meta_id    MID
	 * @param  int     $object_id  Which Post we are dealing with
	 * @param  string  $meta_key   Which meta key we are fetching
	 * @param  string  $date       Value save on the DB
	 *
	 * @return boolean
	 */
	public function update_meta_date( $meta_id, $object_id, $meta_key, $date ) {
		$meta_map = array(
			'_EventEndDate' => $this->key_end_date,
		);

		// Bail when it's not on the Map Meta
		if ( ! isset( $meta_map[ $meta_key ] ) ) {
			return false;
		}

		$event_types = Tribe__Tickets__Main::instance()->post_types();
		$post_type = get_post_type( $object_id );

		// Bail on non event like post type
		if ( ! in_array( $post_type, $event_types ) ) {
			return false;
		}

		$update_meta = $meta_map[ $meta_key ];
		$tickets = $this->get_tickets_ids( $object_id );

		foreach ( $tickets as $ticket ) {
			// Skip tickets with manual updates to that meta
			if ( $this->has_manual_update( $ticket, $update_meta ) ) {
				continue;
			}

			update_post_meta( $ticket, $update_meta, $date );
		}

		return true;
	}

	/**
	 * Updates the Start date of all non-modified tickets when an Ticket supported Post is saved
	 *
	 * @since  TBD
	 *
	 * @param  int      $post_id  Which post we are updating here
	 * @param  WP_Post  $post     Object of the current post updating
	 * @param  boolean  $update   If we are updating or creating a post
	 *
	 * @return boolean
	 */
	public function update_start_date( $post_id, $post, $update ) {
		// Bail on Revision
		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}

		// Bail if the CPT doens't accept tickets
		if ( ! tribe_tickets_post_type_enabled( $post->post_type ) ) {
			return false;
		}

		$update_meta = $this->key_start_date;
		$tickets = $this->get_tickets_ids( $post_id );

		foreach ( $tickets as $ticket ) {
			// Skip tickets with manual updates to that meta
			if ( $this->has_manual_update( $ticket, $update_meta ) ) {
				continue;
			}

			// 30 min
			$round = 30;
			if ( class_exists( 'Tribe__Events__Main' ) ) {
				$round = (int) tribe( 'tec.admin.event-meta-box' )->get_timepicker_step( 'start' );
			}
			// Convert to seconds
			$round *= MINUTE_IN_SECONDS;

			$date = strtotime( $post->post_date );
			$date = round( $date / $round ) * $round;
			$date = date( Tribe__Date_Utils::DBDATETIMEFORMAT, $date );

			update_post_meta( $ticket, $update_meta, $date );
		}

		return true;
	}

	/**
	 * Gets the Tickets from a Post
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $post
	 * @return array
	 */
	public function get_tickets_ids( $post = null ) {
		$modules = Tribe__Tickets__Tickets::modules();
		$args = array(
			'post_type'      => array(),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'publish',
			'order_by'       => 'menu_order',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'OR',
			),
		);

		foreach ( $modules as $provider_class => $name ) {
			$provider = call_user_func( array( $provider_class, 'get_instance' ) );
			$module_args = $provider->get_tickets_query_args( $post );

			$args['post_type'] = array_merge( $args['post_type'], $module_args['post_type'] );
			$args['meta_query'] = array_merge( $args['meta_query'], $module_args['meta_query'] );
		}

		$query = new WP_Query( $args );

		return $query->posts;
	}

	/**
	 * On update of the Event Capacity we will update all shared capacity Stock to match
	 *
	 * @since  TBD
	 *
	 * @param  int     $meta_id         MID
	 * @param  int     $object_id       Which Post we are dealing with
	 * @param  string  $meta_key        Which meta key we are fetching
	 * @param  int     $event_capacity  To which value the event Capacity was update to
	 *
	 * @return int
	 */
	public function update_shared_tickets_capacity( $meta_id, $object_id, $meta_key, $event_capacity ) {
		// Bail on non-capacity
		if ( $this->key_capacity !== $meta_key ) {
			return false;
		}

		$event_types = Tribe__Tickets__Main::instance()->post_types();

		// Bail on non event like post type
		if ( ! in_array( get_post_type( $object_id ), $event_types ) ) {
			return false;
		}

		$completes = array();
		$tickets = $this->get_tickets_ids( $object_id );

		foreach ( $tickets as $ticket ) {
			$mode = get_post_meta( $ticket, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true );

			if ( Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE !== $mode ) {
				continue;
			}

			$totals = $this->get_ticket_totals( $ticket );
			$completes[] = $complete = $totals['pending'] + $totals['sold'];

			$stock = $event_capacity - $complete;
			update_post_meta( $ticket, '_stock', $stock );
		}

		// Make sure we are updating the Global Stock when we update it's capacity
		$shared_stock = new Tribe__Tickets__Global_Stock( $object_id );
		$shared_stock_level = $event_capacity - array_sum( $completes );
		$shared_stock->set_stock_level( $shared_stock_level );
	}

	/**
	 * Allows us to create capacity when none is defined for an older ticket
	 * It will define the new Capacity based on Stock + Tickets Pending + Tickets Sold
	 *
	 * Important to note that we cannot use `get_ticket()` or `new Ticket_Object` in here
	 * due to triggering of a Infinite loop
	 *
	 * @since  TBD
	 *
	 * @param  mixed   $value      Previous value set
	 * @param  int     $object_id  Which Post we are dealing with
	 * @param  string  $meta_key   Which meta key we are fetching
	 *
	 * @return int
	 */
	public function filter_capacity_support( $value, $object_id, $meta_key ) {
		// Something has been already set
		if ( ! is_null( $value ) ) {
			return $value;
		}

		// We only care about Capacity Key
		if ( $this->key_capacity !== $meta_key ) {
			return $value;
		}

		// We remove the Check to allow a fair usage of `metadata_exists`
		remove_filter( 'get_post_metadata', array( $this, 'filter_capacity_support' ), 15 );

		// Bail when we already have the MetaKey saved
		if ( metadata_exists( 'post', $object_id, $meta_key ) ) {
			return get_post_meta( $object_id, $meta_key, true );
		}

		// Bail when we don't have a legacy version
		if ( ! tribe( 'tickets.version' )->is_legacy( $object_id ) ) {
			return $value;
		}

		$post_type = get_post_type( $object_id );
		$global_stock = new Tribe__Tickets__Global_Stock( $object_id );

		if ( tribe_tickets_post_type_enabled( $post_type ) ) {
			$capacity = $global_stock->get_stock_level();
			$tickets  = $this->get_tickets_ids( $object_id );

			foreach ( $tickets as $ticket ) {
				if ( $this->has_shared_capacity( $ticket ) ) {
					continue;
				}

				$totals = $this->get_ticket_totals( $ticket );

				$capacity += $totals['sold'] + $totals['pending'];
			}
		} else {
			// In here we deal with Tickets migration from legacy
			$is_local_capped = false;
			$ticket_local_cap = trim( get_post_meta( $object_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true ) );
			$totals = $this->get_ticket_totals( $object_id );

			if (
				! empty( $ticket_local_cap )
				&& is_numeric( $ticket_local_cap )
				&& 0 !== $ticket_local_cap
			) {
				$is_local_capped = true;
				$capacity = (int) $ticket_local_cap;
			} elseif ( $this->is_ticket_managing_stock( $object_id ) ) {
				$capacity = array_sum( $totals );
			} else {
				$capacity = -1;
			}

			// Fetch ticket event ID for Updating capacity on event
			$event_id = tribe_tickets_get_event_ids( $object_id );

			// It will return an array of Events
			if ( ! empty( $event_id ) ) {
				$event_id = current( $event_id );
				$event_capacity = $capacity;

				// If we had local Cap we overwrite to the event total
				if ( $is_local_capped ) {
					$event_capacity = array_sum( $totals );
				}

				update_post_meta( $event_id, $this->key_capacity, $event_capacity );
			}
		}

		$updated = update_post_meta( $object_id, $this->key_capacity, $capacity );

		// If we updated the Capacity for legacy update the version
		if ( $updated ) {
			tribe( 'tickets.version' )->update( $object_id );
		}

		// Hook it back up
		add_filter( 'get_post_metadata', array( $this, 'filter_capacity_support' ), 15, 4 );

		return $capacity;
	}

	/**
	 * Gets the Total of Stock, Sold and Pending for a given ticket
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket  Which ticket
	 *
	 * @return array
	 */
	public function get_ticket_totals( $ticket ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( ! $ticket instanceof WP_Post ) {
			return false;
		}

		$provider = tribe_tickets_get_ticket_provider( $ticket->ID );

		$totals = array(
			'stock'   => get_post_meta( $ticket->ID, '_stock', true ),
			'sold'    => 0,
			'pending' => 0,
		);

		if ( $provider instanceof Tribe__Tickets_Plus__Commerce__EDD__Main ) {
			$totals['sold']    = $provider->stock()->get_purchased_inventory( $ticket->ID, array( 'publish' ) );
			$totals['pending'] = $provider->stock()->count_incomplete_order_items( $ticket->ID );
		} elseif ( $provider instanceof Tribe__Tickets_Plus__Commerce__WooCommerce__Main ) {
			$totals['sold']    = get_post_meta( $ticket->ID, 'total_sales', true );
			$totals['pending'] = $provider->get_qty_pending( $ticket->ID, true );
		}

		$totals = array_map( 'intval', $totals );

		// Remove Pending from total
		$totals['sold'] -= $totals['pending'];

		return $totals;
	}

	/**
	 * Returns whether a ticket has unlimited capacity
	 *
	 * @since   TBD
	 *
	 * @param   int|WP_Post|object  $ticket
	 *
	 * @return  bool
	 */
	public function is_ticket_managing_stock( $ticket ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( ! $ticket instanceof WP_Post ) {
			return false;
		}

		$manage_stock = get_post_meta( $ticket->ID, '_manage_stock', true );

		return tribe_is_truthy( $manage_stock );
	}

	/**
	 * Returns whether a ticket has unlimited capacity
	 *
	 * @since   TBD
	 *
	 * @param   int|WP_Post|object  $ticket
	 *
	 * @return  bool
	 */
	public function is_unlimited_ticket( $ticket ) {
		return -1 === tribe_tickets_get_capacity( $ticket->ID );
	}

	/**
	 * Returns whether a ticket uses Shared Capacity
	 *
	 * @since   TBD
	 *
	 * @param   int|WP_Post|object  $ticket
	 *
	 * @return  bool
	 */
	public function has_shared_capacity( $ticket ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( ! $ticket instanceof WP_Post ) {
			return false;
		}

		$mode = get_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true );

		return Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $mode || Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $mode;
	}

	/**
	 * Checks if there are any unlimited tickets, optionally by stock mode or ticket type
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 * @param string (null) the stock mode we're concerned with
	 *			can be one of the following:
	 *				Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE ('global')
	 *				Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE ('capped')
	 *				Tribe__Tickets__Global_Stock::OWN_STOCK_MODE ('own')
	 * @param string (null) $provider_class the ticket provider class ex: Tribe__Tickets__RSVP
	 * @return boolean whether there is a ticket (within the provided parameters) with an unlimited stock
	 */
	public function has_unlimited_stock( $post = null, $stock_mode = null, $provider_class = null ) {
		$post_id = Tribe__Main::post_id_helper( $post );
		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post_id );

		foreach ( $tickets as $index => $ticket ) {
			// Eliminate tickets by stock mode
			if ( ! is_null( $stock_mode ) && $ticket->global_stock_mode() !== $stock_mode ) {
				unset( $tickets[ $ticket ] );
				continue;
			}

			// Eliminate tickets by provider class
			if ( ! is_null( $provider_class ) && $ticket->provider_class !== $provider_class ) {
				unset( $tickets[ $ticket ] );
				continue;
			}

			if ( $this->is_unlimited_ticket( $ticket ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the total event capacity.
	 *
	 * @since  TBD
	 *
	 * @param  int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return int|null
	 */
	public function get_total_event_capacity( $post = null ) {
		$post_id            = Tribe__Main::post_id_helper( $post );
		$has_shared_tickets = 0 !== count( $this->get_event_shared_tickets( $post_id ) );
		$total              = 0;

		if ( $has_shared_tickets ) {
			$total = tribe_tickets_get_capacity( $post_id );
		}

		// short circuit unlimited stock
		if ( -1 === $total ) {
			return $total;
		}

		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post_id );

		// Bail when we don't have Tickets
		if ( empty( $tickets ) ) {
			return $total;
		}

		foreach ( $tickets as $ticket ) {
			// Skip shared cap Tickets as it's added when we fetch the total
			if (
				Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $ticket->global_stock_mode()
				|| Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $ticket->global_stock_mode()
			) {
				continue;
			}

			$capacity = $ticket->capacity();

			if ( -1 === $capacity ) {
				$total = -1;
				break;
			}

			$total += $capacity;
		}

		return apply_filters( 'tribe_tickets_total_event_capacity', $total, $post_id );
	}

	/**
	 * Get an array list of unlimited tickets for an event.
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return array list of tickets
	 */
	public function get_event_unlimited_tickets( $post = null ) {
		$post_id     = Tribe__Main::post_id_helper( $post );
		$tickets     = Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$ticket_list = array();

		if ( empty( $tickets ) ) {
			return $ticket_list;
		}

		foreach ( $tickets as $ticket ) {
			if ( ! $this->is_unlimited_ticket( $ticket ) ) {
				continue;
			}

			$ticket_list[] = $ticket;
		}

		return $ticket_list;
	}

	/**
	 * Get an array list of independent tickets for an event.
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return array list of tickets
	 */
	public function get_event_independent_tickets( $post = null ) {
		$post_id     = Tribe__Main::post_id_helper( $post );
		$tickets     = Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$ticket_list = array();

		if ( empty( $tickets ) ) {
			return $ticket_list;
		}

		foreach ( $tickets as $ticket ) {
			if ( Tribe__Tickets__Global_Stock::OWN_STOCK_MODE != $ticket->global_stock_mode() || 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
				continue;
			}

			// Failsafe - should not include unlimited tickets
			if ( $this->is_unlimited_ticket( $ticket ) ) {
				continue;
			}

			$ticket_list[] = $ticket;
		}

		return $ticket_list;
	}

	/**
	 * Get an array list of RSVPs for an event.
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return string list of tickets
	 */
	public function get_event_rsvp_tickets( $post = null ) {
		$post_id     = Tribe__Main::post_id_helper( $post );
		$tickets     = Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$ticket_list = array();

		if ( empty( $tickets ) ) {
			return $ticket_list;
		}

		foreach ( $tickets as $ticket ) {
			if ( 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
				continue;
			}

			$ticket_list[] = $ticket;
		}

		return $ticket_list;
	}

	/**
	 * Get an array list of shared capacity tickets for an event.
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return array list of tickets
	 */
	public function get_event_shared_tickets( $post = null ) {
		$post_id     = Tribe__Main::post_id_helper( $post );
		$tickets     = Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$ticket_list = array();

		if ( empty( $tickets ) ) {
			return $ticket_list;
		}

		foreach ( $tickets as $ticket ) {
			$stock_mode = $ticket->global_stock_mode();
			if ( empty( $stock_mode ) || Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === $stock_mode ) {
				continue;
			}

			// Failsafe - should not include unlimited tickets
			if ( $this->is_unlimited_ticket( $ticket ) ) {
				continue;
			}

			$ticket_list[] = $ticket;
		}


		return $ticket_list;
	}

	/**
	 * Injects event post type
	 *
	 * @param int $event_id
	 */
	public function event_details_top( $event_id ) {
		$pto = get_post_type_object( get_post_type( $event_id ) );

		echo '
			<li class="post-type">
				<strong>' . esc_html__( 'Post type', 'event-tickets' ) . ': </strong>
				' . esc_html( $pto->label ) . '
			</li>
		';
	}

	/**
	 * Injects action links into the attendee screen.
	 *
	 * @param $event_id
	 */
	public function event_action_links( $event_id ) {
		$action_links = array(
			'<a href="' . esc_url( get_edit_post_link( $event_id ) ) . '" title="' . esc_attr_x( 'Edit', 'attendee event actions', 'event-tickets' ) . '">' . esc_html_x( 'Edit Event', 'attendee event actions', 'event-tickets' ) . '</a>',
			'<a href="' . esc_url( get_permalink( $event_id ) ) . '" title="' . esc_attr_x( 'View', 'attendee event actions', 'event-tickets' ) . '">' . esc_html_x( 'View Event', 'attendee event actions', 'event-tickets' ) . '</a>',
		);

		/**
		 * Provides an opportunity to add and remove action links from the
		 * attendee screen summary box.
		 *
		 * @param array $action_links
		 */
		$action_links = (array) apply_filters( 'tribe_tickets_attendees_event_action_links', $action_links );

		if ( empty( $action_links ) ) {
			return;
		}

		echo wp_kses_post( '<li class="event-actions">' . join( ' | ', $action_links ) . '</li>' );
	}

	/**
	 * Print Check In Totals at top of Column
	 */
	public function print_checkedin_totals() {
		$total_checked_in = Tribe__Tickets__Main::instance()->attendance_totals()->get_total_checked_in();

		echo '<div class="totals-header"><h3>' . esc_html_x( 'Checked in:', 'attendee summary', 'event-tickets' ) . '</h3> ' . absint( $total_checked_in ) . '</div>';
	}

	/**
	 * Adds the "attendees" link in the admin list row actions for each event.
	 *
	 * @param $actions
	 *
	 * @return array
	 */
	public function attendees_row_action( $actions ) {
		global $post;

		// Only proceed if we're viewing a tickets-enabled post type.
		if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
			return $actions;
		}

		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post->ID );

		// Only proceed if there are tickets.
		if ( empty( $tickets ) ) {
			return $actions;
		}

		$url = $this->get_attendee_report_link( $post );

		$actions['tickets_attendees'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			esc_html__( 'See who purchased tickets to this event', 'event-tickets' ),
			esc_url( $url ),
			esc_html__( 'Attendees', 'event-tickets' )
		);

		return $actions;
	}

	/**
	 * Registers the Attendees admin page
	 */
	public function attendees_page_register() {
		$cap      = 'edit_posts';
		$event_id = absint( ! empty( $_GET['event_id'] ) && is_numeric( $_GET['event_id'] ) ? $_GET['event_id'] : 0 );

		if ( ! current_user_can( 'edit_posts' ) && $event_id ) {
			$event = get_post( $event_id );

			if ( $event instanceof WP_Post && get_current_user_id() === (int) $event->post_author ) {
				$cap = 'read';
			}
		}

		$this->attendees_page = add_submenu_page( null, 'Attendee list', 'Attendee list', $cap, self::$attendees_slug, array( $this, 'attendees_page_inside' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'attendees_page_load_css_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'attendees_page_load_pointers' ) );
		add_action( 'load-' . $this->attendees_page, array( $this, 'attendees_page_screen_setup' ) );

		/**
		 * This is a workaround to fix the problem
		 *
		 * @see  https://central.tri.be/issues/46198
		 * @todo  we need to remove this
		 */
		add_action( 'admin_init', array( $this, 'attendees_page_screen_setup' ), 1 );
	}

	/**
	 * Enqueues the JS and CSS for the attendees page in the admin
	 *
	 * @TODO: this needs to move to Assets.php
	 *
	 * @param $hook
	 */
	public function attendees_page_load_css_js( $hook ) {
		/**
		 * Filter the Page Slugs the Attendees Page CSS and JS Loads
		 *
		 * @param array array( $this->attendees_page ) an array of admin slugs
		 */
		if ( ! in_array( $hook, apply_filters( 'tribe_filter_attendee_page_slug', array( $this->attendees_page ) ) ) ) {
			return;
		}

		$resources_url = plugins_url( 'src/resources', dirname( dirname( __FILE__ ) ) );

		wp_enqueue_style( self::$attendees_slug, $resources_url . '/css/tickets-attendees.css', array(), Tribe__Tickets__Main::instance()->css_version() );
		wp_enqueue_style( self::$attendees_slug . '-print', $resources_url . '/css/tickets-attendees-print.css', array(), Tribe__Tickets__Main::instance()->css_version(), 'print' );
		wp_enqueue_script( self::$attendees_slug, $resources_url . '/js/tickets-attendees.js', array( 'jquery' ), Tribe__Tickets__Main::instance()->js_version() );

		add_thickbox();

		$mail_data = array(
			'nonce'           => wp_create_nonce( 'email-attendee-list' ),
			'required'        => esc_html__( 'You need to select a user or type a valid email address', 'event-tickets' ),
			'sending'         => esc_html__( 'Sending...', 'event-tickets' ),
			'checkin_nonce'   => wp_create_nonce( 'checkin' ),
			'uncheckin_nonce' => wp_create_nonce( 'uncheckin' ),
			'cannot_move'     => esc_html__( 'You must first select one or more tickets before you can move them!', 'event-tickets' ),
			'move_url'        => add_query_arg( array(
				'dialog'    => Tribe__Tickets__Main::instance()->move_tickets()->dialog_name(),
				'check'     => wp_create_nonce( 'move_tickets' ),
				'TB_iframe' => 'true',
			) ),
		);

		wp_localize_script( self::$attendees_slug, 'Attendees', $mail_data );
	}

	/**
	 * Loads the WP-Pointer for the Attendees screen
	 *
	 * @param $hook
	 */
	public function attendees_page_load_pointers( $hook ) {
		if ( $hook != $this->attendees_page ) {
			return;
		}

		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$pointer   = null;

		if ( version_compare( get_bloginfo( 'version' ), '3.3', '>' ) && ! in_array( 'attendees_filters', $dismissed ) ) {
			$pointer = array(
				'pointer_id' => 'attendees_filters',
				'target'     => '#screen-options-link-wrap',
				'options'    => array(
					'content' => sprintf( '<h3> %s </h3> <p> %s </p>', esc_html__( 'Columns', 'event-tickets' ), esc_html__( 'You can use Screen Options to select which columns you want to see. The selection works in the table below, in the email, for print and for the CSV export.', 'event-tickets' ) ),
					'position' => array( 'edge' => 'top', 'align' => 'right' ),
				),
			);
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
		}

		wp_localize_script( self::$attendees_slug, 'AttendeesPointer', $pointer );
	}

	/**
	 * Sets up the Attendees screen data.
	 */
	public function attendees_page_screen_setup() {
		/* There's no reason for attendee screen setup to happen twice, but because
		 * of a fix for bug #46198 it can indeed be called twice in the same request.
		 * This flag variable is used to workaround that.
		 *
		 * @see Tribe__Tickets__Tickets_Handler::attendees_page_register() (and related @todo inside that method)
		 * @see https://central.tri.be/issues/46198
		 *
		 * @todo remove the has_run check once the above workaround is dispensed with
		 */
		static $has_run = false;

		if ( $has_run || ( is_admin() && ( empty( $_GET['page'] ) || self::$attendees_slug !== $_GET['page'] ) ) ) {
			return;
		}

		$has_run = true;

		/**
		 * This is a workaround to fix the problem
		 *
		 * @see  https://central.tri.be/issues/46198
		 * @todo  remove this
		 */
		if ( current_filter() === 'admin_init' ) {
			$this->attendees_page_load_css_js( $this->attendees_page );

			$GLOBALS['current_screen'] = WP_Screen::get( $this->attendees_page );
		}

		if ( ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'email' ) ) ) {
			define( 'IFRAME_REQUEST', true );

			// Use iFrame Header -- WP Method
			iframe_header();

			// Check if we need to send an Email!
			if ( isset( $_POST['tribe-send-email'] ) && $_POST['tribe-send-email'] ) {
				$status = $this->send_attendee_mail_list();
			} else {
				$status = false;
			}

			$which_tmpl = sanitize_file_name( $_GET['action'] );
			include $this->path . 'src/admin-views/attendees-' . $which_tmpl . '.php';

			// Use iFrame Footer -- WP Method
			iframe_footer();

			// We need nothing else here
			exit;
		} else {
			$this->attendees_table = new Tribe__Tickets__Attendees_Table();

			$this->maybe_generate_attendees_csv();

			add_filter( 'admin_title', array( $this, 'attendees_admin_title' ), 10, 2 );
			add_filter( 'admin_body_class', array( $this, 'attendees_admin_body_class' ) );
		}
	}

	public function attendees_admin_body_class( $body_classes ) {
		return $body_classes . ' plugins-php';
	}

	/**
	 * Sets the browser title for the Attendees admin page.
	 * Uses the event title.
	 *
	 * @param $admin_title
	 * @param $unused_title
	 *
	 * @return string
	 */
	public function attendees_admin_title( $admin_title, $unused_title ) {
		if ( ! empty( $_GET['event_id'] ) ) {
			$event       = get_post( $_GET['event_id'] );
			$admin_title = sprintf( '%s - Attendee list', $event->post_title );
		}

		return $admin_title;
	}

	/**
	 * Renders the Attendees page
	 */
	public function attendees_page_inside() {
		/**
		 * Fires immediately before the content of the attendees screen
		 * is rendered.
		 *
		 * @param $this Tribe__Tickets__Tickets_Handler The current ticket handler instance.
		 */
		do_action( 'tribe_tickets_attendees_page_inside', $this );

		include $this->path . 'src/admin-views/attendees.php';
	}

	/**
	 * Generates a list of attendees taking into account the Screen Options.
	 * It's used both for the Email functionality, as for the CSV export.
	 *
	 * @param $event_id
	 *
	 * @return array
	 */
	private function generate_filtered_attendees_list( $event_id ) {
		/**
		 * Fire immediately prior to the generation of a filtered (exportable) attendee list.
		 *
		 * @param int $event_id
		 */
		do_action( 'tribe_events_tickets_generate_filtered_attendees_list', $event_id );

		if ( empty( $this->attendees_page ) ) {
			$this->attendees_page = 'tribe_events_page_tickets-attendees';
		}

		//Add in Columns or get_column_headers() returns nothing
		$filter_name = "manage_{$this->attendees_page}_columns";
		add_filter( $filter_name, array( $this->attendees_table, 'get_columns' ), 15 );

		$items = Tribe__Tickets__Tickets::get_event_attendees( $event_id );

		//Add Handler for Community Tickets to Prevent Notices in Exports
		if ( ! is_admin() ) {
			$columns = apply_filters( $filter_name, array() );
		} else {
			$columns = array_map( 'wp_strip_all_tags', get_column_headers( get_current_screen() ) );
		}

		// We dont want HTML inputs, private data or other columns that are superfluous in a CSV export
		$hidden = array_merge( get_hidden_columns( $this->attendees_page ), array(
			'cb',
			'meta_details',
			'provider',
			'purchaser',
			'status',
		) );

		$hidden         = array_flip( $hidden );
		$export_columns = array_diff_key( $columns, $hidden );

		// Add additional expected columns
		$export_columns['order_id']           = esc_html_x( 'Order ID', 'attendee export', 'event-tickets' );
		$export_columns['order_status_label'] = esc_html_x( 'Order Status', 'attendee export', 'event-tickets' );
		$export_columns['attendee_id']        = esc_html_x( 'Ticket #', 'attendee export', 'event-tickets' );
		$export_columns['purchaser_name']     = esc_html_x( 'Customer Name', 'attendee export', 'event-tickets' );
		$export_columns['purchaser_email']    = esc_html_x( 'Customer Email Address', 'attendee export', 'event-tickets' );

		/**
		 * Used to modify what columns should be shown on the CSV export
		 * The column name should be the Array Index and the Header is the array Value
		 *
		 * @param array Columns, associative array
		 * @param array Items to be exported
		 * @param int   Event ID
		 */
		$export_columns = apply_filters( 'tribe_events_tickets_attendees_csv_export_columns', $export_columns, $items, $event_id );

		// Add the export column headers as the first row
		$rows = array(
			array_values( $export_columns ),
		);

		foreach ( $items as $single_item ) {
			// Fresh row!
			$row = array();

			foreach ( $export_columns as $column_id => $column_name ) {
				// If additional columns have been added to the attendee list table we can obtain the
				// values by calling the table object's column_default() method - any other values
				// should simply be passed back unmodified
				$row[ $column_id ] = $this->attendees_table->column_default( $single_item, $column_id );

				// Special handling for the check_in column
				if ( 'check_in' === $column_id && 1 == $single_item[ $column_id ] ) {
					$row[ $column_id ] = esc_html__( 'Yes', 'event-tickets' );
				}

				// Special handling for new human readable id
				if ( 'attendee_id' === $column_id ) {
					if ( isset( $single_item[ $column_id ] ) ) {
						$ticket_unique_id  = get_post_meta( $single_item[ $column_id ], '_unique_id', true );
						$ticket_unique_id  = $ticket_unique_id === '' ? $single_item[ $column_id ] : $ticket_unique_id;
						$row[ $column_id ] = esc_html( $ticket_unique_id );
					}
				}

				// Handle custom columns that might have names containing HTML tags
				$row[ $column_id ] = wp_strip_all_tags( $row[ $column_id ] );
				// Remove line breaks (e.g. from multi-line text field) for valid CSV format. Double quotes necessary here.
				$row[ $column_id ] = str_replace( array( "\r", "\n" ), ' ', $row[ $column_id ] );
			}

			$rows[] = array_values( $row );
		}

		return array_filter( $rows );
	}

	/**
	 * Checks if the user requested a CSV export from the attendees list.
	 * If so, generates the download and finishes the execution.
	 */
	public function maybe_generate_attendees_csv() {
		if ( empty( $_GET['attendees_csv'] ) || empty( $_GET['attendees_csv_nonce'] ) || empty( $_GET['event_id'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['attendees_csv_nonce'], 'attendees_csv_nonce' ) || ! $this->user_can( 'edit_posts', $_GET['event_id'] ) ) {
			return;
		}

		/**
		 * Allow for filtering and modifying the list of attendees that will be exported via CSV for a given event.
		 *
		 * @param array $items The array of attendees that will be exported in this CSV file.
		 * @param int $event_id The ID of the event these attendees are associated with.
		 */
		$items = apply_filters( 'tribe_events_tickets_attendees_csv_items', $this->generate_filtered_attendees_list( $_GET['event_id'] ), $_GET['event_id'] );
		$event = get_post( $_GET['event_id'] );

		if ( ! empty( $items ) ) {
			$charset  = get_option( 'blog_charset' );
			$filename = sanitize_file_name( $event->post_title . '-' . __( 'attendees', 'event-tickets' ) );

			// output headers so that the file is downloaded rather than displayed
			header( "Content-Type: text/csv; charset=$charset" );
			header( "Content-Disposition: attachment; filename=$filename.csv" );

			// create a file pointer connected to the output stream
			$output = fopen( 'php://output', 'w' );

			//And echo the data
			foreach ( $items as $item ) {
				fputcsv( $output, $item );
			}

			fclose( $output );
			exit;
		}
	}

	/**
	 * Handles the "send to email" action for the attendees list.
	 */
	public function send_attendee_mail_list() {
		$error = new WP_Error();

		if ( empty( $_GET['event_id'] ) ) {
			$error->add( 'no-event-id', esc_html__( 'Invalid Event ID', 'event-tickets' ), array( 'type' => 'general' ) );

			return $error;
		}

		$cap      = 'edit_posts';
		$event_id = absint( ! empty( $_GET['event_id'] ) && is_numeric( $_GET['event_id'] ) ? $_GET['event_id'] : 0 );

		if ( ! current_user_can( 'edit_posts' ) && $event_id ) {
			$event = get_post( $event_id );

			if ( $event instanceof WP_Post && get_current_user_id() === (int) $event->post_author ) {
				$cap = 'read';
			}
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'email-attendees-list' ) || ! $this->user_can( $cap, $_GET['event_id'] ) ) {
			$error->add( 'nonce-fail', esc_html__( 'Cheatin Huh?', 'event-tickets' ), array( 'type' => 'general' ) );

			return $error;
		}

		if ( empty( $_POST['email_to_address'] ) && ( empty( $_POST['email_to_user'] ) || 0 >= (int) $_POST['email_to_user'] ) ) {
			$error->add( 'empty-fields', esc_html__( 'Empty user and email', 'event-tickets' ), array( 'type' => 'general' ) );

			return $error;
		}

		if ( ! empty( $_POST['email_to_address'] ) ) {
			$type = 'email';
		} else {
			$type = 'user';
		}

		if ( 'email' === $type && ! is_email( $_POST['email_to_address'] ) ) {
			$error->add( 'invalid-email', esc_html__( 'Invalid Email', 'event-tickets' ), array( 'type' => $type ) );

			return $error;
		}

		if ( 'user' === $type && ! is_numeric( $_POST['email_to_user'] ) ) {
			$error->add( 'invalid-user', esc_html__( 'Invalid User ID', 'event-tickets' ), array( 'type' => $type ) );

			return $error;
		}

		/**
		 * Now we know we have valid data
		 */

		if ( 'email' === $type ) {
			// We already check this variable so, no harm here
			$email = $_POST['email_to_address'];
		} else {
			$user = get_user_by( 'id', $_POST['email_to_user'] );

			if ( ! is_object( $user ) ) {
				$error->add( 'invalid-user', esc_html__( 'Invalid User ID', 'event-tickets' ), array( 'type' => $type ) );

				return $error;
			}

			$email = $user->data->user_email;
		}

		$this->attendees_table = new Tribe__Tickets__Attendees_Table();

		$items = $this->generate_filtered_attendees_list( $_GET['event_id'] );

		$event = get_post( $_GET['event_id'] );

		ob_start();
		$attendee_tpl = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/attendees-email.php', array( 'disable_view_check' => true ) );
		include $attendee_tpl;
		$content = ob_get_clean();

		add_filter( 'wp_mail_content_type', array( $this, 'set_contenttype' ) );

		if ( ! wp_mail( $email, sprintf( esc_html__( 'Attendee List for: %s', 'event-tickets' ), $event->post_title ), $content ) ) {
			$error->add( 'email-error', esc_html__( 'Error when sending the email', 'event-tickets' ), array( 'type' => 'general' ) );

			return $error;
		}

		remove_filter( 'wp_mail_content_type', array( $this, 'set_contenttype' ) );

		return esc_html__( 'Email sent successfully!', 'event-tickets' );
	}

	/**
	 * Sets the content type for the attendees to email functionality.
	 * Allows for sending an HTML email.
	 *
	 * @param $content_type
	 *
	 * @return string
	 */
	public function set_contenttype( $content_type ) {
		return 'text/html';
	}

	/**
	 * Tests if the user has the specified capability in relation to whatever post type
	 * the ticket relates to.
	 *
	 * For example, if tickets are created for the banana post type, the generic capability
	 * "edit_posts" will be mapped to "edit_bananas" or whatever is appropriate.
	 *
	 * @internal for internal plugin use only (in spite of having public visibility)
	 *
	 * @param  string $generic_cap
	 * @param  int    $event_id
	 * @return boolean
	 */
	public function user_can( $generic_cap, $event_id ) {
		$type = get_post_type( $event_id );

		// It's possible we'll get null back
		if ( null === $type ) {
			return false;
		}

		$type_config = get_post_type_object( $type );

		if ( ! empty( $type_config->cap->{$generic_cap} ) ) {
			return current_user_can( $type_config->cap->{$generic_cap} );
		}

		return false;
	}

	/* Tickets Metabox */

	/**
	 * Includes the tickets metabox inside the Event edit screen
	 *
	 * @param WP_Post $post
	 */
	public function do_meta_box( $post ) {
		$start_date = date( 'Y-m-d H:00:00' );
		$end_date   = date( 'Y-m-d H:00:00' );
		$start_time = Tribe__Date_Utils::time_only( $start_date, false );
		$end_time   = Tribe__Date_Utils::time_only( $start_date, false );

		$show_global_stock = Tribe__Tickets__Tickets::global_stock_available();
		$tickets           = Tribe__Tickets__Tickets::get_event_tickets( $post->ID );
		$global_stock      = new Tribe__Tickets__Global_Stock( $post->ID );

		include $this->path . 'src/admin-views/meta-box.php';
	}

	/**
	 * Render the ticket row into the ticket table
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 */
	public function render_ticket_row( $ticket ) {
		$provider      = $ticket->provider_class;
		$provider_obj  = call_user_func( array( $provider, 'get_instance' ) );
		$inventory     = $ticket->inventory();
		$available     = $ticket->available();
		$capacity      = $ticket->capacity();
		$stock         = $ticket->stock();
		$needs_warning = false;
		$mode          = $ticket->global_stock_mode();
		$event         = $ticket->get_event();

		// If we don't have an event we should even continue
		if ( ! $event ) {
			return;
		}

		if (
			'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === $ticket->provider_class
			&& -1 !== $capacity
		) {
			$product = wc_get_product( $ticket->ID );
			$shared_stock = new Tribe__Tickets__Global_Stock( $event->ID );
			$needs_warning = (int) $inventory !== (int) $stock;

			// We remove the warning flag when shared stock is used
			if ( $shared_stock->is_enabled() && (int) $stock > (int) $shared_stock->get_stock_level() ) {
				$needs_warning = false;
			}
		}

		?>
		<tr class="<?php echo esc_attr( $provider ); ?> is-expanded" data-ticket-order-id="order_<?php echo esc_attr( $ticket->ID ); ?>" data-ticket-type-id="<?php echo esc_attr( $ticket->ID ); ?>">
			<td class="column-primary ticket_name <?php echo esc_attr( $provider ); ?>" data-label="<?php esc_html_e( 'Ticket Type:', 'event-tickets' ); ?>">
				<span class="dashicons dashicons-screenoptions tribe-handle"></span>
				<input
					type="hidden"
					class="tribe-ticket-field-order"
					name="tribe-tickets[<?php echo esc_attr( $ticket->ID ); ?>][order]"
					value="<?php echo esc_attr( $ticket->menu_order ); ?>"
					<?php echo 'Tribe__Tickets__RSVP' === $ticket->provider_class ? 'disabled' : ''; ?>
				>
				<?php echo esc_html( $ticket->name ); ?>
			</td>

			<?php
			/**
			 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
			 *
			 * @since TBD
			 *
			 * @param Tribe__Tickets__Ticket_Object $ticket
			 * @param obj ecommerce provider object
			 */
			do_action( 'tribe_events_tickets_ticket_table_add_tbody_column', $ticket, $provider_obj );
			?>

			<td class="ticket_capacity">
				<span class='tribe-mobile-only'><?php esc_html_e( 'Capacity:', 'event-tickets' ); ?></span>
				<?php tribe_tickets_get_readable_amount( $capacity, $mode, true ); ?>
			</td>

			<td class="ticket_available">
				<span class='tribe-mobile-only'><?php esc_html_e( 'Available:', 'event-tickets' ); ?></span>
				<?php if ( $needs_warning ) : ?>
					<span class="dashicons dashicons-warning required" title="<?php esc_attr_e( 'The number of Complete ticket sales does not match the number of attendees. Please check the Attendees list and adjust ticket stock in WooCommerce as needed.', 'event-tickets' ) ?>"></span>
				<?php endif; ?>

				<?php tribe_tickets_get_readable_amount( $available, $mode, true ); ?>
			</td>

			<td class="ticket_edit">
				<?php
				printf(
					"<button data-provider='%s' data-ticket-id='%s' title='%s' class='ticket_edit_button'><span class='ticket_edit_text'>%s</span></a>",
					esc_attr( $ticket->provider_class ),
					esc_attr( $ticket->ID ),
					sprintf( __( '( Ticket ID: %d )', 'tribe-tickets' ), esc_attr( $ticket->ID ) ),
					esc_html( $ticket->name )
				);
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Echoes the markup for the tickets list in the tickets metabox
	 *
	 * @param int $unused_post_id event ID
	 * @param array $tickets
	 */
	public function ticket_list_markup( $unused_post_id, $tickets = array() ) {
		if ( ! empty( $tickets ) ) {
			include $this->path . 'src/admin-views/list.php';
		}
	}

	/**
	 * Returns the markup for the tickets list in the tickets metabox
	 *
	 * @param array $tickets
	 *
	 * @return string
	 */
	public function get_ticket_list_markup( $tickets = array() ) {
		ob_start();
		$this->ticket_list_markup( null, $tickets );
		$return = ob_get_clean();

		return $return;
	}

	/**
	 * Returns the markup for the Settings Panel for Tickets
	 *
	 * @param  int    $post_id
	 *
	 * @return string
	 */
	public function get_settings_panel( $post_id ) {
		ob_start();
		include $this->path . 'src/admin-views/settings_admin_panel.php';
		$return = ob_get_clean();

		return $return;
	}

	/**
	 * Returns the markup for the History for a Given Ticket
	 *
	 * @param  int    $ticket_id
	 *
	 * @return string
	 */
	public function get_history_content( $post_id, $ticket ) {
		ob_start();
		include $this->path . 'src/admin-views/tickets-history.php';
		$return = ob_get_clean();

		return $return;
	}

	/**
	 * Returns the attachment ID for the header image for a event.
	 *
	 * @param $event_id
	 *
	 * @return mixed
	 */
	public function get_header_image_id( $event_id ) {
		return get_post_meta( $event_id, $this->image_header_field, true );
	}

	/**
	 * Save or delete the image header for tickets on an event
	 *
	 * @param int $post_id
	 */
	public function save_image_header( $post_id ) {
		if ( ! ( isset( $_POST[ 'tribe-tickets-post-settings' ] ) && wp_verify_nonce( $_POST[ 'tribe-tickets-post-settings' ], 'tribe-tickets-meta-box' ) ) ) {
			return;
		}

		// don't do anything on autosave or auto-draft either or massupdates
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( empty( $_POST['tribe_ticket_header_image_id'] ) ) {
			delete_post_meta( $post_id, $this->image_header_field );
		} else {
			update_post_meta( $post_id, $this->image_header_field, $_POST['tribe_ticket_header_image_id'] );
		}

		return;
	}

	/**
	 * Save the the drag-n-drop ticket order
	 *
	 * @since TBD
	 *
	 * @param int $post
	 *
	 */
	public function save_order( $post, $tickets = null ) {
		// We're calling this during post save, so the save nonce has already been checked.

		// don't do anything on autosave, auto-draft, or massupdates
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return false;
		}

		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}

		// Bail on Invalid post
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		// If we didn't get any Ticket data we fetch from the $_POST
		if ( is_null( $tickets ) ) {
			$tickets = Tribe__Utils__Array::get( $_POST, array( 'tribe-tickets' ), null );
		}

		if ( empty( $tickets ) ) {
			return false;
		}

		foreach ( $tickets as $id => $ticket ) {
			if ( ! isset( $ticket['order'] ) ) {
				continue;
			}

			$args = array(
				'ID'         => absint( $id ),
				'menu_order' => (int) $ticket['order'],
			);

			$updated[] = wp_update_post( $args );
		}

		// Verify if any failed
		return ! in_array( 0, $updated );
	}

	protected function sort_by_menu_order( $a, $b ) {
		return $a->menu_order - $b->menu_order;
	}

	/**
	 * Sorts tickets according to stored menu_order
	 *
	 * @since TBD
	 *
	 * @param array $tickets array of ticket objects
	 *
	 * @return array - sorted array of ticket objects
	 */
	public function sort_tickets_by_menu_order( $tickets ) {
		foreach ( $tickets as $key => $ticket ) {
			// make sure they are ordered correctly
			$orderpost          = get_post( $ticket->ID );
			$ticket->menu_order = $orderpost->menu_order;
		}

		usort( $tickets, array( $this, 'sort_by_menu_order' ) );

		return $tickets;
	}

	/**
	 * Static Singleton Factory Method
	 *
	 * @return Tribe__Tickets__Tickets_Handler
	 */
	public static function instance() {
		return tribe( 'tickets.handler' );
	}

	/**
	 * Returns the current post being handled.
	 *
	 * @return array|bool|null|WP_Post
	 */
	public function get_post() {
		return $this->attendees_table->event;
	}

	/**
	 * Whether the ticket handler should render the title in the attendees report.
	 *
	 * @param bool $should_render_title
	 */
	public function should_render_title( $should_render_title ) {
		$this->should_render_title = $should_render_title;
	}

	/**
	 * Returns the full URL to the attendees report page.
	 *
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public function get_attendee_report_link( $post ) {
		$url = add_query_arg( array(
			'post_type' => $post->post_type,
			'page'      => self::$attendees_slug,
			'event_id'  => $post->ID,
		), admin_url( 'edit.php' ) );

		return $url;
	}

	/**
	 * Saves the event ticket settings via ajax
	 *
	 * @since TBD
	 */
	public function ajax_handler_save_settings() {
		$params = array();
		$id = absint( $_POST['post_ID'] );
		$params = wp_parse_args( $_POST['formdata'], $params );

		/**
		 * Allow other plugins to hook into this to add settings
		 *
		 * @since TBD
		 *
		 * @param array $params the array of parameters to filter
		 */
		do_action( 'tribe_events_save_tickets_settings', $params );

		if ( ! empty( $params['tribe_ticket_header_image_id'] ) ) {
			update_post_meta( $id, $this->image_header_field, $params['tribe_ticket_header_image_id'] );
		} else {
			delete_post_meta( $id, $this->image_header_field );
		}

		// We reversed this logic on the back end
		update_post_meta( $id, Tribe__Tickets_Plus__Attendees_List::HIDE_META_KEY, ! empty( $params['tribe_show_attendees'] ) );


		// Change the default ticket provider
		if ( ! empty( $params['default_ticket_provider'] ) ) {
			update_post_meta( $id, $this->key_provider_field, $params['default_ticket_provider'] );
		} else {
			delete_post_meta( $id, $this->key_provider_field );
		}

		wp_send_json_success( $params );
	}
}
