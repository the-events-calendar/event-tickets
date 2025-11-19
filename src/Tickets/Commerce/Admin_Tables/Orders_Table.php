<?php
/**
 * Orders Table V2
 *
 * @since 5.13.0
 *
 * @package Tribe\Tickets\Commerce\Admin_Tables
 */

namespace TEC\Tickets\Commerce\Admin_Tables;

use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use Tribe__Date_Utils;
use WP_Post;
use WP_User;
use Tribe__Tickets__Tickets;
use WP_Posts_List_Table;

if ( ! class_exists( 'WP_List_Table' ) || ! class_exists( 'WP_Posts_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

/**
 * Class Admin Tables for Orders.
 *
 * @since 5.13.0
 */
class Orders_Table extends WP_Posts_List_Table {

	use Is_Ticket;

	/**
	 * The current post ID
	 *
	 * @since 5.13.0
	 *
	 * @var int
	 */
	public $post_id;

	/**
	 * Orders Table constructor.
	 *
	 * @since 5.13.0
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
	 * Displays the search box.
	 *
	 * @since 5.13.0
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( tribe_get_request_var( 'search', '' ) ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( tribe_get_request_var( 'orderby', '' ) ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( tribe_get_request_var( 'orderby', '' ) ) . '" />';
		}
		if ( ! empty( tribe_get_request_var( 'order', '' ) ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( tribe_get_request_var( 'order', '' ) ) . '" />';
		}

		$text = __( 'Search Orders', 'event-tickets' );
		?>
			<div class="search-box">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
				<input
					type="search"
					id="<?php echo esc_attr( $input_id ); ?>"
					name="search"
					value="<?php echo esc_attr( wp_unslash( tribe_get_request_var( 'search', '' ) ) ); ?>"
					placeholder="<?php esc_attr_e( 'ID or Email', 'event-tickets' ); ?>"
				/>
				<?php submit_button( $text, '', '', false, [ 'id' => 'search-submit' ] ); ?>
			</div>
		<?php
	}

	/**
	 * Generates the required HTML for a list of row action links.
	 *
	 * @since 5.13.0
	 *
	 * Remove method to let the actions be displayed.
	 *
	 * @param string[] $actions        An array of action links.
	 * @param bool     $always_visible Whether the actions should be always visible.
	 *
	 * @return string The HTML for the row actions.
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		return '<button type="button" class="toggle-row"><span class="screen-reader-text">' .
			/* translators: Hidden accessibility text for the row toggle. */
			__( 'Show more details', 'event-tickets' ) .
		'</span></button>';
	}

	/**
	 * Overrides the list of CSS classes for the WP_List_Table table tag.
	 * This function is not hookable in core, so it needs to be overridden!
	 *
	 * @since 5.13.0
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
		 * @since 5.13.0
		 *
		 * @param array $classes The array of classes to be applied.
		 */
		return apply_filters( 'tec_tickets_commerce_orders_table_classes', $classes );
	}

	/**
	 * Returns the  list of columns.
	 *
	 * @since 5.13.0
	 *
	 * @return array An associative array in the format [ <slug> => <title> ]
	 */
	public function get_columns() {
		/**
		 * Filters the list of columns for the Tickets Commerce order report.
		 *
		 * @since 5.13.0
		 *
		 * @param array $columns List of columns.
		 */
		return apply_filters(
			'tec_tickets_commerce_orders_table_columns',
			[
				'order'       => __( 'Order', 'event-tickets' ),
				'status'      => __( 'Status', 'event-tickets' ),
				'items'       => __( 'Items', 'event-tickets' ),
				'total'       => __( 'Total', 'event-tickets' ),
				'date'        => __( 'Date', 'event-tickets' ),
				'post_parent' => __( 'Event', 'event-tickets' ),
				'gateway'     => __( 'Gateway', 'event-tickets' ),
			]
		);
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 5.13.0
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

		$classes .= ' ' . $item->post_status;

		echo '<tr id="' . esc_attr( Order::POSTTYPE ) . '-' . (int) $item->ID . '" class="' . esc_attr( $classes ) . '">';
		$this->single_row_columns( tec_tc_get_order( $item ) );
		echo '</tr>';
	}

	/**
	 * Get the list of bulk actions available.
	 *
	 * @since 5.13.0
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return [];
	}

	/**
	 * Get the views available on the table.
	 *
	 * @since 5.13.0
	 *
	 * @global array $locked_post_status This seems to be deprecated.
	 * @global array $avail_post_stati
	 *
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
			$total_posts -= isset( $num_posts->$state ) ? $num_posts->$state : 0;
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

		$is_post_status_any   = ! isset( $_REQUEST['post_status'] ) || in_array( $_REQUEST['post_status'], [ 'all', 'any' ], true ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_post_status_all   = isset( $_REQUEST['all_posts'] ) && $_REQUEST['all_posts']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$is_all_status_active = empty( $class ) && ( $this->is_base_request() || $is_post_status_all || $is_post_status_any );

		$status_links['all'] = [
			'url'     => esc_url( add_query_arg( $all_args, 'edit.php' ) ),
			'label'   => $all_inner_html,
			'current' => $is_all_status_active,
		];

		foreach ( get_post_stati( [ 'show_in_admin_status_list' => true ], 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			$all_grouped_statuses = tribe( Status_Handler::class )->get_group_of_statuses_by_slug( '', $status_name );

			$total_posts_in_status = 0;

			foreach ( $all_grouped_statuses as $grouped_status ) {
				$total_posts_in_status += isset( $num_posts->$grouped_status ) ? $num_posts->$grouped_status : 0;
			}

			$num_posts->$status_name = $total_posts_in_status;

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

		/**
		 * Filters the list of views available on the Tickets Commerce order report.
		 *
		 * @since 5.13.0
		 *
		 * @param array $status_links List of views.
		 */
		return $this->get_views_links( apply_filters( 'tec_tickets_commerce_orders_table_views', $status_links ) );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 5.13.0
	 *
	 * @return void
	 */
	public function no_items() {
		/**
		 * Filters the message to be displayed when there are no items in the Tickets Commerce order report.
		 *
		 * @since 5.13.0
		 *
		 * @param string $message The message to be displayed.
		 */
		apply_filters( 'tec_tickets_commerce_orders_table_no_items', esc_html_e( 'No matching orders found.', 'event-tickets' ) );
	}

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @since 5.13.0
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
	 * @since 5.13.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$status = tribe( Status_Handler::class )->get_by_wp_slug( $item->post_status, false );

		ob_start();
		?>
		<mark class="tribe-tickets-commerce-order-status status-<?php echo esc_attr( $status->get_slug() ); ?>">
			<?php
			$dashicon = '';
			switch ( $status->get_slug() ) {
				case 'completed':
					$dashicon = 'yes';
					break;
				case 'refunded':
					$dashicon = 'undo';
					break;
				case 'failed':
				case 'denied':
					$dashicon = 'no-alt';
					break;
				case 'pending':
					$dashicon = 'clock';
					break;
			}

			if ( $dashicon ) {
				printf( '<span class="dashicons dashicons-%s"></span>', esc_attr( $dashicon ) );
			}
			?>
			<span>
				<?php echo esc_html( $status->get_name() ); ?>
			</span>
		</mark>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handler for the date column
	 *
	 * @since 5.13.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		// We work on GMT, we display on wp_timezone().
		$dt = $item->post_date_gmt;

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
				_x( '%s ago', '%s = human-readable time difference', 'event-tickets' ),
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
	 * @since 5.13.0
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
			// Check if 'type' exists and proceed only if it's empty or equals 'ticket'.
			if ( ! $this->is_ticket( $cart_item ) ) {
				continue;
			}

			$ticket   = Tribe__Tickets__Tickets::load_ticket_object( $cart_item['ticket_id'] );
			$quantity = esc_html( (int) $cart_item['quantity'] );

			if ( ! $ticket ) {
				$name    = _n( 'Ticket', 'Tickets', $quantity, 'event-tickets' );
				$output .= "<div class='tribe-line-item'>{$quantity} {$name}</div>";
				continue;
			}

			$name    = esc_html( $ticket->name );
			$output .= "<div class='tribe-line-item'>{$quantity} {$name}</div>";
		}

		return $output;
	}

	/**
	 * Handler for the order column
	 *
	 * @since 5.13.0
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

		return sprintf(
			'<a href="%3$s">#%1$s - %2$s</a>',
			esc_html( $item->ID ),
			esc_html( $item->purchaser['email'] ),
			esc_url( get_edit_post_link( $item->ID ) )
		);
	}

	/**
	 * Handler for the total column
	 *
	 * @since 5.13.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_total( $item ) {
		$original = tribe( Order::class )->get_value( $item->ID, true );
		$current  = tribe( Order::class )->get_value( $item->ID );

		if ( $original !== $current ) {
			return sprintf(
				'<div class="tec-tickets-commerce-price-container"><ins><span class="tec-tickets-commerce-price">%s</span></ins><del><span class="tec-tickets-commerce-price">%s</span></del></div>',
				esc_html( $current ),
				esc_html( $original )
			);
		}

		return sprintf(
			'<div class="tec-tickets-commerce-price-container"><ins><span class="tec-tickets-commerce-price">%s</span></ins></div>',
			esc_html( $current )
		);
	}

	/**
	 * Handler for the post parent column.
	 *
	 * @since 5.13.0
	 *
	 * @param WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_post_parent( $item ) {
		$events = tribe( Order::class )->get_events( $item->ID );

		if ( empty( $events ) ) {
			return '';
		}

		$output = '';

		foreach ( $events as $event ) {
			if ( ( ! in_array( $event->post_type, get_post_types( [ 'show_ui' => true ] ), true ) ) ) {
				$output .= sprintf(
					'<div>%s</div>',
					esc_html( get_the_title( $event->ID ) )
				);
				continue;
			}

			if ( ! current_user_can( 'edit_post', $event->ID ) ) {
				$output .= sprintf(
					'<div>%s</div>',
					esc_html( get_the_title( $event->ID ) )
				);
				continue;
			}

			if ( 'trash' === $event->post_status ) {
				// translators: 1) is the event's title and 2) is an indication as a text that it is now trashed.
				$output .= sprintf(
					'<div>%1$s %2$s</div>',
					esc_html( get_the_title( $event->ID ) ),
					esc_html_x( '(trashed)', 'This is about an "event" related to a Tickets Commerce order that now has been trashed.', 'event-tickets' )
				);
				continue;
			}

			$output .= sprintf(
				'<div><a href="%s">%s</a></div>',
				esc_url( get_edit_post_link( $event->ID ) ),
				esc_html( get_the_title( $event->ID ) )
			);
		}

		return $output;
	}

	/**
	 * Handler for gateway order id.
	 *
	 * @since 5.13.0
	 * @since 5.13.3 Added the order URL parameter.
	 *
	 * @param WP_Post $item The current item.
	 * @param string  $order_url The order URL.
	 *
	 * @return string
	 */
	protected function column_gateway_order_id( $item, $order_url = '' ) {
		if ( empty( $order_url ) ) {
			return '';
		}

		ob_start();
		$copy_button_target = tec_copy_to_clipboard_button( $item->gateway_order_id, false );
		$copy_button        = ob_get_clean();

		return sprintf(
			'<br><span class="tribe-dashicons" aria-hidden="true">%1$s<a role="button" aria-label="%2$s" aria-describedby="%4$s" title="%2$s" href="javascript:void(0)" data-clipboard-action="copy" data-clipboard-target=".%3$s" data-notice-target=".%4$s" class="tec-copy-to-clipboard dashicons dashicons-admin-page"></a>%5$s</span>',
			esc_html( $item->gateway_order_id ),
			_x( 'Copy Payment\'s Gateway Transaction ID to your Clipboard', 'Copy payment transaction ID to clipboard.', 'event-tickets' ),
			$copy_button_target,
			str_replace( 'tec-copy-text-target-', 'tec-copy-to-clipboard-notice-content-', $copy_button_target ),
			$copy_button,
		);
	}

	/**
	 * Handler for gateway column
	 *
	 * @since 5.13.0
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

		$order_url = $gateway->get_order_controller()->get_gateway_dashboard_url_by_order( $item );

		if ( empty( $order_url ) ) {
			return $gateway::get_label();
		}

		return (
			'<a class="tribe-dashicons" href="' . esc_url( $order_url ) . '" target="_blank" rel="noopener noreferrer">' .
			esc_html( $gateway::get_label() ) .
			'<span class="dashicons dashicons-external"></span>' .
			'</a>' .
			$this->column_gateway_order_id( $item, $order_url )
		);
	}

	/**
	 * List of sortable columns.
	 *
	 * @since 5.13.0
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		/**
		 * Filters the list of sortable columns for the Tickets Commerce order report.
		 *
		 * @since 5.13.0
		 *
		 * @param array $columns List of columns that can be sorted.
		 */
		return apply_filters(
			'tec_tickets_commerce_orders_table_sortable_columns',
			[
				'order'       => 'order_id',
				'purchaser'   => 'purchaser_full_name',
				'email'       => 'purchaser_email',
				'date'        => 'purchase_time',
				'post_parent' => 'event',
				'gateway'     => 'gateway',
				'status'      => 'status',
				'total'       => 'total_value',
			]
		);
	}

	/**
	 * Get the allowed search types and their labels.
	 *
	 * @see \TEC\Tickets\Commerce\Repositories\Order_Repository for a List of valid ORM args.
	 *
	 * @since 5.13.0
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
		 * @since 5.13.0
		 *
		 * @param array $options List of ORM search types and their labels.
		 */
		return apply_filters( 'tec_tc_order_search_types', $options );
	}

	/**
	 * Get the extra table navigation placed above or below or both the table.
	 *
	 * @since 5.13.0
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions tribe-validation">
		<?php
		if ( 'top' === $which ) {
			ob_start();

			$this->date_range_dropdown( $this->screen->post_type );
			$this->gateways_dropdown( $this->screen->post_type );
			$this->post_parent_dropdown( $this->screen->post_type );
			$this->customer_dropdown( $this->screen->post_type );

			/**
			 * Fires before the Filter button on the Posts and Pages list tables.
			 *
			 * The Filter button allows sorting by date and/or category on the
			 * Posts list table, and sorting by date on the Pages list table.
			 *
			 * !!This is a wp core action!!
			 *
			 * @since 5.13.0
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
				submit_button(
					__( 'Apply Filters', 'event-tickets' ),
					'',
					'filter_action',
					false,
					[
						'id' => 'post-query-submit',
					]
				);
				?>
				<a href="<?php echo esc_url( remove_query_arg( [ 'tec_tc_date_range_from', 'tec_tc_date_range_to', 'tec_tc_gateway', 'tec_tc_events', 'tec_tc_customers' ] ) ); ?>">
					<?php esc_html_e( 'Clear All', 'event-tickets' ); ?>
				</a>
				<?php
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
		 * !!This is a wp core action!!
		 *
		 * @since 5.13.0
		 *
		 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
		 */
		do_action( 'manage_posts_extra_tablenav', $which );
	}

	/**
	 * Displays a dropdown for filtering items in the list table by date range.
	 *
	 * @since 5.13.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	protected function date_range_dropdown( $post_type ) {
		/**
		 * Filters whether to remove the 'Date Range' drop-down from the order list table.
		 *
		 * @since 5.13.0
		 *
		 * @param bool   $disable   Whether to disable the drop-down. Default false.
		 * @param string $post_type The post type.
		 */
		if ( apply_filters( 'tec_tc_orders_disable_date_range_dropdown', false, $post_type ) ) {
			return;
		}

		$date_from = sanitize_text_field( tribe_get_request_var( 'tec_tc_date_range_from', '' ) );
		$date_to   = sanitize_text_field( tribe_get_request_var( 'tec_tc_date_range_to', '' ) );

		$date_from = Tribe__Date_Utils::is_valid_date( $date_from ) ? $date_from : '';
		$date_to   = Tribe__Date_Utils::is_valid_date( $date_to ) ? $date_to : '';
		?>
		<label class="screen-reader-text" for="tec_tc_data-range-from">
			<?php esc_html_e( 'From', 'event-tickets' ); ?>
		</label>
		<input
			autocomplete="off"
			type="text"
			class="tribe-datepicker"
			name="tec_tc_date_range_from"
			id="tec_tc_data-range-from"
			size="10"
			value="<?php echo esc_attr( $date_from ); ?>"
			placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'event-tickets' ); ?>"
			data-validation-type="datepicker"
		/>
		<label for="tec_tc_data-range-to">
			<?php esc_html_e( 'to', 'event-tickets' ); ?>
		</label>
		<input
			autocomplete="off"
			type="text"
			class="tribe-datepicker"
			name="tec_tc_date_range_to"
			id="tec_tc_data-range-to"
			size="10"
			value="<?php echo esc_attr( $date_to ); ?>"
			placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'event-tickets' ); ?>"
			data-validation-type="datepicker"
		/>
		<?php
	}

	/**
	 * Displays a dropdown for filtering items in the list table by month.
	 *
	 * @since 5.13.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	protected function gateways_dropdown( $post_type ) {
		/**
		 * Filters whether to remove the 'Gateways' drop-down from the order list table.
		 *
		 * @since 5.13.0
		 *
		 * @param bool   $disable   Whether to disable the drop-down. Default false.
		 * @param string $post_type The post type.
		 */
		if ( apply_filters( 'tec_tc_orders_disable_gateways_dropdown', false, $post_type ) ) {
			return;
		}

		/**
		 * Filters whether to short-circuit performing the gateways dropdown query.
		 *
		 * @since 5.13.0
		 *
		 * @param array|false $gateways  'Gateways' drop-down results. Default false.
		 * @param string      $post_type The post type.
		 */
		$gateways = apply_filters( 'tec_tc_orders_pre_gateways_dropdown_query', false, $post_type );

		if ( ! is_array( $gateways ) ) {
			$gateways = tec_tc_orders()->get_distinct_values_of_key( 'gateway' );
		}

		/**
		 * Filters the 'Gateways' drop-down results.
		 *
		 * @since 5.13.0
		 *
		 * @param array    $gateways  Array of gateways.
		 * @param string   $post_type The post type.
		 */
		$gateways = apply_filters( 'tec_tc_orders_gateways_dropdown_results', $gateways, $post_type );

		$gateways_count = count( $gateways );

		if ( ! $gateways_count || 1 == $gateways_count ) {
			return;
		}

		$g = tribe_get_request_var( 'tec_tc_gateway', '' );

		if ( ! in_array( $g, $gateways, true ) ) {
			$g = '';
		}

		$gateways_formatted = [
			'' => esc_html__( 'All Gateways', 'event-tickets' ),
		];

		foreach ( $gateways as $gateway ) {
			$gateways_formatted[ $gateway ] = ucfirst( $gateway );
		}

		?>
		<label for="tec_tc_gateway-select" class="screen-reader-text"><?php esc_html_e( 'Filter By Gateway', 'event-tickets' ); ?></label>
		<select
			name="tec_tc_gateway"
			id='tec_tc_gateway-select'
			class='tribe-dropdown'
			data-prevent-clear='true'
		>
			<?php foreach ( $gateways_formatted as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $g, $key ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Displays a dropdown for filtering items in the list table by month.
	 *
	 * @since 5.13.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	protected function post_parent_dropdown( $post_type ) {
		/**
		 * Filters whether to remove the 'Event' drop-down from the order list table.
		 *
		 * @since 5.13.0
		 *
		 * @param bool   $disable   Whether to disable the drop-down. Default false.
		 * @param string $post_type The post type.
		 */
		if ( apply_filters( 'tec_tc_orders_disable_post_parent_dropdown', false, $post_type ) ) {
			return;
		}

		// Event options are being filtered in the Frontend after the user starts typing in the search box.
		// Except for when the user has already filtered by an event. We take the event ID from the URL and add it to the dropdown.

		$e = absint( tribe_get_request_var( 'tec_tc_events', 0 ) );

		$event = $e ? get_post( $e ) : null;

		$event = $event instanceof WP_Post ? $event : null;

		$events_formatted = [
			'' => esc_html__( 'All Events', 'event-tickets' ),
		];

		$events_formatted += $event ? [ (string) $event->ID => get_the_title( $event->ID ) ] : [];
		?>
		<label for="tec_tc_events-select" class="screen-reader-text"><?php esc_html_e( 'Filter By Event', 'event-tickets' ); ?></label>
		<select
			name="tec_tc_events"
			id='tec_tc_events-select'
			class='tribe-dropdown'
			data-freeform="1"
			data-force-search="1"
			data-searching-placeholder="<?php esc_attr_e( 'Searching...', 'event-tickets' ); ?>"
			data-source="tec_tickets_list_ticketables_ajax"
			data-source-nonce="<?php echo esc_attr( wp_create_nonce( 'tribe_dropdown' ) ); ?>"
			data-ajax-delay="400"
			data-ajax-cache="1"
			data-minimum-input-length="3"
			data-tags="0"
		>
			<?php foreach ( $events_formatted as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $e, $key ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Displays a dropdown for filtering items in the list table by month.
	 *
	 * @since 5.13.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	protected function customer_dropdown( $post_type ) {
		/**
		 * Filters whether to remove the 'Customer' drop-down from the order list table.
		 *
		 * @since 5.13.0
		 *
		 * @param bool   $disable   Whether to disable the drop-down. Default false.
		 * @param string $post_type The post type.
		 */
		if ( apply_filters( 'tec_tc_orders_disable_customer_dropdown', false, $post_type ) ) {
			return;
		}

		// Customer options are being filtered in the Frontend after the user starts typing in the search box.
		// Except for when the user has already filtered by a customer. We take the customer ID from the URL and add it to the dropdown.

		$customer = absint( tribe_get_request_var( 'tec_tc_customers', 0 ) );

		$customers_formatted = [
			'' => esc_html__( 'All Customers', 'event-tickets' ),
		];

		$customer_instance = $customer ? get_user_by( 'ID', $customer ) : null;
		$customer_instance = $customer_instance instanceof WP_User ? $customer_instance : null;

		$customers_formatted += $customer_instance ? [ (string) $customer_instance->ID => $customer_instance->display_name . ' (' . $customer_instance->user_email . ' )' ] : [];
		?>
		<label for="tec_tc_customers-select" class="screen-reader-text"><?php esc_html_e( 'Filter By Customer', 'event-tickets' ); ?></label>
		<select
			name="tec_tc_customers"
			id='tec_tc_customers-select'
			class='tribe-dropdown'
			data-freeform="1"
			data-force-search="1"
			data-searching-placeholder="<?php esc_attr_e( 'Searching...', 'event-tickets' ); ?>"
			data-source="tec_tc_order_table_customers"
			data-source-nonce="<?php echo esc_attr( wp_create_nonce( 'tribe_dropdown' ) ); ?>"
			data-ajax-delay="400"
			data-ajax-cache="1"
			data-minimum-input-length="3"
			data-tags="0"
		>
			<?php foreach ( $customers_formatted as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $customer, $key ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}
