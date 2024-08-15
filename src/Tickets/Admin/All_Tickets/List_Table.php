<?php
/**
 * The list table for the All Tickets screen.
 *
 * @since  TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\All_Tickets;

use TEC\Tickets\Commerce\Ticket;
use Tribe__Tickets__Tickets;
use Tribe__Tickets__Ticket_Object;
use WP_List_Table;
use DateTime;

/**
 * Class List_Table.
 *
 * @since  TBD
 *
 * @package TEC\Tickets\Admin
 */
class List_Table extends WP_List_Table {
	/**
	 * The user option that will store how many attendees should be shown per page.
	 *
	 * @var string
	 */
	protected $per_page_option;

	/**
	 * The constructor.
	 *
	 * @since  TBD
	 */
	public function __construct() {
		$screen = get_current_screen();

		parent::__construct(
			[
				'singular' => 'ticket',
				'plural'   => 'tickets',
				'ajax'     => false,
				'screen'   => $screen,
			]
		);

		$this->per_page_option = Screen_Options::$per_page_user_option;

		if ( ! is_null( $screen ) ) {
			$screen->add_option(
				'per_page',
				[
					'label'  => __( 'Number of tickets per page:', 'event-tickets' ),
					'option' => $this->per_page_option,
				]
			);
		}
	}

