<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Class Tribe__Tickets__Attendees_Table
 *
 * See documentation for WP_List_Table
 */
class Tribe__Tickets__Attendees_Table extends WP_List_Table {

	// Store a possible Event
	public $event = false;

	/**
	 * Class constructor
	 *
	 * @param array $args  additional arguments/overrides
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'attendee',
			'plural'   => 'attendees',
			'ajax'     => true,
		) );

		// Fetch the event Object
		if ( ! empty( $_GET['event_id'] ) ) {
			$this->event = get_post( $_GET['event_id'] );
		}

		add_filter( 'event_tickets_attendees_table_row_actions', array( $this, 'add_default_row_actions' ), 10, 2 );

		parent::__construct( apply_filters( 'tribe_events_tickets_attendees_table_args', $args ) );
	}


	/**
	 * Display the search box.
	 * We don't want Core's search box, because we implemented our own jQuery based filter,
	 * so this function overrides the parent's one and returns empty.
	 *
	 * @param string $text     The search button text
	 * @param string $input_id The search input id
	 */
	public function search_box( $text, $input_id ) {
		return;
	}

	/**
	 * Display the pagination.
	 * We are not paginating the attendee list, so it returns empty.
	 */
	public function pagination( $which ) {
		return '';
	}

	/**
	 * Checks the current user's permissions
	 */
	public function ajax_user_can() {
		return current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_posts );
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'ticket'    => esc_html_x( 'Ticket', 'attendee table', 'event-tickets' ),
			'purchaser' => esc_html_x( 'Purchaser', 'attendee table', 'event-tickets' ),
			'status'    => esc_html_x( 'Status', 'attendee table', 'event-tickets' ),
			'security'  => esc_html__( 'Security Code', 'event-tickets' ),
			'check_in'  => esc_html__( 'Check in', 'event-tickets' ),
		);

		return $columns;
	}

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @param $item
	 * @param $column
	 *
	 * @return string
	 */
	public function column_default( $item, $column ) {
		$value = empty( $item[ $column ] ) ? '' : $item[ $column ];

		return apply_filters( 'tribe_events_tickets_attendees_table_column', $value, $item, $column );
	}

	/**
	 * Handler for the checkbox column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', esc_attr( $this->_args['singular'] ), esc_attr( $item['attendee_id'] . '|' . $item['provider'] ) );
	}

	/**
	 * Populates the purchaser column.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_purchaser( array $item ) {
		$purchaser_name  = empty( $item[ 'purchaser_name' ] ) ? '' : esc_html( $item[ 'purchaser_name' ] );
		$purchaser_email = empty( $item[ 'purchaser_email' ] ) ? '' : esc_html( $item[ 'purchaser_email' ] );

		return "
			<div class='purchaser_name'>{$purchaser_name}</div>
			<div class='purchaser_email'>{$purchaser_email}</div>
		";
	}

	/**
	 * Populates the status column.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_status( array $item ) {
		$icon    = '';
		$warning = false;

		// Check if the order_warning flag has been set (to indicate the order has been cancelled, refunded etc)
		if ( isset( $item['order_warning'] ) && $item['order_warning'] ) {
			$warning = true;
		}

		// If the warning flag is set, add the appropriate icon
		if ( $warning ) {
			$icon = sprintf( "<span class='warning'><img src='%s'/></span> ", esc_url( Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/warning.png' ) );
		}

		// Look for an order_status_label, fall back on the actual order_status string @todo remove fallback in 3.4.3
		if ( empty( $item['order_status'] ) ) {
			$item['order_status'] = '';
		}

		$label = isset( $item['order_status_label'] ) ? $item['order_status_label'] : ucwords( $item['order_status'] );

		$order_id_url = $this->get_order_id_url( $item );

		if ( ! empty( $order_id_url ) && ! empty( $item[ 'order_id' ] ) ) {
			$label = '<a href="' . esc_url( $order_id_url ) . '">#' . esc_html( $item[ 'order_id' ] ) . ' &ndash; ' . $label . '</a>';
		} elseif ( ! empty( $item[ 'order_id' ] ) ) {
			$label = '#' . esc_html( $item[ 'order_id' ] ) . ' &ndash; ' . $label;
		}

		/**
		 * Provides an opportunity to modify the order status text within
		 * the attendees table.
		 *
		 * @param string $order_status_html
		 * @param array  $item
		 */
		return apply_filters( 'tribe_tickets_attendees_table_order_status', $icon . $label, $item );
	}

	/**
	 * Retrieves the order id for the specified table row item.
	 *
	 * In some cases, such as when the current item belongs to the RSVP provider, an
	 * empty string may be returned as there is no order screen that can be linekd to.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function get_order_id_url( array $item ) {
		// Backwards compatibility
		if ( empty( $item['order_id_url'] ) ) {
			$item['order_id_url'] = get_edit_post_link( $item['order_id'], true );
		}

		return $item['order_id_url'];
	}

	/**
	 * Handler for the ticket column
	 *
	 * @since 4.1
	 *
	 * @param array $item Item whose ticket data should be output
	 *
	 * @return string
	 */
	public function column_ticket( $item ) {
		ob_start();

		$attendee_id = trim( esc_html( $this->get_attendee_id( $item ) ) );

		if ( ! empty( $attendee_id ) ) {
			$attendee_id .= ' &ndash; ';
		}

		?>
			<div class="event-tickets-ticket-name">
				<?php echo $attendee_id; ?>
				<?php echo esc_html( $item['ticket'] ); ?>
			</div>

			<?php echo $this->get_row_actions( $item ); ?>
		<?php

		/**
		 * Hook to allow for the insertion of additional content in the ticket table cell
		 *
		 * @var array $item Attendee row item
		 */
		do_action( 'event_tickets_attendees_table_ticket_column', $item );

		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Generates and returns the attendee table row actions.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	protected function get_row_actions( array $item ) {
		/**
		 * Sets the row action links that display within the ticket column of the
		 * attendee list table.
		 *
		 * @param array $row_actions
		 * @param array $item
		 */
		$row_actions = (array) apply_filters( 'event_tickets_attendees_table_row_actions', array(), $item );
		$row_actions = join( ' | ', $row_actions );
		return empty( $row_actions ) ? '' : '<div class="row-actions">' . $row_actions . '</div>';
	}

	/**
	 * Adds a set of default row actions to each item in the attendee list table.
	 *
	 * @param array $row_actions
	 * @param array $item
	 *
	 * @return array
	 */
	public function add_default_row_actions( array $row_actions, array $item ) {
		$attendee = esc_attr( $item['attendee_id'] . '|' . $item['provider'] );
		$nonce = wp_create_nonce( 'do_item_action_' . $attendee );

		$check_in_out_url = esc_url( add_query_arg( array(
			'action'   => $item[ 'check_in' ] ? 'uncheck_in' : 'check_in',
			'nonce'    => $nonce,
			'attendee' => $attendee,
		) ) );

		$check_in_out_text = $item[ 'check_in' ]
			? esc_html_x( 'Undo Check In', 'row action', 'event-tickets' )
			: esc_html_x( 'Check In', 'row action', 'event-tickets' );

		$delete_url = esc_url( add_query_arg( array(
			'action'   => 'delete_attendee',
			'nonce'    => $nonce,
			'attendee' => $attendee,
		) ) );

		$default_actions = array(
			'<span class="inline"> <a href="' . $check_in_out_url . '">' . $check_in_out_text . '</a> </span>',
		);

		if ( is_admin() ) {
			$default_actions[] = '<span class="inline move-ticket"> <a href="#">' . esc_html_x( 'Move', 'row action', 'event-tickets' ) . '</a> </span>';
		}

		$default_actions[] = '<span class="trash"><a href="' . $delete_url . '">' . esc_html_x( 'Delete', 'row action', 'event-tickets' ) . '</a></span>';

		return array_merge( $row_actions, $default_actions );
	}

	/**
	 * Returns the attendee ID (or "unique ID" if set).
	 *
	 * @param array $item
	 *
	 * @return int|string
	 */
	public function get_attendee_id( $item ) {
		$attendee_id = empty( $item['attendee_id'] ) ? '' : $item['attendee_id'];
		if ( $attendee_id === '' ) {
			return '';
		}

		$unique_id = get_post_meta( $attendee_id, '_unique_id', true );

		if ( $unique_id === '' ) {
			$unique_id = $attendee_id;
		}

		/**
		 * Filters the ticket number; defaults to the ticket unique ID.
		 *
		 * @param string $unique_id A unique string identifier for the ticket.
		 * @param array  $item      The item entry.
		 */
		return apply_filters( 'tribe_events_tickets_attendees_table_attendee_id_column', $unique_id, $item );
	}

	/**
	 * Handler for the check in column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_check_in( $item ) {
		$default_checkin_stati = array();
		$provider              = $item['provider_slug'];
		$order_id = $item['order_id'];

		/**
		 * Filters the order stati that will allow for a ticket to be checked in for all commerce providers.
		 *
		 * @since 4.1
		 *
		 * @param array  $default_checkin_stati An array of default order stati that will make a ticket eligible for check-in.
		 * @param string $provider              The ticket provider slug.
		 * @param int    $order_id              The order post ID.
		 */
		$check_in_stati = apply_filters( 'event_tickets_attendees_checkin_stati', $default_checkin_stati, $provider, $order_id );

		/**
		 * Filters the order stati that will allow for a ticket to be checked in for a specific commerce provider.
		 *
		 * @since 4.1
		 *
		 * @param array  $default_checkin_stati An array of default order stati that will make a ticket eligible for check-in.
		 * @param int    $order_id              The order post ID.
		 */
		$check_in_stati = apply_filters( "event_tickets_attendees_{$provider}_checkin_stati", $check_in_stati, $order_id );

		if (
			! empty( $item['order_status'] )
			&& ! empty( $item['order_id_link_src'] )
			&& is_array( $check_in_stati )
			&& ! in_array( $item['order_status'], $check_in_stati )
		) {
			$button_template = '<a href="%s" class="button-secondary tickets-checkin">%s</a>';

			return sprintf( $button_template, $item['order_id_link_src'], __( 'View order', 'event-tickets' ) );
		}
		if ( empty( $this->event ) ) {
			$checkin   = sprintf( '<a href="#" data-attendee-id="%d" data-provider="%s" class="button-secondary tickets_checkin">%s</a>', esc_attr( $item['attendee_id'] ), esc_attr( $item['provider'] ), esc_html__( 'Check in', 'event-tickets' ) );
			$uncheckin = sprintf( '<span class="delete"><a href="#" data-attendee-id="%d" data-provider="%s" class="tickets_uncheckin">%s</a></span>', esc_attr( $item['attendee_id'] ), esc_attr( $item['provider'] ), esc_html__( 'Undo Check in', 'event-tickets' ) );
		} else {
			// add the additional `data-event-id` attribute if this is an event
			$checkin   = sprintf( '<a href="#" data-attendee-id="%d" data-event-id="%d" data-provider="%s" class="button-secondary tickets_checkin">%s</a>', esc_attr( $item['attendee_id'] ), esc_attr($this->event->ID), esc_attr( $item['provider'] ), esc_html__( 'Check in', 'event-tickets' ) );
			$uncheckin = sprintf( '<span class="delete"><a href="#" data-attendee-id="%d" data-event-id="%d" data-provider="%s" class="tickets_uncheckin">%s</a></span>', esc_attr( $item['attendee_id'] ), esc_attr($this->event->ID), esc_attr( $item['provider'] ), esc_html__( 'Undo Check in', 'event-tickets' ) );
		}

		return $checkin . $uncheckin;
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' alternate ' : '' );

		$checked = '';
		if ( intval( $item['check_in'] ) === 1 ) {
			$checked = ' tickets_checked ';
		}

		echo '<tr class="' . sanitize_html_class( $row_class ) . esc_attr( $checked ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';

		/**
		 * Hook to allow for the insertion of data after an attendee table row
		 *
		 * @var $item Attendee data
		 */
		do_action( 'event_tickets_attendees_table_after_row', $item );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * Used for the Print, Email and Export buttons, and for the jQuery based search.
	 *
	 * @param string $which (top|bottom)
	 * @see WP_List_Table::display()
	 */
	public function extra_tablenav( $which ) {

		$export_url = add_query_arg(
			array(
				'attendees_csv' => true,
				'attendees_csv_nonce' => wp_create_nonce( 'attendees_csv_nonce' ),
			)
		);

		/**
		 * Include TB_iframe JS
		 */
		add_thickbox();

		$email_link = Tribe__Settings::instance()->get_url( array(
			'page' => 'tickets-attendees',
			'action' => 'email',
			'event_id' => $this->event->ID,
			'TB_iframe' => true,
			'width' => 410,
			'height' => 300,
			'parent' => 'admin.php',
		) );

		$nav = array(
			'left' => array(
				'print' => sprintf( '<input type="button" name="print" class="print button action" value="%s">', esc_attr__( 'Print', 'event-tickets' ) ),
				'email' => '<a class="email button action thickbox" href="' . esc_url( $email_link ) . '">' . esc_attr__( 'Email', 'event-tickets' ) . '</a>',
				'export' => sprintf( '<a href="%s" class="export button action">%s</a>', esc_url( $export_url ), esc_html__( 'Export', 'event-tickets' ) ),
			),
			'right' => array(),
		);

		if ( 'top' == $which ) {
			$nav['right']['filter_box'] = sprintf( '%s: <input type="text" name="filter_attendee" id="filter_attendee" value="">', esc_html__( 'Filter by purchaser name, ticket #, order # or security code', 'event-tickets' ) );
		}

		$nav = apply_filters( 'tribe_events_tickets_attendees_table_nav', $nav, $which );

		?>
		<div class="alignleft actions"><?php echo implode( $nav['left'] ); ?></div>
		<div class="alignright"><?php echo implode( $nav['right'] ) ?></div>
		<?php
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'check_in'        => esc_attr__( 'Check in', 'event-tickets' ),
			'uncheck_in'      => esc_attr__( 'Undo Check in', 'event-tickets' ),
			'delete_attendee' => esc_attr__( 'Delete', 'event-tickets' ),
		);

		return (array) apply_filters( 'tribe_events_tickets_attendees_table_bulk_actions', $actions );
	}

	/**
	 * @deprecated use process_actions()
	 */
	public function process_bulk_actions() {
		_deprecated_function( __FUNCTION__, '4.3', __CLASS__ . '::process_actions()' );
		$this->process_actions();
	}

	/**
	 * Handler for the different bulk actions
	 */
	public function process_actions() {
		if ( ! $this->validate_action_nonce() ) {
			return;
		}

		switch ( $this->current_action() ) {
			case 'check_in':
				$this->do_check_in();
				break;
			case 'uncheck_in':
				$this->do_uncheck_in();
				break;
			case 'delete_attendee':
				$this->do_delete();
				break;
			default:
				do_action( 'tribe_events_tickets_attendees_table_process_bulk_action', $this->current_action() );
				break;
		}
	}

	/**
	 * Indicates if a valid nonce was set for the currently requested bulk or
	 * individual action.
	 *
	 * @return bool
	 */
	protected function validate_action_nonce() {
		// If a bulk action request was posted
		if ( @$_POST[ 'attendee' ] && wp_verify_nonce( $_POST[ '_wpnonce' ], 'bulk-attendees' ) ) {
			return true;
		}

		// If an individual action was requested
		if ( @$_GET[ 'attendee' ] && wp_verify_nonce( $_GET[ 'nonce' ], 'do_item_action_' . $_GET[ 'attendee' ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the list of attendee/tickets for the current bulk or individual
	 * action.
	 *
	 * The format is an array where the elements represent the ticket ID and
	 * provider in the following format:
	 *
	 *     [
	 *         '123|Ticket_Provider',
	 *         '4567|Ticket_Provider',
	 *     ]
	 *
	 * @return array
	 */
	protected function get_action_ids() {
		$action_ids = array();

		if ( isset( $_POST[ 'attendee' ] ) ) {
			$action_ids = (array) $_POST[ 'attendee' ];
		} else if ( isset( $_GET[ 'attendee' ] ) ) {
			$action_ids = (array) $_GET[ 'attendee' ];
		}

		return $action_ids;
	}

	protected function do_check_in() {
		$attendee_ids = $this->get_action_ids();

		if ( ! $attendee_ids ) {
			return;
		}

		foreach ( $attendee_ids as $attendee ) {
			list( $id, $addon ) = $this->attendee_reference( $attendee );
			if ( false === $id ) {
				continue;
			}
			$addon->checkin( $id );
		}
	}

	protected function do_uncheck_in() {
		$attendee_ids = $this->get_action_ids();

		if ( ! $attendee_ids ) {
			return;
		}

		foreach ( $attendee_ids as $attendee ) {
			list( $id, $addon ) = $this->attendee_reference( $attendee );
			if ( false === $id ) {
				continue;
			}
			$addon->uncheckin( $id );
		}
	}

	protected function do_delete() {
		$attendee_ids = $this->get_action_ids();

		if ( ! $attendee_ids ) {
			return;
		}

		foreach ( $attendee_ids as $attendee ) {
			list( $id, $addon ) = $this->attendee_reference( $attendee );
			if ( false === $id ) {
				continue;
			}
			$addon->delete_ticket( null, $id );
		}
	}

	/**
	 * Returns the attendee ID and instance of the specific ticketing solution or "addon" used
	 * to handle it.
	 *
	 * This is used in the context of bulk actions where each attendee table entry is identified
	 * by a string of the pattern {id}|{ticket_class} - where possible this method turns that into
	 * an array consisting of the attendee object ID and the relevant ticketing object.
	 *
	 * If this cannot be determined, both array elements will be set to false.
	 *
	 * @param $reference
	 *
	 * @return array
	 */
	protected function attendee_reference( $reference ) {
		$failed = array( false, false );
		if ( false === strpos( $reference, '|' ) ) {
			return $failed;
		}

		$parts = explode( '|', $reference );
		if ( count( $parts ) < 2 ) {
			return $failed;
		}

		$id = absint( $parts[0] );
		if ( $id <= 0 ) {
			return $failed;
		}

		$addon = call_user_func( array( $parts[1], 'get_instance' ) );
		if ( ! is_subclass_of( $addon, 'Tribe__Tickets__Tickets' ) ) {
			return $failed;
		}

		return array( $id, $addon );
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {

		$this->process_actions();

		$event_id = isset( $_GET['event_id'] ) ? $_GET['event_id'] : 0;

		$items = Tribe__Tickets__Tickets::get_event_attendees( $event_id );


		$this->items = $items;
		$total_items = count( $this->items );
		$per_page    = $total_items;

		$this->set_pagination_args(
			 array(
				 'total_items' => $total_items,
				 'per_page'    => $per_page,
				 'total_pages' => 1,
			 )
		);

	}


}
