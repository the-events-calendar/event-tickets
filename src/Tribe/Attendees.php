<?php

use TEC\Tickets\Event;
use Tribe__Utils__Array as Arr;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket;
use Tribe__Tickets__Global_Stock as Global_Stock;
use TEC\Tickets\Admin\Attendees\Page as Attendees_Page;

/**
 * Handles most actions related to an attendee or multiple attendees.
 */
class Tribe__Tickets__Attendees {
	/**
	 * Hook of the admin page for attendees
	 *
	 * @since 4.6.2
	 *
	 * @var string
	 */
	public $page_id;

	/**
	 * WP_Post_List children for Attendees
	 *
	 * @since 4.6.2
	 *
	 * @var Tribe__Tickets__Attendees_Table
	 */
	public $attendees_table;

	/**
	 * Hooks all the required actions and filters in WordPress
	 *
	 * @since 4.6.2
	 *
	 * @return void
	 */
	public function hook() {
		// Register before the default priority of 10 to avoid submenu hook issues.
		add_action( 'admin_menu', [ $this, 'register_page' ], 5 );

		add_action( 'tribe_report_page_after_text_label', [ $this, 'include_export_button_title' ], 25, 2 );
		add_action( 'tribe_tabbed_view_heading_after_text_label', [ $this, 'include_export_button_title' ], 25, 2 );
		add_action( 'tribe_events_tickets_attendees_totals_bottom', [ $this, 'print_checkedin_totals' ], 0 );
		add_action( 'tribe_tickets_attendees_event_details_list_top', [ $this, 'event_details_top' ], 20 );
		add_action( 'tribe_tickets_plus_report_event_details_list_top', [ $this, 'event_details_top' ], 20 );
		add_action( 'tribe_tickets_report_event_details_list_top', [ $this, 'event_details_top' ], 20 );

		add_action( 'tribe_tickets_attendees_event_details_list_top', [ $this, 'event_action_links' ], 25 );
		add_action( 'tribe_tickets_plus_report_event_details_list_top', [ $this, 'event_action_links' ], 25 );
		add_action( 'tribe_tickets_register_attendees_page', [ $this, 'add_dynamic_parent' ] );
		add_action( 'tribe_tickets_report_event_details_list_top', [ $this, 'event_action_links' ], 25 );

		add_filter( 'post_row_actions', [ $this, 'filter_admin_row_actions' ] );
		add_filter( 'page_row_actions', [ $this, 'filter_admin_row_actions' ] );
	}

	/**
	 * Returns the Attendees Post Type Slug (mostly used for RSVP)
	 *
	 * @since 4.6.2
	 *
	 * @return string
	 */
	public function slug() {
		return 'tickets-attendees';
	}

	/**
	 * Returns the current post being handled.
	 *
	 * @since 4.6.2
	 *
	 * @return array|bool|null|WP_Post
	 */
	public function get_post() {
		return empty( $this->attendees_table->event ) ? false : $this->attendees_table->event;
	}

	/**
	 * Injects event post type
	 *
	 * @since 4.6.2
	 *
	 * @param int $event_id
	 */
	public function event_details_top( $event_id ) {
		$pto = get_post_type_object( get_post_type( $event_id ) );

		if ( $pto === null ) {
			return;
		}

		$label = strtolower( $pto->labels->singular_name );

		/**
		 * Filters the label used in the Attendees page for the event post type.
		 *
		 * @since 5.8.0
		 *
		 * @param string $label    The label used in the Attendees page for the event post type.
		 * @param int    $event_id The ID of the post the Attendees page is for.
		 * @param WP_Post_Type|null The post type object.
		 */
		$label = apply_filters( 'tec_tickets_attendees_event_details_top_label', $label, $event_id, $pto );

		echo '
			<li class="post-type">
				<strong>' . esc_html__( 'Post type', 'event-tickets' ) . ': </strong>
				' . esc_html( $label ) . '
			</li>
		';
	}

	/**
	 * Injects action links into the attendee screen.
	 *
	 * @since 4.6.2
	 *
	 * @param int|WP_Post $event_id The Post ID of the event.
	 */
	public function event_action_links( $event_id ) {

		/**
		 * Allows for control of the specific "edit post" URLs used for event Sales and Attendees Reports.
		 *
		 * @since 4.6.2
		 *
		 * @param string      $link     The default "edit post" URL.
		 * @param int|WP_Post $event_id The Post ID of the event.
		 */
		$edit_post_link = apply_filters( 'tribe_tickets_event_action_links_edit_url', get_edit_post_link( $event_id ), $event_id );

		$post     = get_post( $event_id );
		$pto      = get_post_type_object( $post->post_type );
		$singular = $pto->labels->singular_name;
		$edit     = esc_html( sprintf( _x( 'Edit %s', 'attendee event actions', 'event-tickets' ), $singular ) );
		$view     = esc_html( sprintf( _x( 'View %s', 'attendee event actions', 'event-tickets' ), $singular ) );

		$action_links = [
			'<a href="' . esc_url( $edit_post_link ) . '" title="' . esc_attr_x( 'Edit', 'attendee event actions', 'event-tickets' ) . '">' . $edit . '</a>',
			'<a href="' . esc_url( get_permalink( $event_id ) ) . '" title="' . esc_attr_x( 'View', 'attendee event actions', 'event-tickets' ) . '">' . $view . '</a>',
		];

		/**
		 * Provides an opportunity to add and remove action links from the attendee screen summary box.
		 *
		 * @param array       $action_links The action links to be displayed.
		 * @param int|WP_Post $event_id     The Post ID of the event.
		 */
		$action_links = (array) apply_filters( 'tribe_tickets_attendees_event_action_links', $action_links, $event_id );

		if ( empty( $action_links ) ) {
			return;
		}

		echo wp_kses_post( '<li class="event-actions">' . implode( ' | ', $action_links ) . '</li>' );
	}


