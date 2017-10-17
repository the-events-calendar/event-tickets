<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Orders__Table
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Orders__Table extends WP_List_Table {

	/**
	 * @var string The user option that will be used to store the number of orders per page to show.
	 */
	public $per_page_option;

	/**
	 * @var int The current post ID
	 */
	public $post_id;

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Orders__Sales
	 */
	protected $sales;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Orders__Table constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$args = array(
			'singular' => 'order',
			'plural'   => 'orders',
			'ajax'     => true,
		);

		$this->per_page_option = Tribe__Tickets__Commerce__PayPal__Screen_Options::$per_page_user_option;

		$screen = get_current_screen();

		$screen->add_option( 'per_page', array(
			'label'  => __( 'Number of orders per page:', 'event-tickets-plus' ),
			'option' => $this->per_page_option,
		) );

		$this->sales = tribe( 'tickets.commerce.paypal.orders.sales' );

		parent::__construct( $args );
	}

	/**
	 * Displays the search box.
	 *
	 * @since TBD
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {
		// do not display the search box
		return;
	}

	/**
	 * Checks the current user's permissions
	 *
	 * @since TBD
	 */
	public function ajax_user_can() {
		$post_type = get_post_type_object( $this->screen->post_type );

		return ! empty( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_posts );
	}

	/**
	 * Returns the  list of columns.
	 *
	 * @since TBD
	 *
	 * @return array An associative array in the format [ <slug> => <title> ]
	 */
	public function get_columns() {
		$columns = array(
			'order'     => __( 'Order', 'event-tickets' ),
			'purchaser' => __( 'Purchaser', 'event-tickets' ),
			'email'     => __( 'Email', 'event-tickets' ),
			'purchased' => __( 'Purchased', 'event-tickets' ),
			'date'      => __( 'Date', 'event-tickets' ),
			'status'    => __( 'Status', 'event-tickets' ),
		);

		$columns['total'] = __( 'Total', 'event-tickets' );

		return $columns;
	}

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @since TBD
	 *
	 * @param $item
	 * @param $column
	 *
	 * @return string
	 */
	public function column_default( $item, $column ) {
		$value = empty( $item->$column ) ? '' : $item->$column;

		return apply_filters( 'tribe_tickets_commerce_paypal_orders_table_column', $value, $item, $column );
	}

	/**
	 * Handler for the date column
	 *
	 * @since TBD
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		$date = $item['purchase_time'];

		return esc_html( Tribe__Date_Utils::reformat( $date, Tribe__Date_Utils::DATEONLYFORMAT ) );
	}

	/**
	 * Handler for the purchased column
	 *
	 * @since TBD
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_purchased( $item ) {
		$output = '';

		$tickets = $this->sales->count_attendees_by( $item['attendees'], 'ticket' );

		foreach ( $tickets as $_name => $_quantity ) {
			$name     = esc_html( $_name );
			$quantity = esc_html( $_quantity );
			$output   .= "<div class='tribe-line-item'>{$quantity} - {$name}</div>";
		}

		return $output;
	}

	/**
	 * Handler for the order column
	 *
	 * @since TBD
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_order( $item ) {
		$order_number = $item['number'];

		$order_number_link = '<a href="' . esc_url( $item['url'] ) . '" target="_blank">' . $order_number . '</a>';

		$output = sprintf( esc_html__( '%1$s', 'event-tickets' ), $order_number_link );

		if ( 'completed' !== $item['status'] ) {
			$output .= '<div class="order-status order-status-' . esc_attr( $item['status'] ) . '">' . esc_html(
					ucwords( $item['status_label'] )
				) . '</div>';
		}

		return $output;
	}

	/**
	 * Handler for the total column
	 *
	 * @since TBD
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_total( $item ) {
		return tribe_format_currency( number_format( $item['line_total'], 2 ) );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since TBD
	 *
	 * @param $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr class="' . esc_attr( $item['status'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since TBD
	 */
	public function prepare_items() {
		$this->post_id = Tribe__Utils__Array::get( $_GET, 'event_id', Tribe__Utils__Array::get( $_GET, 'post_id', 0 ), 0 );

		/** @var \Tribe__Tickets__Commerce__PayPal__Orders__Sales $sales */
		$sales = tribe( 'tickets.commerce.paypal.orders.sales' );

		$product_ids = Tribe__Utils__Array::get( $_GET, 'product_ids', null );

		if ( false !== $product_ids ) {
			$product_ids = explode( ',', $product_ids );
		}

		$items       = $sales->get_orders_for_post( $this->post_id, $product_ids );

		$total_items = count( $items );

		$per_page = $this->get_items_per_page( $this->per_page_option );

		$current_page = $this->get_pagenum();

		$this->items = array_slice( $items, ( $current_page - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Returns the customer name.
	 *
	 * @since TBD
	 *
	 * @param $item The current item.
	 *
	 * @return string
	 */
	public function column_purchaser( $item ) {
		return esc_html( $item['purchaser_name'] );
	}

	/**
	 * Returns the customer email.
	 *
	 * @since TBD
	 *
	 * @param $item The current item.
	 *
	 * @return string
	 */
	public function column_email( $item ) {
		return esc_html( $item['purchaser_email'] );
	}

	/**
	 * Returns the order status.
	 *
	 * @since TBD
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		return esc_html( $item['status_label'] );
	}
}