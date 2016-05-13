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
			'cb'              => '<input type="checkbox" />',
			'order_id'        => esc_html__( 'Order #', 'event-tickets' ),
			'order_status'    => esc_html__( 'Order Status', 'event-tickets' ),
			'purchaser_name'  => esc_html__( 'Purchaser name', 'event-tickets' ),
			'purchaser_email' => esc_html__( 'Purchaser email', 'event-tickets' ),
			'ticket'          => esc_html__( 'Ticket type', 'event-tickets' ),
			'attendee_id'     => esc_html__( 'Ticket #', 'event-tickets' ),
			'security'        => esc_html__( 'Security Code', 'event-tickets' ),
			'check_in'        => esc_html__( 'Check in', 'event-tickets' ),
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
	 * Handler for the order id column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_order_id( $item ) {

		//back compat
		if ( empty( $item['order_id_link'] ) ) {
			$item['order_id_link'] = sprintf( '<a class="row-title" href="%s">%s</a>', esc_url( get_edit_post_link( $item['order_id'], true ) ), esc_html( $item['order_id'] ) );
		}

		return $item['order_id_link'];
	}

	/**
	 * Handler for the order status column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_order_status( $item ) {
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

		return $icon . $label;
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

		?>
		<div class="event-tickets-ticket-name">
			<?php echo esc_html( $item['ticket'] ); ?>
		</div>
		<?php

		/**
		 * Hook to allow for the insertion of additional content in the ticket table cell
		 *
		 * @var $item Attendee row item
		 */
		do_action( 'event_tickets_attendees_table_ticket_column', $item );

		$output = ob_get_clean();

		return $output;
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

		$checkin   = sprintf( '<a href="#" data-attendee-id="%d" data-provider="%s" class="button-secondary tickets_checkin">%s</a>', esc_attr( $item['attendee_id'] ), esc_attr( $item['provider'] ), esc_html__( 'Check in', 'event-tickets' ) );
		$uncheckin = sprintf( '<span class="delete"><a href="#" data-attendee-id="%d" data-provider="%s" class="tickets_uncheckin">%s</a></span>', esc_attr( $item['attendee_id'] ), esc_attr( $item['provider'] ), esc_html__( 'Undo Check in', 'event-tickets' ) );

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
		 * When we use a TB_iframe link and we are not on an Admin Page we need to include it's JS
		 */
		if ( ! is_admin() ) {
			add_thickbox();
		}

		$email_link = Tribe__Settings::instance()->get_url( array(
			'page' => 'tickets-attendees',
			'action' => 'email',
			'event_id' => $this->event->ID,
			'TB_iframe' => true,
			'width' => 410,
			'height' => 300,
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
	 * Handler for the different bulk actions
	 */
	public function process_bulk_action() {
		switch ( $this->current_action() ) {
			case 'check_in':
				$this->bulk_check_in();
				break;
			case 'uncheck_in':
				$this->bulk_uncheck_in();
				break;
			case 'delete_attendee':
				$this->bulk_delete();
				break;
			default:
				do_action( 'tribe_events_tickets_attendees_table_process_bulk_action', $this->current_action() );
				break;
		}
	}

	protected function bulk_check_in() {
		if ( ! isset( $_POST['attendee'] ) ) {
			return;
		}

		foreach ( (array) $_POST['attendee'] as $attendee ) {
			list( $id, $addon ) = $this->attendee_reference( $attendee );
			if ( false === $id ) {
				continue;
			}
			$addon->checkin( $id );
		}
	}

	protected function bulk_uncheck_in() {
		if ( ! isset( $_POST['attendee'] ) ) {
			return;
		}

		foreach ( (array) $_POST['attendee'] as $attendee ) {
			list( $id, $addon ) = $this->attendee_reference( $attendee );
			if ( false === $id ) {
				continue;
			}
			$addon->uncheckin( $id );
		}
	}

	protected function bulk_delete() {
		if ( ! isset( $_POST['attendee'] ) ) {
			return;
		}

		foreach ( (array) $_POST['attendee'] as $attendee ) {
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

		$this->process_bulk_action();

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
