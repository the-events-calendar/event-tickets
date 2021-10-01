<?php
/**
 * Attendees Table
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets\Commerce\Admin_Tables;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket;
use WP_List_Table;

/**
 * Class Admin Tables for Attendees
 */
class Attendees extends WP_List_Table {

	/**
	 * Legacy Attendees Table Controller
	 *
	 * @var \Tribe__Tickets__Attendees_Table
	 */
	private $legacy_attendees_table;

	/**
	 * The name attribute of the search box input
	 *
	 * @var string
	 */
	private $search_box_input_name = 's';

	/**
	 *  Documented in WP_List_Table
	 */
	public function __construct() {
		$args = [
			'singular' => 'attendee',
			'plural'   => 'attendees',
			'ajax'     => true,
		];

		$this->legacy_attendees_table = new \Tribe__Tickets__Attendees_Table();

		parent::__construct( $args );
	}

	/**
	 * Enqueues the JS and CSS for the attendees page in the admin
	 *
	 * @since TBD
	 *
	 * @param string $hook The current admin page.
	 *
	 * @todo  this needs to use tribe_assets()
	 */
	public function enqueue_assets( $hook ) {
		/**
		 * Filter the Page Slugs the Attendees Page CSS and JS Loads
		 *
		 * @param array array( $this->page_id ) an array of admin slugs
		 */
		if ( ! in_array( $hook, apply_filters( 'tribe_filter_attendee_page_slug', [ $this->page_id ] ) ) ) {
			return;
		}

		$resources_url = plugins_url( 'src/resources', dirname( dirname( __FILE__ ) ) );

		wp_enqueue_style( 'tickets-report-css', $resources_url . '/css/tickets-report.css', [], \Tribe__Tickets__Main::instance()->css_version() );
		wp_enqueue_style( 'tickets-report-print-css', $resources_url . '/css/tickets-report-print.css', [], \Tribe__Tickets__Main::instance()->css_version(), 'print' );
		wp_enqueue_script( $this->slug() . '-js', $resources_url . '/js/tickets-attendees.js', [ 'jquery' ], \Tribe__Tickets__Main::instance()->js_version(), true );

		add_thickbox();

		$move_url_args = [
			'dialog'    => \Tribe__Tickets__Main::instance()->move_tickets()->dialog_name(),
			'check'     => wp_create_nonce( 'move_tickets' ),
			'TB_iframe' => 'true',
		];

		$config_data = [
			'nonce'             => wp_create_nonce( 'email-attendee-list' ),
			'required'          => esc_html__( 'You need to select a user or type a valid email address', 'event-tickets' ),
			'sending'           => esc_html__( 'Sending...', 'event-tickets' ),
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			'checkin_nonce'     => wp_create_nonce( 'checkin' ),
			'uncheckin_nonce'   => wp_create_nonce( 'uncheckin' ),
			'cannot_move'       => esc_html__( 'You must first select one or more tickets before you can move them!', 'event-tickets' ),
			'move_url'          => add_query_arg( $move_url_args ),
			'confirmation'      => esc_html__( 'Please confirm that you would like to delete this attendee.', 'event-tickets' ),
			'bulk_confirmation' => esc_html__( 'Please confirm you would like to delete these attendees.', 'event-tickets' ),
		];

		/**
		 * Allow filtering the configuration data for the Attendee objects on Attendees report page.
		 *
		 * @since TBD
		 *
		 * @param array $config_data List of configuration data to be localized.
		 */
		$config_data = apply_filters( 'tribe_tickets_attendees_report_js_config', $config_data );

		wp_localize_script( $this->slug() . '-js', 'Attendees', $config_data );
	}

