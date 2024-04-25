<?php

namespace TEC\Tickets\Commerce\Admin_Tables;

use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Status_Handler;
use \Tribe__Utils__Array as Arr;

use \WP_List_Table;
use \WP_Post;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Admin Tables for Orders.
 *
 * @since 5.2.0
 *
 */
class Orders extends WP_List_Table {

	/**
	 * The user option that will be used to store the number of orders per page to show.
	 *
	 * @var string
	 */
	public $per_page_option = 20;

	/**
	 * The current post ID
	 *
	 * @var int
	 */
	public $post_id;

	/**
	 * The name (what gets submitted to the server) of our search box input.
	 *
	 * @var string $search_box_input_name
	 */
	private $search_box_input_name = 'search';

	/**
	 * The name of the search type slug.
	 *
	 * @var string $search_box_input_name
	 */
	private $search_type_slug = 'tec_tc_order_search_type';

	/**
	 * Orders Table constructor.
	 *
	 * @since 5.2.0
	 */
	public function __construct() {
		$args = [
			'singular' => 'order',
			'plural'   => 'orders',
			'ajax'     => true,
		];

		parent::__construct( $args );
	}

	/**
	 * Overrides the list of CSS classes for the WP_List_Table table tag.
	 * This function is not hookable in core, so it needs to be overridden!
	 *
	 * @since 5.2.0
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$classes = [ 'widefat', 'striped', 'tribe-tickets-commerce-report-orders' ];

		if ( is_admin() ) {
			$classes[] = 'fixed';
		}

		/**
		 * Filters the default classes added to the Tickets Commerce order report `WP_List_Table`.
		 *
		 * @since 5.2.0
		 *
		 * @param array $classes The array of classes to be applied.
		 */
		return apply_filters( 'tec_tickets_commerce_reports_orders_table_classes', $classes );
	}

	/**
	 * Checks the current user's permissions
	 *
	 * @since 5.2.0
	 */
	public function ajax_user_can() {
		$post_type = get_post_type_object( $this->screen->post_type );

		return ! empty( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_posts );
	}

	/**
	 * Returns the  list of columns.
	 *
	 * @since 5.2.0
	 *
	 * @return array An associative array in the format [ <slug> => <title> ]
	 */
	public function get_columns() {
		$columns = [
			'order'            => __( 'Order', 'event-tickets' ),
			'purchaser'        => __( 'Purchaser', 'event-tickets' ),
			'email'            => __( 'Email', 'event-tickets' ),
			'purchased'        => __( 'Purchased', 'event-tickets' ),
			'date'             => __( 'Date', 'event-tickets' ),
			'gateway'          => __( 'Gateway', 'event-tickets' ),
			'gateway_order_id' => __( 'Gateway ID', 'event-tickets' ),
			'status'           => __( 'Status', 'event-tickets' ),
			'total'            => __( 'Total', 'event-tickets' ),
		];

		return $columns;
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr class="' . esc_attr( $item->post_status ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 5.2.0
	 */
	public function prepare_items() {
		$post_id = tribe_get_request_var( 'post_id', 0 );
		$post_id = tribe_get_request_var( 'event_id', $post_id );

		$this->post_id = $post_id;
		$product_ids   = tribe_get_request_var( 'product_ids' );
		$product_ids   = ! empty( $product_ids ) ? explode( ',', $product_ids ) : null;

		$search    = tribe_get_request_var( $this->search_box_input_name );
		$page      = absint( tribe_get_request_var( 'paged', 0 ) );
		$orderby   = tribe_get_request_var( 'orderby' );
		$order     = tribe_get_request_var( 'order' );
		$arguments = [
			'status'         => 'any',
			'paged'          => $page,
			'posts_per_page' => $this->per_page_option,
		];

		if ( ! empty( $search ) ) {
			$search_keys = array_keys( $this->get_search_options() );

			// Default selection.
			$search_key  = 'purchaser_full_name';
			$search_type = sanitize_text_field( tribe_get_request_var( $this->search_type_slug ) );

			if (
				$search_type
				&& in_array( $search_type, $search_keys, true )
			) {
				$search_key = $search_type;
			}

			$search_like_keys = [
				'purchaser_full_name',
				'purchaser_email',
			];

			/**
			 * Filters the item keys that support LIKE matching to filter orders while searching them.
			 *
			 * @since 5.5.6
			 *
			 * @param array  $search_like_keys The keys that support LIKE matching.
			 * @param array  $search_keys      The keys that can be used to search orders.
			 * @param string $search           The current search string.
			 */
			$search_like_keys = apply_filters( 'tec_tc_order_search_like_keys', $search_like_keys, $search_keys, $search );

			// Update search key if it supports LIKE matching.
			if ( in_array( $search_key, $search_like_keys, true ) ) {
				$search_key .= '__like';
			}

			$arguments[ $search_key ] = $search;
		}

		if ( ! empty( $post_id ) ) {
			$arguments['events'] = $post_id;
		}
		if ( ! empty( $product_ids ) ) {
			$arguments['tickets'] = $product_ids;
		}

		if ( ! empty( $orderby ) ) {
			$arguments['orderby'] = $orderby;
		}

		if ( ! empty( $order ) ) {
			$arguments['order'] = $order;
		}

		/**
		 * Filters the arguments used to fetch the orders for the order report.
		 *
		 * @since 5.5.6
		 *
		 * @param array $arguments The arguments used to fetch the orders.
		 */
		$arguments = apply_filters( 'tec_tc_order_report_args', $arguments );

		$orders_repository = tec_tc_orders()->by_args( $arguments );

		$total_items = $orders_repository->found();

		$this->items = $orders_repository->all();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $this->per_page_option,
		] );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 5.2.0
	 */
	public function no_items() {
		_e( 'No matching orders found.', 'event-tickets' );
	}

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item
	 * @param         $column
	 *
	 * @return string
	 */
	public function column_default( $item, $column ) {
		return empty( $item->$column ) ? '??' : $item->$column;
	}

	/**
	 * Returns the customer name.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_purchaser( $item ) {
		return esc_html( $item->purchaser['full_name'] );
	}

	/**
	 * Returns the customer email.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_email( $item ) {
		return esc_html( $item->purchaser['email'] );
	}

	/**
	 * Returns the order status.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$status = tribe( Status_Handler::class )->get_by_wp_slug( $item->post_status );

		return esc_html( $status->get_name() );
	}

	/**
	 * Handler for the date column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		return esc_html( \Tribe__Date_Utils::reformat( $item->post_modified, \Tribe__Date_Utils::DATEONLYFORMAT ) );
	}

	/**
	 * Handler for the purchased column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item
	 *
	 * @return string
	 */
	public function column_purchased( $item ) {
		$output = '';

		if (
			! $item instanceof WP_Post
			|| ! isset( $item->items )
		) {
			return $output;
		}

		foreach ( $item->items as $cart_item ) {
			$ticket   = \Tribe__Tickets__Tickets::load_ticket_object( $cart_item['ticket_id'] );
			$name     = esc_html( $ticket->name );
			$quantity = esc_html( (int) $cart_item['quantity'] );
			$output   .= "<div class='tribe-line-item'>{$quantity} - {$name}</div>";
		}

		return $output;
	}

	/**
	 * Handler for the order column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item
	 *
	 * @return string
	 */
	public function column_order( $item ) {
		$output = sprintf( esc_html__( '%1$s', 'event-tickets' ), $item->ID );
		$status = tribe( Status_Handler::class )->get_by_wp_slug( $item->post_status );

		switch ( $status->get_slug() ) {
			default:
				$output .= ' <div class="order-status order-status-' . esc_attr( $status->get_slug() ) . '">';
				$output .= esc_html( ucwords( $status->get_name() ) );
				$output .= '</div>';
				break;
		}

		return $output;
	}

	/**
	 * Handler for the total column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item
	 *
	 * @return string
	 */
	public function column_total( $item ) {
		return $item->total_value->get_currency();
	}

	/**
	 * Handler for gateway order id.
	 *
	 * @since 5.2.0
	 * @since 5.9.1 Handle when the $order_url is empty.
	 *
	 * @param WP_Post $item
	 *
	 * @return string
	 */
	public function column_gateway_order_id( $item ) {
		$gateway = tribe( Manager::class )->get_gateway_by_key( $item->gateway );
		if ( ! $gateway ) {
			return $item->gateway_order_id;
		}

		$order_url = $gateway->get_order_controller()->get_gateway_dashboard_url_by_order( $item );

		if ( empty( $order_url ) ) {
			return $item->gateway_order_id;
		}

		return sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			$order_url,
			$item->gateway_order_id
		);
	}

	/**
	 * Handler for gateway column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item
	 *
	 * @return string
	 */
	public function column_gateway( $item ) {
		$gateway = tribe( Manager::class )->get_gateway_by_key( $item->gateway );
		if ( ! $gateway ) {
			return $item->gateway;
		}
		return $gateway::get_label();
	}

	/**
	 * List of sortable columns.
	 *
	 * @since 5.5.0
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'order'            => 'order_id',
			'purchaser'        => 'purchaser_full_name',
			'email'            => 'purchaser_email',
			'date'             => 'purchase_time',
			'gateway'          => 'gateway',
			'gateway_order_id' => 'gateway_id',
			'status'           => 'status',
			'total'            => 'total_value'
		];
	}

	/**
	 * Get the allowed search types and their labels.
	 *
	 * @see \TEC\Tickets\Commerce\Repositories\Order_Repository for a List of valid ORM args.
	 *
	 * @since 5.5.6
	 *
	 * @return array
	 */
	public function get_search_options() {
		$options = [
			'purchaser_full_name' => __( 'Search by Purchaser Name', 'event-tickets' ),
			'purchaser_email'     => __( 'Search by Purchaser Email', 'event-tickets' ),
			'gateway'             => __( 'Search by Gateway', 'event-tickets' ),
			'gateway_order_id'    => __( 'Search by Gateway ID', 'event-tickets' ),
			'id'                  => __( 'Search by Order ID', 'event-tickets' ),
		];

		/**
		 * Filters the search types to be shown in the search box for filtering orders.
		 *
		 * @since 5.5.6
		 *
		 * @param array $options List of ORM search types and their labels.
		 */
		return apply_filters( 'tec_tc_order_search_types', $options );
	}

	/**
	 * @inheritDoc
	 */
	public function search_box( $text, $input_id ) {
		// Workaround to show the search box even when no items are found.
		$old_items   = $this->items;
		$this->items = [
			'Temporary',
		];

		// Get normal search box HTML to override.
		ob_start();
		parent::search_box( $text, $input_id );
		$search_box = ob_get_clean();

		// Assign the custom search name.
		$search_box = str_replace( 'name="s"', 'name="' . esc_attr( $this->search_box_input_name ) . '"', $search_box );
		// And get its value upon reloading the page to display its search results so user knows what they searched for.
		$search_box = str_replace( 'value=""', 'value="' . esc_attr( tribe_get_request_var( $this->search_box_input_name ) ) . '"', $search_box );

		$this->items = $old_items;

		// Default selection.
		$selected = 'purchaser_full_name';

		$search_type = sanitize_text_field( tribe_get_request_var( $this->search_type_slug ) );
		$options     = $this->get_search_options();

		if (
			$search_type
			&& array_key_exists( $search_type, $options )
		) {
			$selected = $search_type;
		}

		$template_vars = [
			'options'  => $options,
			'selected' => $selected,
		];

		$custom_search = tribe( \TEC\Tickets\Commerce\Reports\Orders::class )->get_template()->template( 'orders/search-options', $template_vars, false );
		// Add our search type dropdown before the search box input
		$search_box = str_replace( '<input type="submit"', $custom_search . '<input type="submit"', $search_box );

		echo $search_box;
	}

	/**
	 * Displays extra controls.
	 *
	 * @since 5.8.1
	 *
	 * @param string $which The location of the actions: 'left' or 'right'.
	 */
	public function extra_tablenav( $which ) {
		$allowed_tags = [
			'input' => [
				'type'  => true,
				'name'  => true,
				'class' => true,
				'value' => true,
			],
			'a'     => [
				'class'  => true,
				'href'   => true,
				'rel'    => true,
				'target' => true,
			],
		];

		$nav = [
			'left'  => [
				'print'  => sprintf(
					'<input type="button" name="print" class="print button action" value="%s">',
					esc_attr__(
						'Print',
						'event-tickets'
					)
				),
				'export' => sprintf(
					'<a target="_blank" href="%s" class="export action button" rel="noopener noreferrer">%s</a>',
					esc_url( $this->get_export_url() ),
					esc_html__(
						'Export',
						'event-tickets'
					)
				),
			],
			'right' => [],
		];

		$nav = apply_filters( 'tribe_events_tickets_orders_table_nav', $nav, $which );
		?>
		<div class="alignleft actions attendees-actions"><?php echo wp_kses( implode( $nav['left'] ), $allowed_tags ); ?></div>
		<div class="alignright attendees-filter"><?php echo wp_kses( implode( $nav['right'] ), $allowed_tags ); ?></div>
		<?php
	}

	/**
	 * Maybe generate a CSV file for orders.
	 *
	 * This method checks if the necessary GET parameters are set to trigger the CSV generation.
	 * If conditions are met, it generates a CSV file with order data and outputs it for download.
	 *
	 * @since 5.8.1
	 *
	 * @return void
	 */
	public function maybe_generate_csv(): void {
		// Early bail: Check if the necessary GET parameters are not set.
		if ( empty( $_GET['orders_csv'] ) || empty( $_GET['orders_csv_nonce'] ) || empty( $_GET['post_id'] ) ) {
			return;
		}

		$event_id = absint( $_GET['post_id'] );

		/**
		 * Filters the event ID before using it to fetch orders.
		 *
		 * @since 5.8.1
		 *
		 * @param int $event_id The event ID.
		 */
		$event_id = apply_filters( 'tec_tickets_filter_event_id', $event_id );

		// Early bail: Verify the event ID and the nonce.
		if ( empty( $event_id ) || ! wp_verify_nonce( $_GET['orders_csv_nonce'], 'orders_csv_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		$post_id     = tribe_get_request_var(
			'event_id',
			tribe_get_request_var(
				'post_id',
				0
			)
		);
		$product_ids = explode(
			',',
			tribe_get_request_var(
				'product_ids',
				''
			)
		);

		// Initialize arguments for fetching orders.
		$arguments = [
			'status'  => 'any',
			'events'  => $post_id,
			'tickets' => ! empty( $product_ids ) ? $product_ids : null,
			'orderby' => tribe_get_request_var( 'orderby', '' ),
			'order'   => tribe_get_request_var( 'order', '' ),
		];

		/**
		 * Filters the arguments for the order report export.
		 *
		 * @since 5.8.1
		 *
		 * @param array $arguments The arguments for order retrieval.
		 */
		$arguments = apply_filters( 'tec_tc_order_report_export_args', $arguments );

		// Fetch orders using the repository.
		$orders_repository = tec_tc_orders()->by_args( $arguments );
		$items             = $orders_repository->all();

		// Format the orders data for CSV.
		$formatted_data = $this->format_for_csv( $items );

		// Get the event post for filename.
		$event    = get_post( $post_id );
		$filename = sanitize_title( $event->post_title ) . '-' . __(
			'orders',
			'event-tickets'
		) . '.csv';

		// Generate and output the CSV file.
		$this->generate_csv_file( $formatted_data, $filename );
	}

	/**
	 * Formats the orders data for CSV export.
	 *
	 * @since 5.8.1
	 *
	 * @param array $items The orders data.
	 *
	 * @return array The formatted data ready for CSV export.
	 */
	public function format_for_csv( array $items ): array {
		$csv_data = [];

		// Use the values of get_columns() as CSV headers.
		$csv_headers = array_values( $this->get_columns() );
		$csv_data[]  = $csv_headers;

		// Iterate over each item and format the data.
		foreach ( $items as $item ) {
			$csv_row = [];
			foreach ( array_keys( $this->get_columns() ) as $header ) {
				$method_name = 'column_' . $header;
				if ( method_exists(
					$this,
					$method_name
				) ) {
					// Dynamically call the method for the column.
					$value = call_user_func(
						[
							$this,
							$method_name,
						],
						$item
					);
				} else {
					// Accessing the item properties directly using column_default.
					$value = $this->column_default(
						$item,
						$header
					);
				}
				
				$csv_row[] = empty( $value ) ? $value : $this->sanitize_and_format_csv_value( $value );
			}
			$csv_data[] = $csv_row;
		}

		return $csv_data;
	}

	/**
	 * Sanitizes and formats a value for CSV output.
	 *
	 * @since 5.8.1
	 *
	 * @param string $value The value to be formatted.
	 *
	 * @return string The sanitized and formatted value.
	 */
	private function sanitize_and_format_csv_value( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = html_entity_decode(
			$value,
			ENT_QUOTES | ENT_XML1,
			'UTF-8'
		);
		$value = str_replace(
			[
				"\r",
				"\n",
			],
			' ',
			$value
		);

		return $value;
	}

	/**
	 * Outputs the formatted data as a CSV file for download.
	 *
	 * @since 5.8.1
	 *
	 * @param array  $formatted_data The formatted data for CSV export.
	 * @param string $filename The name of the file for download.
	 *
	 * @return void
	 */
	public function generate_csv_file(
		array $formatted_data,
		string $filename = 'export.csv'
	) {
		$charset = get_option( 'blog_charset' );
		// Set headers to force download on the browser.
		header( "Content-Type: text/csv; charset=$charset" );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

		// Open the PHP output stream to write the CSV content.
		$output = fopen(
			'php://output',
			'w'
		);

		// Iterate over each row and write to the PHP output stream.
		foreach ( $formatted_data as $row ) {
			fputcsv(
				$output,
				$row
			); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv
		}

		// Close the output stream.
		fclose( $output );

		// Exit to prevent any additional output.
		exit;
	}

	/**
	 * Generate the export URL for exporting orders.
	 *
	 * @since 5.8.1
	 *
	 * @return string Relative URL for the export.
	 */
	public function get_export_url(): string {
		return add_query_arg(
			[
				'orders_csv'       => true,
				'orders_csv_nonce' => wp_create_nonce( 'orders_csv_nonce' ),
			]
		);
	}
}