	/**
	 * Print Check In Totals at top of Column.
	 *
	 * @since 4.6.2
	 * @since 5.6.5   Added $post_id parameter.
	 *
	 * @param int $post_id The post ID.
	 */
	public function print_checkedin_totals( $post_id ) {
		// Bail if we don't have a post ID.
		if ( ! $post_id ) {
			return;
		}

		$total_checked_in = $this->get_checkedin_total();
		$check_in_percent = $this->get_checkedin_percentage( $post_id );
		$total_attendees  = Tickets::get_event_attendees_count( $post_id );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );
		$args        = [
			'total_attendees'    => absint( $total_attendees ),
			'total_checked_in'   => absint( $total_checked_in ),
			'percent_checked_in' => $check_in_percent,
		];
		$admin_views->template( 'attendees/attendees-event/attendance-totals', $args, true );
	}

	/**
	 * Get Check In Total.
	 *
	 * @since 5.6.5
	 *
	 * @return int
	 */
	public function get_checkedin_total(): int {
		return (int) Tribe__Tickets__Main::instance()->attendance_totals()->get_total_checked_in();
	}

	/**
	 * Get Check In Percentage.
	 *
	 * @since 5.6.5
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	public function get_checkedin_percentage( $post_id ): string {
		$total_checked_in = $this->get_checkedin_total();
		$total            = Tickets::get_event_attendees_count( $post_id );

		// Remove the "Not Going" RSVPs.
		$not_going = tribe( 'tickets.rsvp' )->get_attendees_count_not_going( $post_id );
		$total     -= $not_going;

		if ( $total_checked_in === 0 || $total <= 0 ) {
			return '0%';
		}

		return round( ( $total_checked_in / $total ) * 100 ) . '%';
	}

	/**
	 * Returns the full URL to the attendees report page.
	 *
	 * @since 5.6.4 - tec_tickets_filter_event_id filter to normalize the $post_id.
	 * @since 4.6.2
	 *
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public function get_report_link( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$post_id = Event::filter_event_id( $post->ID, 'attendees-report-link' );

		$args = [
			'post_type' => $post->post_type,
			'page'      => $this->slug(),
			'event_id'  => $post_id,
		];

		$url = add_query_arg( $args, admin_url( 'edit.php' ) );

		/**
		 * Filter the Attendee Report Url
		 *
		 * @since 5.0.3
		 *
		 * @param string $url  a url to attendee report
		 * @param int    $post ->ID post id
		 */
		$url = apply_filters( 'tribe_ticket_filter_attendee_report_link', $url, $post->ID );

		return $url;
	}

	/**
	 * Adds the "attendees" link in the admin list row actions for each event.
	 *
	 * @param array $actions The actions to be displayed.
	 *
	 * @return array
	 */
	public function filter_admin_row_actions( $actions ) {
		global $post;

		// Only proceed if we're viewing a tickets-enabled post type.
		if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
			return $actions;
		}

		if ( ! $this->can_access_page( $post->ID ) ) {
			return $actions;
		}

		$tickets = Tickets::get_event_tickets( $post->ID );

		// Only proceed if there are tickets.
		if ( empty( $tickets ) ) {
			return $actions;
		}

		$url = $this->get_report_link( $post );

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
	 *
	 * @since 4.6.2
	 */
	public function register_page() {
		$cap      = 'edit_posts';
		$event_id = absint( ! empty( $_GET['event_id'] ) && is_numeric( $_GET['event_id'] ) ? $_GET['event_id'] : 0 );

		if ( ! $this->can_access_page( $event_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) && $event_id ) {
			$event = get_post( $event_id );

			if ( $event instanceof WP_Post && get_current_user_id() === (int) $event->post_author ) {
				$cap = 'read';
			}
		}

		$this->page_id = add_submenu_page(
			'',
			'Attendee list',
			'Attendee list',
			$cap,
			$this->slug(),
			[ $this, 'render' ]
		);

		$attendees_page_hook_suffix = \TEC\Tickets\Admin\Attendees\Page::$hook_suffix;

		/**
		 * @since 4.7.1
		 *
		 * @param string $page_id
		 */
		do_action( 'tribe_tickets_register_attendees_page', $this->page_id );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_pointers' ] );
		add_action( "load-{$this->page_id}", [ $this, 'screen_setup' ] );
		add_action( "load-{$attendees_page_hook_suffix}", [ $this, 'screen_setup' ] );
	}

	/**
	 * Add dynamic registered pages that belongs to Tribe__Events__Main::POSTTYPE as those are a subpage of that
	 * parent page.
	 *
	 * @since 4.7.1
	 *
	 * @param string $page_id
	 */
	public function add_dynamic_parent( $page_id = '' ) {
		if ( ! $page_id ) {
			return;
		}

		$options               = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$venue_has_tickets     = class_exists( 'Tribe__Events__Venue' ) && in_array( Tribe__Events__Venue::POSTTYPE, $options, true );
		$organizer_has_tickets = class_exists( 'Tribe__Events__Organizer' ) && in_array( Tribe__Events__Organizer::POSTTYPE, $options, true );

		global $_registered_pages;

		if ( ! is_array( $_registered_pages ) ) {
			return;
		}

		if ( $venue_has_tickets || $organizer_has_tickets ) {
			$dynamic_page                       = str_replace( 'admin_page', Tribe__Events__Main::POSTTYPE . '_page', $page_id );
			$_registered_pages[ $dynamic_page ] = true;
		}
	}

	/**
	 * Enqueues the JS and CSS for the attendees page in the admin
	 *
	 * @todo  this needs to use tribe_assets()
	 *
	 * @since 4.6.2
	 *
	 * @param string $hook The hook of the current screen.
	 *
	 */
	public function enqueue_assets( $hook ) {
		/**
		 * Filter the Page Slugs the Attendees Page CSS and JS Loads
		 *
		 * @param array $slugs an array of admin slugs
		 */
		if ( ! in_array( $hook, apply_filters( 'tribe_filter_attendee_page_slug', [ $this->page_id ] ) ) ) {
			return;
		}

		tribe_asset_enqueue_group( 'event-tickets-admin-attendees' );

		add_thickbox();
	}

	/**
	 * Loads the WP-Pointer for the Attendees screen.
	 *
	 * @since 4.6.2
	 *
	 * @param string $hook The hook of the current screen.
	 */
	public function load_pointers( $hook ) {
		if ( $hook != $this->page_id ) {
			return;
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
	 * Sets up the Attendees screen data.
	 *
	 * @since 4.6.2
	 * @since 5.13.3 Included param $all to allow for CSV export of all attendees.
	 */
	public function screen_setup() {
		$page   = tribe_get_request_var( 'page', false );
		$action = tribe_get_request_var( 'action', false );

		// When on the admin and not on the correct page bail.
		if (
			is_admin()
			&& ( $this->slug() !== $page && \TEC\Tickets\Admin\Attendees\Page::$slug !== $page )
		) {
			return;
		}

		if ( 'email' === $action ) {
			define( 'IFRAME_REQUEST', true );

			// Use iFrame Header -- WP Method.
			iframe_header();

			$event_id          = tribe_get_request_var( 'event_id' );
			$event_id          = ! is_numeric( $event_id ) ? null : absint( $event_id );
			$email_address     = tribe_get_request_var( 'email_to_address' );
			$user_id           = tribe_get_request_var( 'email_to_user' );
			$should_send_email = (bool) tribe_get_request_var( 'tribe-send-email', false );
			$type              = $email_address ? 'email' : 'user';
			$send_to           = $type === 'email' ? $email_address : $user_id;

			/** @var bool|WP_Error|string $status Email status. If false, no status is shown. */
			$status = $should_send_email ? $this->send_mail_list( $event_id, $type, $send_to ) : false;

			tribe( 'tickets.admin.views' )->template( 'attendees/attendees-email', [ 'status' => $status ] );

			// Use iFrame Footer -- WP Method.
			iframe_footer();

			// We need nothing else here.
			exit;
		} else {
			$this->attendees_table = new Tribe__Tickets__Attendees_Table();

			$this->maybe_generate_csv( 'all' === tribe_get_request_var( 'event_id' ) );

			add_filter( 'admin_title', [ $this, 'filter_admin_title' ], 10, 2 );
			add_filter( 'admin_body_class', [ $this, 'filter_admin_body_class' ] );
		}
	}

	/**
	 * Add admin body class
	 *
	 * @since 4.6.2
	 *
	 * @param string $body_classes The string with the body classes.
	 */
	public function filter_admin_body_class( $body_classes ): string {
		return $body_classes . ' plugins-php';
	}

	/**
	 * Sets the browser title for the Attendees admin page.
	 * Uses the event title.
	 *
	 * @since 4.6.2
	 *
	 * @param string $admin_title The admin title.
	 * @param string $unused_title An unused title.
	 *
	 * @return string
	 */
	public function filter_admin_title( $admin_title, $unused_title ) {
		if ( ! empty( $_GET['event_id'] ) ) {
			$event       = get_post( $_GET['event_id'] );
			$admin_title = sprintf( __( '%s - Attendee list', 'event-tickets' ), $event->post_title );
		}

		return $admin_title;
	}

	/**
	 * Renders the Attendees page
	 *
	 * @since 4.6.2
	 */
	public function render() {
		/**
		 * Fires immediately before the content of the attendees screen
		 * is rendered.
		 *
		 * @param Tribe__Tickets__Tickets_Handler $handler The current ticket handler instance.
		 */
		do_action( 'tribe_tickets_attendees_page_inside', $this );

		$post_id = tribe_get_request_var( 'post_id', 0 );
		$post_id = tribe_get_request_var( 'event_id', $post_id );

		$context = $this->get_render_context( (int) $post_id );

		/*
		 * See template filters to update the template or its context data.
		 */
		tribe( 'tickets.admin.views' )->template( 'attendees', $context );
	}

	/**
	 * Generates a list of attendees taking into account the Screen Options.
	 * It's used both for the Email functionality, as well as the CSV export.
	 *
	 * @since 4.6.2
	 * @since 5.13.3 Included param $all to allow for CSV export of all attendees.
	 *
	 * @param int|string $event_id The ID of the event to export the list for or 'all' for all events.
	 *
	 * @return array
	 */
	public function generate_filtered_list( $event_id ) {
		/**
		 * Fire immediately prior to the generation of a filtered (exportable) attendee list.
		 *
		 * @param int|string $event_id The ID of the event to export the list for or 'all' for all events.
		 */
		do_action( 'tribe_events_tickets_generate_filtered_attendees_list', $event_id );

		if ( empty( $this->page_id ) ) {
			$this->page_id = 'tribe_events_page_tickets-attendees';
		}

		// Add in Columns or get_column_headers() returns nothing.
		$filter_name = "manage_{$this->page_id}_columns";
		add_filter( $filter_name, [ $this->attendees_table, 'get_columns' ], 15 );

		$items = 'all' === $event_id ? Tickets::get_attendees_by_args()['attendees'] : Tickets::get_event_attendees( $event_id );

		// Add Handler for Community Tickets to Prevent Notices in Exports.
		if ( ! is_admin() ) {
			$columns = apply_filters( $filter_name, [] );
		} else {
			$columns = array_filter( (array) get_column_headers( get_current_screen() ) );
			$columns = array_map( 'wp_strip_all_tags', $columns );
		}

		// We don't want HTML inputs, private data or other columns that are superfluous in a CSV export.
		$hidden = array_merge(
			get_hidden_columns( $this->page_id ),
			[
				'cb',
				'meta_details',
				'primary_info',
				'provider',
				'purchaser',
				'status',
				'attendee_actions',
				'attendee_event',
			]
		);

		$hidden         = array_flip( $hidden );
		$export_columns = array_diff_key( $columns, $hidden );

		// Add additional expected columns.
		$export_columns['order_id']        = esc_html_x(
			'Order ID',
			'attendee export',
			'event-tickets'
		);
		$export_columns['order_status']    = esc_html_x(
			'Order Status',
			'attendee export',
			'event-tickets'
		);
		$export_columns['attendee_id']     = esc_html(
			sprintf(
			/* Translators: %s: The type of ID. */
				_x(
					'%s ID',
					'attendee export',
					'event-tickets'
				),
				tribe_get_ticket_label_singular( 'attendee_export_ticket_id' )
			)
		);
		$export_columns['holder_name']     = esc_html_x(
			'Ticket Holder Name',
			'attendee export',
			'event-tickets'
		);
		$export_columns['holder_email']    = esc_html_x(
			'Ticket Holder Email Address',
			'attendee export',
			'event-tickets'
		);
		$export_columns['purchaser_name']  = esc_html_x(
			'Purchaser Name',
			'attendee export',
			'event-tickets'
		);
		$export_columns['purchaser_email'] = esc_html_x(
			'Purchaser Email Address',
			'attendee export',
			'event-tickets'
		);

		/**
		 * Used to modify what columns should be shown on the CSV export.
		 * The column name should be the Array Index, and the Header should be the array Value.
		 *
		 * @since 5.9.3
		 *
		 * @param array $export_columns Columns, associative array
		 * @param array $items          Items to be exported
		 * @param int   $event_id       Event ID
		 */
		$export_columns = apply_filters( 'tribe_events_tickets_attendees_csv_export_columns', $export_columns, $items, $event_id );

		// Add the export column headers as the first row.
		$rows = [
			array_values( $export_columns ),
		];

		foreach ( $items as $single_item ) {
			// Fresh row!
			$row = [];

			foreach ( $export_columns as $column_id => $column_name ) {
				// If additional columns have been added to the attendee list table we can obtain the
				// values by calling the table object's column_default() method - any other values
				// should simply be passed back unmodified.
				$row[ $column_id ] = $this->attendees_table->column_default( $single_item, $column_id );

				// In the case of orphaned field data, handle converting array to string.
				if ( is_array( $row[ $column_id ] ) ) {
					$row[ $column_id ] = Arr::get( $row[ $column_id ], 'value', '' );
				}

				// Special handling for the check_in column.
				if ( ! empty( $single_item[ $column_id ] ) && 'check_in' === $column_id && 1 == $single_item[ $column_id ] ) {
					$row[ $column_id ] = esc_html__( 'Yes', 'event-tickets' );
				}

				// Special handling for new human readable id.
				if ( 'attendee_id' === $column_id ) {
					if ( isset( $single_item[ $column_id ] ) ) {
						$ticket_unique_id  = get_post_meta( $single_item[ $column_id ], '_unique_id', true );
						$ticket_unique_id  = $ticket_unique_id === '' ? $single_item[ $column_id ] : $ticket_unique_id;
						$row[ $column_id ] = esc_html( $ticket_unique_id );
					}
				}

				// Handle custom columns that might have names containing HTML tags.
				$row[ $column_id ] = wp_strip_all_tags( $row[ $column_id ] );
				// Decode HTML Entities.
				$row[ $column_id ] = html_entity_decode( $row[ $column_id ], ENT_QUOTES | ENT_XML1, 'UTF-8' );
				// Remove line breaks (e.g. from multi-line text field) for valid CSV format. Double quotes necessary here.
				$row[ $column_id ] = str_replace( [ "\r", "\n" ], ' ', $row[ $column_id ] );
			}

			$rows[] = array_values( $row );
		}

		return array_filter( $rows );
	}

	/**
	 * Sanitize rows for CSV usage.
	 *
	 * @since 4.10.7.2
	 *
	 * @param array $rows Rows to be sanitized.
	 *
	 * @return array Sanitized rows.
	 */
	public function sanitize_csv_rows( array $rows ) {
		foreach ( $rows as &$row ) {
			$row = array_map( [ $this, 'sanitize_csv_value' ], $row );
		}

		return $rows;
	}

	/**
	 * Sanitize a value for CSV usage.
	 *
	 * @since 4.10.7.2
	 *
	 * @param mixed $value Value to be sanitized.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize_csv_value( $value ) {
		if (
			0 === tribe_strpos( $value, '=' )
			|| 0 === tribe_strpos( $value, '+' )
			|| 0 === tribe_strpos( $value, '-' )
			|| 0 === tribe_strpos( $value, '@' )
		) {
			// Prefix the value with a single quote to prevent formula from being processed.
			$value = '\'' . $value;
		}

		return $value;
	}

	/**
	 * Checks if the user requested a CSV export from the attendees list.
	 * If so, generates the download and finishes the execution.
	 *
	 * @since 4.6.2
	 * @since 5.13.3 Included param $all to allow for CSV export of all attendees.
	 *
	 * @param bool $all Whether to generate a CSV for attendees of all events or just the current one.
	 *
	 * @return void
	 */
	public function maybe_generate_csv( $all = false ) {
		if (
				! isset( $_GET['attendees_csv_nonce'] )
				|| ! wp_verify_nonce( sanitize_key( $_GET['attendees_csv_nonce'] ), 'attendees_csv_nonce' )
				|| empty( $_GET['attendees_csv'] )
				|| ! $this->user_can_export_attendees_csv() ) {
			return;
		}

		if ( ! $all ) {
			$event_id = isset( $_GET['event_id'] ) ? absint( sanitize_key( $_GET['event_id'] ) ) : 0;
			$event_id = Event::filter_event_id( $event_id, 'attendee-csv-report' );
			$event    = get_post( $event_id );

			if ( is_null( $event ) ) {
				return;
			}

			$items = $this->generate_filtered_list( $event_id );
		} else {
			$event_id = 'all';
			$items    = $this->generate_filtered_list( 'all' );
		}

		// Sanitize items for CSV usage.
		$items = $this->sanitize_csv_rows( $items );

		/**
		 * Allow for filtering and modifying the list of attendees that will be exported via CSV for a given event.
		 *
		 * @param array $items    The array of attendees that will be exported in this CSV file.
		 * @param int|string $event_id The ID of the event these attendees are associated with or 'all' for all events.
		 */
		$items = apply_filters( 'tribe_events_tickets_attendees_csv_items', $items, $event_id );

		if ( empty( $items ) ) {
			return;
		}

		$charset = get_option( 'blog_charset' );

		if ( ! $all ) {
			$filename = sanitize_file_name( $event->post_title . '-' . _x( 'attendees', 'CSV export file name', 'event-tickets' ) );
		} else {
			$filename = sanitize_file_name( _x( 'All event attendees', 'CSV export file name', 'event-tickets' ) );
		}

		// Output headers so that the file is downloaded rather than displayed.
		header( "Content-Type: text/csv; charset={$charset}" );
		header( "Content-Disposition: attachment; filename={$filename}.csv" );

		// Create the file pointer connected to the output stream.
		$output = fopen( 'php://output', 'w' );

		/**
		 * Allow filtering the field delimiter used in the CSV export file.
		 *
		 * @since 5.1.3
		 *
		 * @param string $delimiter The field delimiter used in the CSV export file.
		 */
		$delimiter = apply_filters( 'tribe_tickets_attendees_csv_export_delimiter', ',' );

		// Output the lines into the file.
		foreach ( $items as $item ) {
			fputcsv( $output, $item, $delimiter ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv
		}

		fclose( $output );
		exit;
	}

	/**
	 * Determines if the current user is allowed to export Attendees list as a CSV.
	 *
	 * @since 5.14.0
	 *
	 * @return boolean
	 */
	public function user_can_export_attendees_csv() {
		// Applies to Super Admins, Admins and Editors by default.
		$can_export = current_user_can( 'publish_pages' );

		/**
		 * Filter if the current user can export the Attendees list as a CSV.
		 *
		 * This allows developers to customize the function to grant permission
		 * to additional roles or specific users.
		 *
		 * @param bool $can_export Whether the user can export CSV or not.
		 * @param WP_User $user The current user object.
		 */
		return apply_filters( 'tec_tickets_attendees_user_can_export_csv', $can_export );
	}

	/**
	 * Checks if the current user has the capability to perform a certain action on a given event and it's attendees list.
	 *
	 * @since 5.8.2
	 *
	 * @param ?int|?string $event_id The event ID.
	 * @param ?string      $nonce    The nonce.
	 * @param ?string      $type     The type of recipient.
	 *                               Accepts 'user' or 'email'.
	 * @param ?string|?int $send_to  The recipient's ID or email.
	 *                               If $type is 'user', this should be the user ID.
	 *
	 * @return true|WP_Error
	 */
	public function has_attendees_list_access( $event_id = null, ?string $nonce = null, ?string $type = 'user', $send_to = null ) {
		$error = new WP_Error();

		if ( ! $event_id ) {
			$error->add( 'no-event-id', esc_html__( 'Invalid Event ID', 'event-tickets' ), [ 'type' => 'general' ] );

			return $error;
		}

		$cap = 'edit_posts';

		if ( ! current_user_can( 'edit_posts' ) ) {
			$event = get_post( $event_id );

			if ( $event instanceof WP_Post && get_current_user_id() === (int) $event->post_author ) {
				$cap = 'read';
			}
		}

		if (
			empty( $nonce )
			|| ! wp_verify_nonce( $nonce, 'email-attendees-list' )
			|| ! $this->user_can( $cap, $event_id )
		) {
			$error->add( 'nonce-fail', esc_html__( 'Cheatin Huh?', 'event-tickets' ), [ 'type' => 'general' ] );

			return $error;
		}

		if ( empty( $send_to ) ) {
			$error->add( 'empty-fields', esc_html__( 'Empty user and/or email', 'event-tickets' ), [ 'type' => 'general' ] );

			return $error;
		}

		if ( 'email' === $type && ! is_email( $send_to ) ) {
			$error->add( 'invalid-email', esc_html__( 'Invalid Email', 'event-tickets' ), [ 'type' => $type ] );

			return $error;
		}

		if ( 'user' === $type && ! is_numeric( $send_to ) ) {
			$error->add( 'invalid-user', esc_html__( 'Invalid User ID', 'event-tickets' ), [ 'type' => $type ] );

			return $error;
		}

		return true;
	}

	/**
	 * Handles the "send to email" action for the attendees list.
	 *
	 * @since 4.6.2
	 * @since 5.8.2 Included params $event_id, $type, $send_to and $error to allow for testing.
	 *
	 * @param ?int|?string $event_id   The event ID.
	 * @param ?string      $type       The type of recipient.
	 *                                 Accepts 'user' or 'email'.
	 * @param ?string|?int $send_to    The recipient's ID or email.
	 *                                 If $type is 'user', this should be the user ID.
	 * @param ?WP_Error   $error       The error object.
	 *                                 If null, a new WP_Error object will be created.
	 *
	 * @return string|WP_Error
	 */
	public function send_mail_list( $event_id = null, ?string $type = 'user', $send_to = null, $error = null ) {

		// Check user access.
		$nonce         = tribe_get_request_var( '_wpnonce' );
		$access_status = $this->has_attendees_list_access(
			$event_id,
			$nonce,
			$type,
			$send_to
		);
		if ( is_wp_error( $access_status ) ) {
			return $access_status;
		}

		if ( null === $error ) {
			$error = new WP_Error();
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		// Send to could be an email or a user ID.
		$email = sanitize_email( $send_to );

		if ( 'user' === $type ) {
			$user = get_user_by( 'id', (int) $send_to );

			if ( ! is_object( $user ) ) {
				$error->add( 'invalid-user', esc_html__( 'Invalid User ID', 'event-tickets' ), [ 'type' => $type, 'user' => $send_to ] );

				return $error;
			}

			$email = $user->data->user_email;
		}

		$this->attendees_table = new Tribe__Tickets__Attendees_Table();
		$items                 = $this->generate_filtered_list( $event_id );

		$event = get_post( $event_id );

		ob_start();
		$attendee_tpl = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/attendees-email.php', [ 'disable_view_check' => true ] );
		include $attendee_tpl;
		$content = ob_get_clean();

		add_filter( 'wp_mail_content_type', [ $this, 'set_contenttype' ] );

		if ( ! wp_mail( $email, sprintf( esc_html__( 'Attendee List for: %s', 'event-tickets' ), $event->post_title ), $content ) ) {
			$error->add( 'email-error', esc_html__( 'Error when sending the email', 'event-tickets' ), [ 'type' => 'general' ] );

			return $error;
		}

		remove_filter( 'wp_mail_content_type', [ $this, 'set_contenttype' ] );

		return esc_html__( 'Email sent successfully!', 'event-tickets' );
	}

	/**
	 * Sets the content type for the attendees to email functionality.
	 * Allows for sending an HTML email.
	 *
	 * @since 4.6.2
	 *
	 * @param string $unused_content_type The content type.
	 *
	 * @return string
	 */
	public function set_contenttype( $unused_content_type ) {
		return 'text/html';
	}

	/**
	 * Tests if the user has the specified capability in relation to whatever post type
	 * the ticket relates to.
	 *
	 * For example, if tickets are created for the banana post type, the generic capability
	 * "edit_posts" will be mapped to "edit_bananas" or whatever is appropriate.
	 *
	 * @since 4.6.2
	 *
	 * @param string $generic_cap
	 * @param int    $event_id
	 *
	 * @return boolean
	 * @internal for internal plugin use only (in spite of having public visibility)
	 *
	 */
	public function user_can( $generic_cap, $event_id ) {
		$type = get_post_type( $event_id );

		// It's possible we'll get null back.
		if ( null === $type ) {
			return false;
		}

		$type_config = get_post_type_object( $type );

		if ( ! empty( $type_config->cap->{$generic_cap} ) ) {
			return current_user_can( $type_config->cap->{$generic_cap} );
		}

		return false;
	}

	/**
	 * Determines if the current user (or an ID-specified one) is allowed to delete, check-in, and
	 * undo check-in attendees.
	 *
	 * @since 4.6.3
	 *
	 * @param int $user_id Optional. The ID of the user whose access we're checking.
	 *
	 * @return boolean
	 */
	public function user_can_manage_attendees( $user_id = 0, $event_id = '' ) {
		$user_id  = 0 === $user_id ? get_current_user_id() : $user_id;
		$user_can = true;

		// Bail quickly here as we don't have a user to check.
		if ( empty( $user_id ) ) {
			return false;
		}

		/**
		 * Allows customizing the caps a user must have to be allowed to manage attendees.
		 *
		 * @since 4.6.3
		 *
		 * @param array $default_caps The caps a user must have to be allowed to manage attendees.
		 * @param int   $user_id      The ID of the user whose capabilities are being checked.
		 */
		$required_caps = apply_filters(
			'tribe_tickets_caps_can_manage_attendees',
			[ 'edit_others_posts' ],
			$user_id
		);

		// Next make sure the user has proper caps in their role.
		foreach ( $required_caps as $cap ) {
			if ( ! user_can( $user_id, $cap ) ) {
				$user_can = false;
				// Break on first fail.
				break;
			}
		}

		/**
		 * Filter our return value to let other plugins hook in and alter things
		 *
		 * @since 4.10.1
		 *
		 * @param bool $user_can return value, user can or can't
		 * @param int  $user_id  id of the user we're checking
		 * @param int  $event_id id of the event we're checking (matter for checks on event authorship)
		 */
		$user_can = apply_filters( 'tribe_tickets_user_can_manage_attendees', $user_can, $user_id, $event_id );

		return $user_can;
	}

	/**
	 * Create an attendee for any Commerce provider from a ticket.
	 *
	 * @since 5.1.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|int $ticket        Ticket object or ID to create the attendee for.
	 * @param array                             $attendee_data Attendee data to create from.
	 *
	 * @return WP_Post|false The new post object or false if unsuccessful.
	 */
	public function create_attendee( $ticket, $attendee_data ) {
		if ( is_numeric( $ticket ) ) {
			// Try to get provider from the ticket ID.
			$provider = tribe_tickets_get_ticket_provider( (int) $ticket );
		} else {
			// Get provider from ticket object.
			$provider = $ticket->get_provider();
		}

		if ( ! $provider ) {
			return false;
		}

		return $provider->create_attendee( $ticket, $attendee_data );
	}

	/**
	 * Update an attendee for any Commerce provider.
	 *
	 * @since 5.1.0
	 *
	 * @param array|int $attendee      The attendee data or ID for the attendee to update.
	 * @param array     $attendee_data The attendee data to update to.
	 *
	 * @return WP_Post|false The updated post object or false if unsuccessful.
	 */
	public function update_attendee( $attendee, $attendee_data ) {
		$provider = false;

		if ( is_numeric( $attendee ) ) {
			// Try to get provider from the attendee ID.
			$provider = tribe_tickets_get_ticket_provider( (int) $attendee );
		} elseif ( is_array( $attendee ) && isset( $attendee['provider'] ) ) {
			// Try to get provider from the attendee data.
			$provider = Tickets::get_ticket_provider_instance( $attendee['provider'] );
		}

		if ( ! $provider ) {
			return false;
		}

		return $provider->update_attendee( $attendee, $attendee_data );
	}

	/**
	 * Generate the export URL for exporting attendees.
	 *
	 * @since 5.1.7
	 * @since 5.13.3 Included param $all to allow for CSV export of all attendees.
	 *
	 * @return string Relative URL for the export.
	 */
	public function get_export_url() {
		return add_query_arg(
			[
				'attendees_csv'       => true,
				'attendees_csv_nonce' => wp_create_nonce( 'attendees_csv_nonce' ),
				'event_id'            => tribe_get_request_var( 'event_id' ) ?? 'all',
			]
		);
	}

	/**
	 * Echo the button for the export that appears next to the attendees page title.
	 *
	 * @since 5.1.7
	 *
	 * @param int                       $event_id  The Post ID of the event.
	 * @param Tribe__Tickets__Attendees $attendees The attendees object.
	 *
	 * @return string Relative URL for the export.
	 */
	public function include_export_button_title( $event_id, Tribe__Tickets__Attendees $attendees = null ) {
		// Bail if not on the Attendees page.
		if ( 'tickets-attendees' !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		// If this function is called from the tabbed-view.php file it does not send over $event_id or $attendees.
		// If the $event_id is not an integer we can get the information from the get scope and find the data.
		if (
			! is_int( $event_id )
			&& ! empty( tribe_get_request_var( 'event_id' ) )
		) {
			$event_id  = tribe_get_request_var( 'event_id' );
			$attendees = tribe( 'tickets.attendees' );
			$attendees->attendees_table->prepare_items();
		}

		// Bail early if there are no attendees.
		if (
			empty( $attendees )
			|| ! $attendees->attendees_table->has_items()
		) {
			return;
		}

		// Bail early if user is not owner/have permissions.
		if ( ! $this->user_can_manage_attendees( 0, $event_id ) ) {
			return;
		}

		echo sprintf(
			'<a target="_blank" href="%s" class="export action page-title-action" rel="noopener noreferrer">%s</a>',
			esc_url( $export_url = $this->get_export_url() ),
			esc_html__( 'Export', 'event-tickets' )
		);
	}

	/**
	 * Returns the context used to render the Attendees page.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The ID of the post to render the Attendees page for.
	 *
	 * @return array<string,mixed> The context used to render the Attendees page.
	 */
	public function get_render_context( int $post_id ): array {
		$tickets         = Tickets::get_event_tickets( $post_id );
		$tickets_by_type = [ 'rsvp' => [], 'default' => [] ];
		$ticket_totals   = [
			'sold'      => 0,
			'available' => 0,
		];

		$available_contributors = [];

		// Split Tickets by their type.
		/** @var Ticket[] $tickets */
		foreach ( $tickets as $ticket ) {
			$ticket_type = $ticket->type();
			if ( ! isset( $tickets_by_type[ $ticket_type ] ) ) {
				$tickets_by_type[ $ticket_type ] = [];
			}
			$tickets_by_type[ $ticket_type ][] = $ticket;
			$ticket_totals['sold']             += $ticket->qty_sold();

			if ( $ticket_totals['available'] === - 1 ) {
				// Unlimited capacity trumps any other capacity; if already unlimited, it will stay unlimited.
				continue;
			}

			if ( - 1 === $ticket->available() ) {
				// Unlimited capacity trumps any other capacity: set to unlimited.
				$ticket_totals['available'] = - 1;
				continue;
			}

			if ( $ticket->global_stock_mode() === Global_Stock::OWN_STOCK_MODE ) {
				// Own stock: add to the available count.
				$ticket_totals['available'] += $ticket->available();
				continue;
			}

			if ( ! isset( $available_contributors[ (int) $ticket->get_event_id() ] ) || $ticket->available() > $available_contributors[ (int) $ticket->get_event_id() ] ) {
				// Shared or capped capacity: add to the available contributors only if we haven't already counted it or if it's higher than the previous count.
				$available_contributors[ (int) $ticket->get_event_id() ] = $ticket->available();
			}
		}

		if ( $ticket_totals['available'] !== - 1 && count( $available_contributors ) ) {
			$ticket_totals['available'] += array_sum( $available_contributors );
		}

		$render_context = [
			'attendees'         => $this,
			'event_id'          => $post_id,
			'tickets_by_type'   => $tickets_by_type,
			'ticket_totals'     => $ticket_totals,
			'type_icon_classes' => [
				'default' => 'tec-tickets__admin-attendees-overview-ticket-type-icon--ticket',
				'rsvp'    => 'tec-tickets__admin-attendees-overview-ticket-type-icon--rsvp',
			],
			'type_labels'       => [
				'default' => tec_tickets_get_default_ticket_type_label_plural( 'attendee overview' ),
				'rsvp'    => tribe_get_rsvp_label_plural( 'attendee overview' ),
			],
		];

		/**
		 * Filters the context used to render the Attendee Report page.
		 *
		 * @since 5.16.0
		 *
		 * @param array $render_context The context used to render the Attendee Report page.
		 * @param int $post_id The post ID of the post to retrieve tickets for.
		 * @param array $tickets The tickets to display on the page.
		 */
		return apply_filters( 'tec_tickets_attendees_page_render_context', $render_context, $post_id, $tickets );
	}

	/**
	 * Checks if the current user can access a page based on post ownership and capabilities.
	 *
	 * This method determines access by checking if the current user is the author of the post
	 * or if they have the capability to edit others' posts (edit_others_posts) within the same post type.
	 * If neither condition is met, access is denied.
	 *
	 * @since 5.8.4
	 *
	 * @param int $post_id The ID of the post to check access against.
	 *
	 * @return bool True if the user can access the page, false otherwise.
	 */
	public function can_access_page( int $post_id ): bool {
		$is_on_general_page = tribe( Attendees_Page::class )->is_on_page();

		if ( $is_on_general_page ) {
			return tribe( Attendees_Page::class )->can_access_page();
		}

		$post = get_post( $post_id );
		// Ensure $post is valid to prevent errors in cases where $post_id might be invalid.
		if ( ! $post ) {
			return false;
		}

		$post_type_object      = get_post_type_object( $post->post_type );
		$can_edit_others_posts = current_user_can( $post_type_object->cap->edit_others_posts );

		// Return true if the user can edit others' posts of this type or if they're the author, false otherwise.
		$has_access = $can_edit_others_posts || get_current_user_id() == $post->post_author;

		/**
		 * Filters whether a user can access the attendees page for a given post.
		 *
		 * @since 5.8.4
		 *
		 * @param bool $has_access True if the user has access, false otherwise.
		 * @param int $post_id The ID of the post being checked.
		 * @param WP_Post $post The post object.
		 */
		return apply_filters( 'tec_tickets_attendees_page_role_access', $has_access, $post_id, $post );
	}
}