	/**
	 * Loads the WP-Pointer for the Attendees screen
	 *
	 * @since TBD
	 *
	 * @param string $hook The current admin page.
	 */
	public function load_pointers( $hook ) {
		if ( $hook != $this->page_id ) {
			// return;
		}

		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$pointer   = [];

		if ( version_compare( get_bloginfo( 'version' ), '3.3', '>' ) && ! in_array( 'attendees_filters', $dismissed ) ) {
			$pointer = [
				'pointer_id' => 'attendees_filters',
				'target'     => '#screen-options-link-wrap',
				'options'    => [
					'content'  => sprintf( '<h3> %s </h3> <p> %s </p>', esc_html__( 'Columns', 'event-tickets' ), esc_html__( 'You can use Screen Options to select which columns you want to see. The selection works in the table below, in the email, for print and for the CSV export.', 'event-tickets' ) ),
					'position' => [
						'edge'  => 'top',
						'align' => 'right',
					],
				],
			];
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
		}

		wp_localize_script( $this->slug() . '-js', 'AttendeesPointer', $pointer );
	}

	/**
	 * Returns the  list of columns.
	 *
	 * @since TBD
	 * @return array An associative array in the format [ <slug> => <title> ]
	 */
	public function get_columns() {
		$columns = [
			'ticket'        => __( 'Ticket', 'event-tickets' ),
			'primary_info'  => __( 'Primary Information', 'event-tickets' ),
			'security_code' => __( 'Security Code', 'event-tickets' ),
			'status'        => __( 'Status', 'event-tickets' ),
			'check_in'      => __( 'Check In', 'event-tickets' ),
		];

		return $columns;
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since TBD
	 */
	public function prepare_items() {
		$post_id = tribe_get_request_var( 'post_id', 0 );
		$post_id = tribe_get_request_var( 'event_id', $post_id );

		$this->post_id = $post_id;

		$search = tribe_get_request_var( $this->search_box_input_name );
		$page   = absint( tribe_get_request_var( 'paged', 0 ) );

		$arguments = [
			'page'               => $page,
			'posts_per_page'     => $this->per_page_option,
			'return_total_found' => true,
		];

		if ( ! empty( $search ) ) {
			$arguments['search'] = $search;

			$search_keys = array_keys( $this->get_search_options() );

			/**
			 * Filters the item keys that can be used to filter attendees while searching them.
			 *
			 * @since TBD
			 * @since TBD
			 *
			 * @param array  $search_keys The keys that can be used to search attendees.
			 * @param array  $items       (deprecated) The attendees list.
			 * @param string $search      The current search string.
			 */
			$search_keys = apply_filters( 'tribe_tickets_search_attendees_by', $search_keys, [], $search );

			// Default selection.
			$search_key = 'purchaser_name';

			$search_type = sanitize_text_field( tribe_get_request_var( 'tribe_attendee_search_type' ) );

			if (
				$search_type
				&& in_array( $search_type, $search_keys, true )
			) {
				$search_key = $search_type;
			}

			$search_like_keys = [
				'purchaser_name',
				'purchaser_email',
				'holder_name',
				'holder_email',
			];

			/**
			 * Filters the item keys that support LIKE matching to filter attendees while searching them.
			 *
			 * @since TBD
			 *
			 * @param array  $search_like_keys The keys that support LIKE matching.
			 * @param array  $search_keys      The keys that can be used to search attendees.
			 * @param string $search           The current search string.
			 */
			$search_like_keys = apply_filters( 'tribe_tickets_search_attendees_by_like', $search_like_keys, $search_keys, $search );

			// Update search key if it supports LIKE matching.
			if ( in_array( $search_key, $search_like_keys, true ) ) {
				$search_key .= '__like';
				$search     = '%' . $search . '%';
			}

			// Only get matches that have search phrase in the key.
			$arguments['by'] = [
				$search_key => [
					$search,
				],
			];
		}

		if ( ! empty( $post_id ) ) {
			$arguments['events'] = $post_id;
		}

		$attendee_repos = \tec_tc_attendees( 'all' );
		$rsvp           = [];

		foreach ( $attendee_repos as $repo ) {
			$repo->by( 'event', $post_id );
			$repo->by_args( $arguments );
			$rsvp = array_merge( $rsvp, $repo->all() );
		}

		foreach ( $rsvp as $attendee ) {
			$attendees[] = tribe( Attendee::class )->get_attendee( $attendee );
		}

		$pagination_args = [
			'total_items' => $total_items,
			'per_page'    => $this->per_page_option,
		];

		if ( ! empty( $this->items ) ) {
			$pagination_args['total_items'] = count( $this->items );
		}

		$this->items = $attendees;

		$this->set_pagination_args( $pagination_args );
	}

	/**
	 * Get the allowed search types and their descriptions.
	 *
	 * @since TBD
	 *
	 * @return array
	 * @see   \Tribe__Tickets__Attendee_Repository::__construct() List of valid ORM args.
	 *
	 */
	private function get_search_options() {
		return [
			'purchaser_name'  => esc_html_x( 'Search by Purchaser Name', 'Attendees Table search options', 'event-tickets' ),
			'purchaser_email' => esc_html_x( 'Search by Purchaser Email', 'Attendees Table search options', 'event-tickets' ),
			'holder_name'     => esc_html_x( 'Search by Ticket Holder Name', 'Attendees Table search options', 'event-tickets' ),
			'holder_email'    => esc_html_x( 'Search by Ticket Holder Email', 'Attendees Table search options', 'event-tickets' ),
			'user'            => esc_html_x( 'Search by User ID', 'Attendees Table search options', 'event-tickets' ),
			'order_status'    => esc_html_x( 'Search by Order Status', 'Attendees Table search options', 'event-tickets' ),
			'order'           => esc_html_x( 'Search by Order ID', 'Attendees Table search options', 'event-tickets' ),
			'security_code'   => esc_html_x( 'Search by Security Code', 'Attendees Table search options', 'event-tickets' ),
			'ID'              => esc_html( sprintf( _x( 'Search by %s ID', 'Attendees Table search options', 'event-tickets' ), tribe_get_ticket_label_singular( 'attendees_table_search_box_ticket_id' ) ) ),
			'product_id'      => esc_html_x( 'Search by Product ID', 'Attendees Table search options', 'event-tickets' ),
		];
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since TBD
	 *
	 * @param WP_Post $item The current item.
	 */
	public function single_row( $item ) {
		echo '<tr class="' . esc_attr( $item->post_status ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Content for the ticket column
	 *
	 * @since TBD
	 *
	 * @param array $item the array of row information.
	 *
	 * @return string
	 */
	public function column_ticket( $item ) {
		$unique_id = tribe( Attendee::class )->get_unique_id( $item );
		$ticket    = get_post( tribe( Attendee::class )->get_ticket_id( $item ) );

		return esc_html( "$unique_id [#$item->ID] - $ticket->post_title" );
	}

	/**
	 * Content for the primary info column
	 *
	 * @since TBD
	 *
	 * @param array $item the array of row information.
	 *
	 * @return string
	 */
	public function column_primary_info( $item ) {

		$name  = $item->holder_name ?? '';
		$email = $item->holder_email ?? '';

		return sprintf(
			'
				<div class="purchaser_name">%1$s</div>
				<div class="purchaser_email">%2$s</div>
			',
			esc_html( $name ),
			esc_html( $email )
		);
	}

	/**
	 * Content for the security code column
	 *
	 * @since TBD
	 *
	 * @param array $item the array of row information.
	 *
	 * @return string
	 */
	public function column_security_code( $item ) {
		$security_code = tribe( Attendee::class )->get_security_code( $item );

		return esc_html( $security_code );
	}

	/**
	 * Content for the status column
	 *
	 * @since TBD
	 *
	 * @param array $item the array of row information.
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$status = tribe( Attendee::class )->get_status( $item );

		return esc_html( $status );
	}

	/**
	 * Content for the check in column
	 *
	 * @since TBD
	 *
	 * @param array $item the array of row information.
	 *
	 * @return false|string
	 */
	public function column_check_in( $item ) {
		return $this->legacy_attendees_table->column_check_in( (array) $item );
	}

}