	/**
	 * Returns the columns for the list table.
	 *
	 * @since  TBD
	 *
	 * @return array
	 */
	public function get_table_columns(): array {
		$table_columns = [
			'name'      => esc_html__( 'Ticket Name', 'event-tickets' ),
			'id'        => esc_html__( 'Ticket ID', 'event-tickets' ),
			'event'     => esc_html__( 'Event', 'event-tickets' ),
			'start'     => esc_html__( 'Sale Starts', 'event-tickets' ),
			'end'       => esc_html__( 'Sale Ends', 'event-tickets' ),
			'days_left' => esc_html__( 'Days Remaining', 'event-tickets' ),
			'price'     => esc_html__( 'Price', 'event-tickets' ),
			'sold'      => esc_html__( 'Sold', 'event-tickets' ),
			'remaining' => esc_html__( 'Remaining', 'event-tickets' ),
			'sales'     => esc_html__( 'Sales', 'event-tickets' ),
		];

		/**
		 * Filters the columns for the All Tickets Table.
		 *
		 * @since TBD
		 *
		 * @param array $table_columns The columns for the All Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_all_tickets_table_columns', $table_columns );
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since  TBD
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return $this->get_table_columns();
	}

	/**
	 * Get primary column for the list table.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_primary_column_name(): string {
		return 'name';
	}

	/**
	 * Get hidden columns for the list table.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_hidden_columns(): array {
		$screen = get_current_screen();

		if ( is_null( $screen ) ) {
			return $this->get_default_hidden_columns();
		}

		return get_hidden_columns( $screen );
	}

	/**
	 * Returns the columns for the list table.
	 *
	 * @since  TBD
	 *
	 * @return array
	 */
	public static function get_default_hidden_columns(): array {
		$default_hidden_columns = [
			'id',
			'start',
			'days_left',
			'sales',
		];

		/**
		 * Filter the default hidden columns for the All Tickets Table.
		 *
		 * @since TBD
		 *
		 * @param array $default_hidden_columns The default hidden columns for the All Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_all_tickets_table_default_hidden_columns', $default_hidden_columns );
	}

	/**
	 * Returns the sortable columns for the list table.
	 *
	 * @since  TBD
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'name'      => [ 'name', true ],
			'id'        => [ 'id', true ],
			'start'     => [ 'start', true ],
			'end'       => [ 'end', 'desc' ], // Start with DESC order.
			'days_left' => [ 'days_left', true ],
			'price'     => [ 'price', true ],
			'sold'      => [ 'sold', true ],
		];

		/**
		 * Filters the sortable columns for the All Tickets Table.
		 *
		 * @since TBD
		 *
		 * @param array $sortable_columns The sortable columns for the All Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_all_tickets_table_sortable_columns', $sortable_columns );
	}

	/**
	 * Get the column value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function get_ticket_type_icon( $item ) {
		ob_start();
		do_action( 'tec_tickets_editor_list_table_title_icon_' . $item->type() );
		$icon_html = ob_get_clean();
		return $icon_html;
	}

	/**
	 * Get the column name value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_name( $item ): string {
		$event = $item->get_event();
		if ( ! $event ) {
			return '-';
		}
		$edit_post_url = get_edit_post_link( $event );
		$edit_post_link = sprintf(
			'<a href="%s" class="tec-tickets-all-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $edit_post_url ),
			esc_html( $item->name )
		);
		$admin_views = tribe( 'tickets.admin.views' );
		$context     = [
			'icon_html'   => $this->get_ticket_type_icon( $item ),
			'ticket_link' => $edit_post_link,
		];
		return $admin_views->template( 'all-tickets/table-column-name', $context, false );
	}

	/**
	 * Get the column ID value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_id( $item ): string {
		return $item->ID;
	}

	/**
	 * Get the column event value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_event( $item ): string {
		$event = $item->get_event();
		if ( ! $event ) {
			return '-';
		}
		$edit_post_url = get_edit_post_link( $event );
		$edit_post_link = sprintf(
			'<a href="%s" class="tec-tickets-all-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $edit_post_url ),
			get_the_title( $event )
		);
		$orders_report_url = sprintf(
			'https://wp.lndo.site/wp-admin/edit.php?post_type=%s&page=tickets-orders&event_id=%d',
			$event->post_type,
			$event->ID
		);
		$orders_report_link = sprintf(
			'<a href="%s" class="tec-tickets-all-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $orders_report_url ),
			esc_html__( 'Orders', 'event-tickets' )
		);
		$attendees_report_url = sprintf(
			'https://wp.lndo.site/wp-admin/edit.php?post_type=%s&page=tickets-attendees&event_id=%d',
			$event->post_type,
			$event->ID
		);
		$attendees_report_link = sprintf(
			'<a href="%s" class="tec-tickets-all-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $attendees_report_url ),
			esc_html__( 'Attendees', 'event-tickets' )
		);
		$actions = [
			'orders'    => $orders_report_link,
			'attendees' => $attendees_report_link,
		];

		/**
		 * Filters the actions for the event in the All Tickets Table.
		 *
		 * @since TBD
		 *
		 * @param array $actions The actions for the event in the All Tickets Table.
		 */
		$actions = apply_filters( 'tec_tickets_all_tickets_table_event_actions', $actions, $event, $item );

		return sprintf( '%1$s %2$s', $edit_post_link, $this->row_actions( $actions ) );
	}

	/**
	 * Get the column start value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_start( $item ): string {
		$date_format = tribe_get_date_format( true );
		$datetime = $item->start_date( false );
		return $datetime->format( $date_format );
	}

	/**
	 * Get the column end value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_end( $item ): string {
		$date_format = tribe_get_date_format( true );
		$datetime = $item->end_date( false );
		return $datetime->format( $date_format );
	}

	/**
	 * Get the column days_left value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_days_left( $item ): string {
		$datetime = $item->end_date( false );
		$now = new DateTime();
		$interval = $now->diff( $datetime );
		if ( $interval->invert ) {
			return '-';
		}
		return $interval->days;
	}

	/**
	 * Get the column price value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_price( $item ): string {
		return tribe_format_currency( number_format( $item->price, 2 ), $item->ID );
	}

	/**
	 * Get the column sold value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_sold( $item ): string {
		return $item->qty_sold();
	}

	/**
	 * Get the column remaining value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_remaining( $item ): string {
		$available = $item->available();
		return $available < 0 ? '-' : $available;
	}

	/**
	 * Get the column sales value.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_sales( $item ): string {
		return tribe_format_currency( number_format( $item->qty_sold() * $item->price, 2 ), $item->ID );
	}

	/**
	 * Modify the sort arguments.
	 *
	 * @since TBD
	 *
	 * @param array $args The arguments used to query the tickets for the All Tickets Table.
	 *
	 * @return array
	 */
	public function modify_sort_args( $args ) {
		$orderby = tribe_get_request_var( 'orderby', 'end' );
		switch ( $orderby ) {
			case 'name':
				$args['orderby'] = 'post_title';
				break;
			case 'id':
				$args['orderby'] = 'ID';
				break;
			case 'start':
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = '_ticket_start_date';
				$args['meta_type'] = 'DATE';
				break;
			case 'end':
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = '_ticket_end_date';
				$args['meta_type'] = 'DATE';
				break;
			case 'days_left':
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = '_ticket_end_date';
				$args['meta_type'] = 'DATE';
				break;
			case 'price':
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = '_price';
				break;
			case 'sold':
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = 'total_sales';
				break;
		}

		$order         = tribe_get_request_var( 'order', 'desc' );
		$args['order'] = strtoupper( $order );

		return $args;
	}

	/**
	 * Get the query args for the list table.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_query_args() {

		$current_page = $this->get_pagenum();
		$per_page     = $this->get_items_per_page( $this->per_page_option );

		$args = [
			'offset'             => ( $current_page - 1 ) * $per_page,
			'posts_per_page'     => $per_page,
			'return_total_found' => true,
		];

		$args = $this->modify_sort_args( $args );

		/**
		 * Filters the arguments used to query the tickets for the All Tickets Table.
		 *
		 * @since TBD
		 *
		 * @param array $args The arguments used to query the tickets for the All Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_all_tickets_table_query_args', $args );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since TBD
	 */
	public function prepare_items() {
		global $wpdb;

		$args               = $this->get_query_args();
		$tickets_repository = tribe_tickets()->by_args( $args );
		$total_items        = $tickets_repository->found();
		$items              = $tickets_repository->all();
		foreach ( $items as $i => $item ) {
			$this->items[] = Tribe__Tickets__Tickets::load_ticket_object( $item->ID );
		}

		$pagination_args = [
			'total_items' => $total_items,
			'per_page'    => $this->get_items_per_page( $this->per_page_option ),
		];

		$this->_column_headers = [
			$this->get_table_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		];

		$this->set_pagination_args( $pagination_args );
	}
}
