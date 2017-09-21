<?php

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
	 * Post Meta key for showing attendees on the front end
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $show_attendees_field = '_tribe_show_attendees';

	/**
	 * Post Meta key for event ecommerce provider
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $ticket_provider_field = '_tribe_ticket_provider';

	/**
	 * Post Meta key for global stock/capacity amount
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $global_stock_field = '_tribe_ticket_global_stock_level';

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
	 * @since TBD
	 *
	 * @var string
	 */
	public $unlimited_term = 'unlimited';

	/**
	 *    Class constructor.
	 */
	public function __construct() {
		$main = Tribe__Tickets__Main::instance();
		$this->unlimited_term = __( 'unlimited', 'event-tickets' );

		foreach ( $main->post_types() as $post_type ) {
			add_action( 'save_post_' . $post_type, array( $this, 'save_image_header' ) );
			add_action( 'save_post_' . $post_type, array( $this, 'save_tickets_order' ) );
		}

		add_action( 'admin_menu', array( $this, 'attendees_page_register' ) );
		add_action( 'tribe_tickets_attendees_event_details_list_top', array( $this, 'event_details_top' ), 20 );
		add_action( 'tribe_tickets_plus_report_event_details_list_top', array( $this, 'event_details_top' ), 20 );
		add_action( 'tribe_tickets_attendees_event_details_list_top', array( $this, 'event_action_links' ), 25 );
		add_action( 'tribe_tickets_plus_report_event_details_list_top', array( $this, 'event_action_links' ), 25 );
		add_action( 'tribe_events_tickets_attendees_totals_top', array( $this, 'print_checkedin_totals' ), 0 );
		add_action( 'tribe_ticket_order_field', array( $this, 'tickets_order_input' ) );
		add_action( 'wp_ajax_tribe-ticket-save-settings', array( $this, 'ajax_handler_save_settings' ) );

		add_filter( 'post_row_actions', array( $this, 'attendees_row_action' ) );
		add_filter( 'page_row_actions', array( $this, 'attendees_row_action' ) );

		$this->path = trailingslashit(  dirname( dirname( dirname( __FILE__ ) ) ) );
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
	 * Returns whether a ticket has unlimited capacity
	 *
	 * @since TBD
	 *
	 * @param object Tribe__Tickets__Ticket_Object
	 */
	 public function is_unlimited_ticket( $ticket ) {
		switch ( $ticket->global_stock_mode() ) {
			case Tribe__Tickets__Global_Stock::OWN_STOCK_MODE:
			case Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE:
			case Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE:
				return false;
			default:
				return true;
		}
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
	 * Get an array list of unlimited tickets for an event.
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 * @param boolean (true) exclude RSVPs from list
	 *
	 * @return array list of tickets
	 */
	public function get_event_unlimited_tickets( $post = null, $exclude_rsvp = true ) {
		$post_id     = Tribe__Main::post_id_helper( $post );
		$tickets     = Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$ticket_list = array();

		if ( ! empty( $tickets ) ) {
			foreach ( $tickets as $ticket ) {
				if ( ! $this->is_unlimited_ticket( $ticket ) ) {
					continue;
				}

				if ( $exclude_rsvp && 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
					continue;
				}

				$ticket_list[] = $ticket;
			}
		}

		return $ticket_list;
	}

	/**
	 * Get the total event capacity.
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return int|string number of tickets ( or string 'unlimited' )
	 */
	public function get_total_event_capacity( $post = null ) {
		$capacity = 0;
		$post_id  = Tribe__Main::post_id_helper( $post );

		// short circuit unlimited stock
		if ( $this->has_unlimited_stock( $post ) ) {
			/**
			 * Allow templates to filter the returned value
			 *
			 * @since TDB
			 *
			 * @param int|string $capacity Total capacity value (string 'unlimited' for unlimited capacity)
			 * @param int $post Post ID tickets are attached to
			 */
			return apply_filters( 'tribe_tickets_total_event_capacity', $this->unlimited_term, $post_id );
		}

		$cap_array = array(
			$this->get_total_original_event_shared_capacity( $post_id ),
			$this->get_total_event_independent_capacity( $post_id ),
			$this->get_total_event_rsvp_capacity( $post_id ),
		);

		// Something bad happend and we got nothing, return 0
		if ( empty( $cap_array ) ) {
			return apply_filters( 'tribe_tickets_total_event_capacity', 0, $post_id );
		}

		// Add it up...
		$capacity = array_sum( $cap_array );

		return apply_filters( 'tribe_tickets_total_event_capacity', $capacity, $post_id );
	}

	/**
	 * Get the total event independent capacity. For display
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return int|string number of tickets ( or string 'unlimited' )
	 */
	public function get_total_event_independent_capacity( $post = null ) {
		$post_id  = Tribe__Main::post_id_helper( $post );
		$tickets  = $this->get_event_independent_tickets( $post_id );
		$capacity = 0;

		if ( ! empty( $tickets ) ) {
			foreach ( $tickets as $ticket ) {
				$stock    = $ticket->original_stock();
				$capacity += $stock;
			}
		}

		/**
		 * Allow templates to filter the returned value
		 *
		 * @since TDB
		 *
		 * @param int $capacity Total capacity value
		 * @param int $post Post ID tickets are attached to
		 * @param array $tickets array of all tickets
		 */
		return apply_filters( 'tribe_tickets_total_event_independent_capacity', $capacity, $post_id, $tickets );
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

		if ( ! empty( $tickets ) ) {
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
		}

		return $ticket_list;
	}

	/**
	 * Get the total event RSVP capacity. For display
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return int|string number of tickets ( or string 'unlimited' )
	 */
	public function get_total_event_rsvp_capacity( $post = null ) {
		$post_id  = Tribe__Main::post_id_helper( $post );
		$tickets  = $this->get_event_rsvp_tickets( $post_id );
		$capacity = 0;

		if ( ! empty( $tickets ) ) {
			foreach ( $tickets as $ticket ) {
				$stock = $ticket->original_stock();

				// Failsafe - empty original stock means unlimited tickets, let's not add infinity!
				if ( empty( $stock ) ) {
					// If one ticket is unlimited, so is total capacity - break out with flag value
					$capacity = $this->unlimited_term;
					break;
				} else {
					$capacity += $stock;
				}
			}
		}

		/**
		 * Allow templates to filter the returned value
		 *
		 * @since TDB
		 *
		 * @param int $capacity Total capacity value
		 * @param int $post Post ID tickets are attached to
		 * @param array $tickets array of all tickets
		 */
		return apply_filters( 'tribe_tickets_total_event_rsvp_capacity', $capacity, $post_id, $tickets );
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
	public function get_event_rsvp_tickets( $post = null, $exclude_unlimited = false ) {
		$post_id     = Tribe__Main::post_id_helper( $post );
		$tickets     = Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$ticket_list = array();

		if ( ! empty( $tickets ) ) {
			foreach ( $tickets as $ticket ) {
				if ( 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
					continue;
				}

				if ( $exclude_unlimited && $this->is_unlimited_ticket( $ticket ) ) {
					continue;
				}

				$ticket_list[] = $ticket;
			}
		}

		return $ticket_list;
	}

	/**
	 * Get the total event shared capacity. For display
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return int|string number of tickets ( or string 'unlimited' )
	 */
	public function get_total_event_shared_capacity( $post = null ) {
		$post_id                 = Tribe__Main::post_id_helper( $post );
		$capacity                = 0;
		$global_capacity_enabled = get_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED, true );
		$capacity                = get_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL, true );

		// If we don't have $global_capacity_enabled, do some housecleaning
		if ( ! $global_capacity_enabled ) {
			delete_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED );
			delete_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL );
			$capacity = 0;
		}

		/**
		 * Allow templates to filter the returned value
		 *
		 * @since TDB
		 *
		 * @param int $capacity Total capacity value
		 * @param int $post Post ID tickets are attached to
		 */
		return apply_filters( 'tribe_tickets_total_event_shared_capacity', $capacity, $post_id );
	}

	/**
	 * Get the total event shared capacity. For display
	 *
	 * @since TBD
	 *
	 * @param int|object (null) $post Post or Post ID tickets are attached to
	 *
	 * @return int|string number of tickets ( or string 'unlimited' )
	 */
	public function get_total_original_event_shared_capacity( $post = null ) {
		$post_id                 = Tribe__Main::post_id_helper( $post );
		$capacity                = 0;
		$global_capacity_enabled = get_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED, true );

		// If we don't have $global_capacity_enabled, do some housecleaning
		if ( ! $global_capacity_enabled ) {
			delete_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED );
			delete_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL );

			/**
			 * Allow templates to filter the returned value
			 *
			 * @since TDB
			 *
			 * @param int $capacity Total capacity value
			 * @param int $post Post ID tickets are attached to
			 */
			return apply_filters( 'tribe_tickets_total_event_shared_capacity', $capacity, $post_id );
		}

		$capacity       = get_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL, true );
		$shared_tickets = $this->get_event_shared_tickets( $post_id );

		foreach ( $shared_tickets as $shared_ticket ) {
			$capacity += $shared_ticket->qty_sold();
		}

		/**
		 * Allow templates to filter the returned value
		 *
		 * @since TDB
		 *
		 * @param int $capacity Total capacity value
		 * @param int $post Post ID tickets are attached to
		 */
		return apply_filters( 'tribe_tickets_total_event_shared_capacity', $capacity, $post_id );
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

		if ( ! empty( $tickets ) ) {
			foreach ( $tickets as $ticket ) {
				$stock_mode = $ticket->global_stock_mode();
				if ( empty( $stock_mode ) || Tribe__Tickets__Global_Stock::OWN_STOCK_MODE == $stock_mode ) {
					continue;
				}

				// Failsafe - should not include unlimited tickets
				if ( $this->is_unlimited_ticket( $ticket ) ) {
					continue;
				}

				$ticket_list[] = $ticket;
			}
		}

		return $ticket_list;
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

		$items = apply_filters( 'tribe_events_tickets_attendees_csv_items', $this->generate_filtered_attendees_list( $_GET['event_id'] ) );;
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
		$provider     = $ticket->provider_class;
		$provider_obj = call_user_func( array( $provider, 'get_instance' ) );
		?>
		<tr class="<?php echo esc_attr( $provider ); ?>" data-ticket-order-id="order_<?php echo esc_attr( $ticket->ID ); ?>" data-ticket-type-id="<?php echo esc_attr( $ticket->ID ); ?>">
			<td class=" column-primary ticket_name <?php echo esc_attr( $provider ); ?>">
				<span class="ticket_cell_label"><?php esc_html_e( 'Ticket Type:', 'event-tickets' ); ?></span>
				<p><?php echo esc_html( $ticket->name ); ?></p>
				<button type="button" class="tribe-toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'event-tickets' ); ?></span></button>
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

			$global_stock_mode = $ticket->global_stock_mode();
			?>

			<td class="ticket_capacity">
				<span class="ticket_cell_label"><?php esc_html_e( 'Capacity:', 'event-tickets' ); ?></span>
				<?php
				$show_parens = Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $global_stock_mode || Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $global_stock_mode;
				if ( $show_parens ) {
					echo '(';
				}
				$ticket->display_original_stock();
				if ( $show_parens ) {
					echo ')';
				}
				?>
			</td>

			<td class="ticket_available">
				<span class="ticket_cell_label"><?php esc_html_e( 'Available:', 'event-tickets' ); ?></span>
				<?php
				if ( $this->unlimited_term === $ticket->display_original_stock( false ) ) {
					// escaping handled in function - could be string|int
					$ticket->display_original_stock();
				} elseif ( Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === $global_stock_mode ) {
					echo esc_html( $ticket->remaining() );
				} else {
					echo '(' . esc_html( $ticket->remaining() ) . ')';
				}
				?>
			</td>

			<td class="ticket_edit">
				<?php
				printf(
					"<button data-provider='%s' data-ticket-id='%s' class='ticket_edit_button'><span class='ticket_edit_text'>%s</span></a>",
					esc_attr( $ticket->provider_class ),
					esc_attr( $ticket->ID ),
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
	 * @param int $post_id
	 *
	 */
	public function save_tickets_order( $post_id ) {
		// We're calling this during post save, so the save nonce has already been checked.

		// don't do anything on autosave, auto-draft, or massupdates
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// If our data is missing or we're already in the middle of saving, bail
		if (
			empty( $_POST[ 'tribe_tickets_order' ] )
			|| ! (
				isset( $_POST[ 'tribe-tickets-post-settings' ] )
				&& wp_verify_nonce( $_POST[ 'tribe-tickets-post-settings' ], 'tribe-tickets-meta-box' )
			)
		) {
			return;
		}

		$ticket_order = $_POST['tribe_tickets_order'];

		update_post_meta(
			$post_id,
			$this->tickets_order_field,
			$ticket_order
		);

		$ticket_order = explode( ',', $ticket_order );
		$ticket_order = array_flip( $ticket_order );

		foreach ( $ticket_order as $id => $order ) {
			wp_update_post( array(
				'ID'         => absint( $id ),
				'menu_order' => absint( $order ),
			) );
		}

		return;
	}

	/**
	 * Adds the hidden input to store the drag-n-drop ticket order
	 *
	 * @since TBD
	 *
	 * @param int $post_id
	 */
	public function tickets_order_input( $post_id ) {
		$tickets_order = get_post_meta( $post_id, $this->tickets_order_field, true );
		?>
		<input type="hidden" name="tribe_tickets_order" id="tribe_tickets_order" value="<?php echo esc_html( $tickets_order ); ?>">
		<?php
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
		$id = $_POST['post_ID'];
		parse_str( $_POST['formdata'], $params );

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
		if ( ! empty( $params['tribe_show_attendees'] ) ) {
			delete_post_meta( $id, $this->show_attendees_field );
		} else {
			update_post_meta( $id, $this->show_attendees_field, 1 );
		}

		// Change the default ticket provider
		if ( ! empty( $params['default_ticket_provider'] ) ) {
			update_post_meta( $id, $this->ticket_provider_field, $params['default_ticket_provider'] );
		} else {
			delete_post_meta( $id, $this->ticket_provider_field );
		}

		wp_send_json_success( $params );
	}
}
