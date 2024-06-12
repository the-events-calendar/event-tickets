<?php
/**
 * Orders Table V2
 *
 * @since TBD
 *
 * @package Tribe\Tickets\Commerce\Admin_Tables\V2
 */

namespace TEC\Tickets\Commerce\Admin_Tables\V2;

use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use WP_Post;
use WP_Posts_List_Table;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

/**
 * Class Admin Tables for Orders.
 *
 * @since TBD
 */
class Orders extends WP_Posts_List_Table {

	/**
	 * The current post ID
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $post_id;

	/**
	 * The name (what gets submitted to the server) of our search box input.
	 *
	 * @since TBD
	 *
	 * @var string $search_box_input_name
	 */
	private $search_box_input_name = 'search';

	/**
	 * The name of the search type slug.
	 *
	 * @since TBD
	 *
	 * @var string $search_box_input_name
	 */
	private $search_type_slug = 'tec_tc_order_search_type';

	/**
	 * Orders Table constructor.
	 *
	 * @since TBD
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
	 * Generates the required HTML for a list of row action links.
	 *
	 * @since TBD
	 *
	 * Remove method to let the actions be displayed.
	 *
	 * @param string[] $actions        An array of action links.
	 * @param bool     $always_visible Whether the actions should be always visible.
	 *
	 * @return string The HTML for the row actions.
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		return '';
	}

	/**
	 * Overrides the list of CSS classes for the WP_List_Table table tag.
	 * This function is not hookable in core, so it needs to be overridden!
	 *
	 * @since TBD
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$classes = [ 'widefat', 'striped', 'tribe-tickets-commerce-orders' ];

		if ( is_admin() ) {
			$classes[] = 'fixed';
		}

		/**
		 * Filters the default classes added to the Tickets Commerce order report `WP_List_Table`.
		 *
		 * @since TBD
		 *
		 * @param array $classes The array of classes to be applied.
		 */
		return apply_filters( 'tec_tickets_commerce_orders_table_classes', $classes );
	}

	/**
	 * Returns the  list of columns.
	 *
	 * @since TBD
	 *
	 * @return array An associative array in the format [ <slug> => <title> ]
	 */
	public function get_columns() {
		return [
			'order'            => __( 'Order', 'event-tickets' ),
			'date'             => __( 'Date', 'event-tickets' ),
			'status'           => __( 'Status', 'event-tickets' ),
			'items'            => __( 'Items', 'event-tickets' ),
			'total'            => __( 'Total', 'event-tickets' ),
			'gateway'          => __( 'Gateway', 'event-tickets' ),
			'gateway_order_id' => __( 'Gateway ID', 'event-tickets' ),
		];
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since TBD
	 *
	 * @param WP_Post $item The current item.
	 * @param int     $level The current level Not used for non hierarchical CPTs.
	 *
	 * @return void
	 */
	public function single_row( $item, $level = 0 ) {
		$classes = 'iedit author-' . ( get_current_user_id() === (int) $item->post_author ? 'self' : 'other' );

		$lock_holder = wp_check_post_lock( $item->ID );

		if ( $lock_holder ) {
			$classes .= ' wp-locked';
		}

		if ( $item->post_parent ) {
			$count    = count( get_post_ancestors( $item->ID ) );
			$classes .= ' level-' . $count;
		} else {
			$classes .= ' level-0';
		}

		$classes .= $item->post_status;

		echo '<tr id="tec_tc_order-' . (int) $item->ID . '" class="' . esc_attr( $classes ) . '">';
		$this->single_row_columns( tec_tc_get_order( $item ) );
		echo '</tr>';
	}

	/**
	 * Get the list of bulk actions available.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return [];
	}

	/**
	 * @global array $locked_post_status This seems to be deprecated.
	 * @global array $avail_post_stati
	 * @return array
	 */
	protected function get_views() {
		global $locked_post_status, $avail_post_stati;

		$post_type = $this->screen->post_type;

		if ( ! empty( $locked_post_status ) ) {
			return [];
		}

		$status_links = [];
		$num_posts    = wp_count_posts( $post_type, 'readable' );
		$total_posts  = array_sum( (array) $num_posts );
		$class        = '';
		$all_args     = [ 'post_type' => $post_type ];

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( [ 'show_in_admin_all_list' => false ] ) as $state ) {
			$total_posts -= $num_posts->$state;
		}

		$all_inner_html = sprintf(
			/* translators: %s: Number of posts. */
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_posts,
				'posts'
			),
			number_format_i18n( $total_posts )
		);

		$status_links['all'] = [
			'url'     => esc_url( add_query_arg( $all_args, 'edit.php' ) ),
			'label'   => $all_inner_html,
			'current' => empty( $class ) && ( $this->is_base_request() || isset( $_REQUEST['all_posts'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		];

		foreach ( get_post_stati( [ 'show_in_admin_status_list' => true ], 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( ! in_array( $status_name, $avail_post_stati, true ) || empty( $num_posts->$status_name ) ) {
				continue;
			}

			if ( isset( $_REQUEST['post_status'] ) && $status_name === $_REQUEST['post_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$class = 'current';
			}

			$status_args = [
				'post_status' => $status_name,
				'post_type'   => $post_type,
			];

			$status_label = sprintf(
				translate_nooped_plural( $status->label_count, $num_posts->$status_name ),
				number_format_i18n( $num_posts->$status_name )
			);

			$status_links[ $status_name ] = [
				'url'     => esc_url( add_query_arg( $status_args, 'edit.php' ) ),
				'label'   => $status_label,
				'current' => isset( $_REQUEST['post_status'] ) && $status_name === $_REQUEST['post_status'], // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			];
		}

		$trash = $status_links['trash'] ?? null;
		if ( $trash ) {
			unset( $status_links['trash'] );
			$status_links['trash'] = $trash;
		}

		return $this->get_views_links( $status_links );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 5.2.0
	 */
	public function no_items() {
		esc_html_e( 'No matching orders found.', 'event-tickets' );
	}

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item  The current item.
	 * @param string  $column The current column.
	 *
	 * @return string
	 */
	public function column_default( $item, $column ) {
		return empty( $item->$column ) ? '??' : $item->$column;
	}

	/**
	 * Returns the order status.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item The current item.
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
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		$dt = $item->post_date;

		if ( ! $dt ) {
			return '&ndash;';
		}

		$ts = strtotime( $dt );

		if ( ! $ts ) {
			return '&ndash;';
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $ts > strtotime( '-1 day', time() ) && $ts <= time() ) {
			$show_date = sprintf(
				/* translators: %s: human-readable time difference */
				_x( '%s ago', '%s = human-readable time difference', 'woocommerce' ),
				human_time_diff( $ts, time() )
			);
		} else {
			$show_date = \Tribe__Date_Utils::reformat( $ts, \Tribe__Date_Utils::DATEONLYFORMAT );
		}

		return sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( \Tribe__Date_Utils::reformat( $ts, 'c' ) ),
			esc_html( \Tribe__Date_Utils::reformat( $ts, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 * Handler for the items column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_items( $item ) {
		$output = '';

		if ( ! isset( $item->items ) ) {
			return $output;
		}

		foreach ( $item->items as $cart_item ) {
			$ticket   = \Tribe__Tickets__Tickets::load_ticket_object( $cart_item['ticket_id'] );
			$name     = esc_html( $ticket->name );
			$quantity = esc_html( (int) $cart_item['quantity'] );
			$output  .= "<div class='tribe-line-item'>{$quantity} - {$name}</div>";
		}

		return $output;
	}

	/**
	 * Handler for the order column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_order( $item ) {
		if ( ! $item instanceof WP_Post ) {
			return '';
		}

		if ( ! isset( $item->purchaser ) ) {
			return $item->ID;
		}

		return sprintf( '#%1$s %2$s (%3$s)', $item->ID, $item->purchaser['full_name'], $item->purchaser['email'] );
	}

	/**
	 * Handler for the total column
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $item The current item.
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
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_gateway_order_id( $item ) {
		$gateway = tribe( Manager::class )->get_gateway_by_key( $item->gateway );

		if ( $gateway instanceof Free_Gateway ) {
			return esc_html__( 'N\A', 'event-tickets' );
		}

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
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_gateway( $item ) {
		$gateway = tribe( Manager::class )->get_gateway_by_key( $item->gateway );

		if ( $gateway instanceof Free_Gateway ) {
			return esc_html__( 'Free', 'event-tickets' );
		}

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
			'total'            => 'total_value',
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
			'all'              => __( 'Search All', 'event-tickets' ),
			'gateway_order_id' => __( 'Search by Gateway ID', 'event-tickets' ),
			'id'               => __( 'Search by Order ID', 'event-tickets' ),
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
	 * Get the extra table navigation placed above or below or both the table.
	 *
	 * @since TBD
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
		<?php
		if ( 'top' === $which ) {
			ob_start();

			$this->months_dropdown( $this->screen->post_type );
			$this->gateways_dropdown( $this->screen->post_type );

			/**
			 * Fires before the Filter button on the Posts and Pages list tables.
			 *
			 * The Filter button allows sorting by date and/or category on the
			 * Posts list table, and sorting by date on the Pages list table.
			 *
			 * @since 2.1.0
			 * @since 4.4.0 The `$post_type` parameter was added.
			 * @since 4.6.0 The `$which` parameter was added.
			 *
			 * @param string $post_type The post type slug.
			 * @param string $which     The location of the extra table nav markup:
			 *                          'top' or 'bottom' for WP_Posts_List_Table,
			 *                          'bar' for WP_Media_List_Table.
			 */
			do_action( 'restrict_manage_posts', $this->screen->post_type, $which );

			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, StellarWP.XSS.EscapeOutput.OutputNotEscaped
				submit_button( __( 'Filter' ), '', 'filter_action', false, [ 'id' => 'post-query-submit' ] );
			}
		}

		if ( ! empty( $_GET['post_status'] ) && 'trash' === $_GET['post_status'] && $this->has_items() // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_others_posts )
		) {
			submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false );
		}
		?>
		</div>
		<?php
		/**
		 * Fires immediately following the closing "actions" div in the tablenav for the posts
		 * list table.
		 *
		 * @since 4.4.0
		 *
		 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
		 */
		do_action( 'manage_posts_extra_tablenav', $which );
	}

	/**
	 * Displays a dropdown for filtering items in the list table by month.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb      $wpdb      WordPress database abstraction object.
	 *
	 * @param string $post_type The post type.
	 */
	protected function gateways_dropdown( $post_type ) {
		global $wpdb;

		/**
		 * Filters whether to remove the 'Months' drop-down from the post list table.
		 *
		 * @since 4.2.0
		 *
		 * @param bool   $disable   Whether to disable the drop-down. Default false.
		 * @param string $post_type The post type.
		 */
		if ( apply_filters( 'tec_tc_orders_disable_gateways_dropdown', false, $post_type ) ) {
			return;
		}

		/**
		 * Filters whether to short-circuit performing the months dropdown query.
		 *
		 * @since 5.7.0
		 *
		 * @param object[]|false $months   'Months' drop-down results. Default false.
		 * @param string         $post_type The post type.
		 */
		$gateways = apply_filters( 'tec_tc_orders_pre_gateways_dropdown_query', false, $post_type );

		if ( ! is_array( $gateways ) ) {
			$gateways = tec_tc_orders()->get_distinct_values_of_key( 'gateway' );
		}

		/**
		 * Filters the 'Months' drop-down results.
		 *
		 * @since 3.7.0
		 *
		 * @param object[] $months    Array of the months drop-down query results.
		 * @param string   $post_type The post type.
		 */
		$gateways = apply_filters( 'tec_tc_orders_gateways_dropdown_results', $gateways, $post_type );

		$gateways_count = count( $gateways );

		if ( ! $gateways_count || 1 == $gateways_count ) {
			return;
		}

		$g = $_GET['tec_tc_gateway'] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! in_array( $g, $gateways, true ) ) {
			$g = '';
		}
		?>
		<label for="tec-tc-filter-by-gateway" class="screen-reader-text"><?php esc_html_e( 'Filter By Gateway', 'event-tickets' ); ?></label>
		<select name="tec_tc_gateway" id="tec-tc-filter-by-gateway">
			<option<?php selected( $g, '' ); ?> value=""><?php esc_html_e( 'All Gateways', 'event-tickets' ); ?></option>
		<?php
		foreach ( $gateways as $gateway ) {

			printf(
				"<option %s value='%s'>%s</option>\n",
				selected( $g, $gateway, false ),
				esc_attr( $gateway ),
				esc_html( ucfirst( $gateway ) )
			);
		}
		?>
		</select>
		<?php
	}
}
