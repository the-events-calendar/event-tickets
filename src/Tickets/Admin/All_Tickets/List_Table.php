<?php
/**
 * The list table for the All Tickets screen.
 *
 * @since  TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\All_Tickets;

use WP_List_Table;

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
		return [
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
	 * Returns the columns for the list table.
	 *
	 * @since  TBD
	 *
	 * @return array
	 */
	public function get_hidden_columns(): array {
		return [
			'id',
			'start',
			'days_left',
			'sales',
		];
	}

	/**
	 * Returns the sortable columns for the list table.
	 *
	 * @since  TBD
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'name'      => [ 'name', true ],
			'id'        => [ 'id', true ],
			'event'     => [ 'event', true ],
			'start'     => [ 'start', true ],
			'end'       => [ 'end', true ],
			'days_left' => [ 'days_left', true ],
			'price'     => [ 'price', true ],
			'sold'      => [ 'sold', true ],
			'remaining' => [ 'remaining', true ],
			'sales'     => [ 'sales', true ],
		];
	}

	/**
	 * Renders the default column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 * @param string $column_name The current column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return $item[ $column_name ];
	}

	/**
	 * Renders the name column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_name( $item ) {
		return esc_html__( 'Ticket Name', 'event-tickets' );
	}

	/**
	 * Renders the quantity column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_quantity( $item ) {
		return esc_html__( 'Ticket Quantity', 'event-tickets' );
	}

	/**
	 * Renders the price column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_price( $item ) {
		return esc_html__( 'Ticket Price', 'event-tickets' );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since TBD
	 */
	public function prepare_items() {
		$current_page = $this->get_pagenum();
		$per_page     = $this->get_items_per_page( $this->per_page_option );

		$pagination_args = [
			'total_items' => 1,
			'per_page'    => $per_page,
		];

		$args = [
			'page'               => $current_page,
			'per_page'           => $per_page,
			'return_total_found' => true,
			'order'              => 'DESC',
		];

		// Setup sorting args.
		if ( tribe_get_request_var( 'orderby' ) ) {
			$args['orderby'] = tribe_get_request_var( 'orderby' );
		}

		if ( tribe_get_request_var( 'order' ) ) {
			$args['order'] = tribe_get_request_var( 'order' );
		}

		/**
		 * Filters the arguments used to query the tickets for the All Tickets Table.
		 *
		 * @since TBD
		 *
		 * @param array $args The arguments used to query the tickets for the All Tickets Table.
		 *
		 * @return array
		 */
		$args = apply_filters( 'tec_tickets_all_tickets_table_query_args', $args );

		$items = [];

		$pagination_args['total_items'] = count( $items );

		$this->_column_headers = [
			$this->get_table_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		];
		$this->items           = $items;

		$this->set_pagination_args( $pagination_args );
	}
}
