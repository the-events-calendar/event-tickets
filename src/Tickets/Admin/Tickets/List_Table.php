<?php
/**
 * The list table for the Admin Tickets screen.
 *
 * @since  5.14.0
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Tickets;

use Tribe__Tickets__Commerce__Currency;
use Tribe__Tickets__Ticket_Object;
use WP_List_Table;
use DateTime;
use TEC\Tickets\Commerce as TicketsCommerce;
use Tribe\Tickets\Admin\Settings;
use Tribe__Template;
use Tribe__Tickets__Main;
use WP_Post;
use WP_Query;
use TEC\Events_Pro\Custom_Tables\V1\Links\Provider as Custom_Tables_Links_Provider;

/**
 * Class List_Table.
 *
 * @since  5.14.0
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
	 * The template object.
	 *
	 * @var Tribe__Template
	 */
	protected $template;

	/**
	 * Default status filter.
	 *
	 * @var string
	 */
	public static $default_status = 'all';

	/**
	 * Default Sort By.
	 *
	 * @var string
	 */
	public static $default_sort_by = 'end';

	/**
	 * Default Sort Order.
	 *
	 * @var string
	 */
	public static $default_sort_order = 'desc';

	/**
	 * The query for the tickets.
	 *
	 * @var WP_Query $query
	 */
	protected $query;

	/**
	 * List of events by ID.
	 *
	 * @var array
	 */
	protected $events_by_id = [];

	/**
	 * List of event edit URLs.
	 *
	 * @var array
	 */
	protected $event_edit_urls = [];

	/**
	 * Get the template object.
	 *
	 * @since 5.14.0
	 *
	 * @return Tribe__Template
	 */
	protected function get_template() {
		if ( ! empty( $this->template ) ) {
			return $this->template;
		}

		$this->template = tribe( 'tickets.admin.views' );

		return $this->template;
	}

	/**
	 * Get the default status for the Admin Tickets Table.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	public static function get_default_status() {
		/**
		 * Filters the default status for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string $default_status The default status for the Admin Tickets Table.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_default_status', self::$default_status );
	}

	/**
	 * Get the default sort by for the Admin Tickets Table.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	public static function get_default_sort_by() {
		/**
		 * Filters the default sort by for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string $default_sort_by The default sort by for the Admin Tickets Table.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_default_sort_by', self::$default_sort_by );
	}

	/**
	 * Get the default sort order for the Admin Tickets Table.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	public static function get_default_sort_order() {
		/**
		 * Filters the default sort order for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string $default_sort_order The default sort order for the Admin Tickets Table.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_default_sort_order', self::$default_sort_order );
	}

	/**
	 * The constructor.
	 *
	 * @since  5.14.0
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
	 * @since  5.14.0
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
			'days_left' => esc_html__( 'Days Left', 'event-tickets' ),
			'price'     => esc_html__( 'Price', 'event-tickets' ),
			'sold'      => esc_html__( 'Sold', 'event-tickets' ),
			'remaining' => esc_html__( 'Remaining', 'event-tickets' ),
			'sales'     => esc_html__( 'Sales', 'event-tickets' ),
		];

		/**
		 * Filters the columns for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param array $table_columns The columns for the Admin Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_columns', $table_columns );
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since  5.14.0
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return $this->get_table_columns();
	}

	/**
	 * Get primary column for the list table.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	protected function get_primary_column_name(): string {
		return 'name';
	}

	/**
	 * Get hidden columns for the list table.
	 *
	 * @since 5.14.0
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
	 * @since  5.14.0
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
		 * Filter the default hidden columns for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param array $default_hidden_columns The default hidden columns for the Admin Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_default_hidden_columns', $default_hidden_columns );
	}

	/**
	 * Returns the sortable columns for the list table.
	 *
	 * @since  5.14.0
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
		 * Filters the sortable columns for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param array $sortable_columns The sortable columns for the Admin Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_sortable_columns', $sortable_columns );
	}

	/**
	 * Get the ticket type icon.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string HTML for the ticket type icon.
	 */
	protected function get_ticket_type_icon( $item ) {
		ob_start();
		do_action( 'tec_tickets_editor_list_table_title_icon_' . $item->type() );
		return ob_get_clean();
	}

	/**
	 * Get the default column value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object $item        The current item.
	 * @param string                        $column_name The column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		// If the column name is empty or the item does not have the column name, return an empty string.
		if ( empty( $column_name ) || ! isset( $item->$column_name ) ) {
			return '';
		}

		// If value is not a string or a number, return an empty string.
		if ( ! is_string( $item->$column_name ) && ! is_numeric( $item->$column_name ) ) {
			return '';
		}

		$default = esc_html( $item->$column_name );

		/**
		 * Filters the default column value for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $default     The default column value for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item        The current item.
		 * @param string                        $column_name The column name.
		 *
		 * @return string
		 */
		$default = apply_filters( 'tec_tickets_admin_tickets_table_column_default', $default, $item, $column_name );

		/**
		 * Filters the default column value for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $default The default column value for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item    The current item.
		 *
		 * @return string
		 */
		return apply_filters( "tec_tickets_admin_tickets_table_column_default_{$column_name}", $default, $item );
	}

	/**
	 * Get the event edit URL.
	 *
	 * @since 5.14.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return string
	 */
	protected function get_event_edit_url( $event_id ) {
		if ( isset( $this->event_edit_urls[ $event_id ] ) ) {
			return $this->event_edit_urls[ $event_id ];
		}

		if ( isset( $this->events_by_id[ $event_id ] ) ) {
			$event = $this->events_by_id[ $event_id ];
		} else {
			$event                           = get_post( $event_id );
			$this->events_by_id[ $event_id ] = $event;
		}

		$ecp_installed = has_action( 'tribe_common_loaded', 'tribe_register_pro' );
		if ( $ecp_installed ) {
			remove_filter( 'get_edit_post_link', [ tribe( Custom_Tables_Links_Provider::class ), 'update_event_edit_link' ], 10 );
		}

		$edit_post_url = get_edit_post_link( $event );

		if ( $ecp_installed ) {
			add_filter( 'get_edit_post_link', [ tribe( Custom_Tables_Links_Provider::class ), 'update_event_edit_link' ], 10, 2 );
		}
		$this->event_edit_urls[ $event_id ] = $edit_post_url;

		return $edit_post_url;
	}

	/**
	 * Get the column name value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_name( $item ): string {
		if ( $item instanceof WP_Post ) {
			return get_the_title( $item );
		}

		$event = $item->get_event();
		if ( ! $event ) {
			return esc_html( $item->name );
		}

		$edit_post_link = sprintf(
			'<a href="%s" class="tec-tickets-admin-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $this->get_event_edit_url( $event->ID ) ),
			esc_html( $item->name )
		);

		$template = $this->get_template();
		$context  = [
			'icon_html'   => $this->get_ticket_type_icon( $item ),
			'ticket_link' => $edit_post_link,
		];

		$name = $template->template( 'admin-tickets/column/name', $context, false );

		/**
		 * Filters the name for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $name The name for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_name', $name, $item );
	}

	/**
	 * Get the column ID value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object $item The current item.
	 *
	 * @return string
	 */
	public function column_id( $item ): string {
		$id = (string) $item->ID;

		/**
		 * Filters the ID for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $id   The ID for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_id', $id, $item );
	}

	/**
	 * Get the column event value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_event( $item ): string {
		// If the item is a post, it means the post type is disabled in the ticket settings.
		if ( $item instanceof WP_Post ) {
			$msg_line_1 = esc_html__( 'This ticket is connected to a disabled post type.', 'event-tickets' );

			$ticket_settings_url  = add_query_arg( [ 'page' => Settings::$settings_page_id ], admin_url( 'admin.php' ) );
			$ticket_settings_link = sprintf(
				'<a href="%s" class="tec-tickets-admin-tickets-table-event-link" rel="nofollow noopener">%s</a>',
				esc_url( $ticket_settings_url ),
				esc_html__( 'Ticket Settings Page', 'event-tickets' )
			);
			$msg_line_2           = sprintf(
				// Translators: %s: Ticket Settings Page link.
				esc_html__( 'You can enable this post type on the %s.', 'event-tickets' ),
				$ticket_settings_link
			);

			return wp_kses_post( sprintf( '<i>%s<br>%s</i>', $msg_line_1, $msg_line_2 ) );
		}

		// If the item is a ticket object, get the event.
		$event = $item->get_event();
		if ( ! $event ) {
			return esc_html__( 'No associated event', 'event-tickets' );
		}

		$edit_post_link = sprintf(
			'<a href="%s" class="tec-tickets-admin-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $this->get_event_edit_url( $event->ID ) ),
			get_the_title( $event )
		);

		$orders_report_url  = add_query_arg(
			[
				'post_type' => $event->post_type,
				'page'      => $this->get_order_page_slug(),
				'event_id'  => $event->ID,
			],
			admin_url( 'edit.php' )
		);
		$orders_report_link = sprintf(
			'<a href="%s" class="tec-tickets-admin-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $orders_report_url ),
			esc_html__( 'Orders', 'event-tickets' )
		);

		$attendees_report_url  = add_query_arg(
			[
				'post_type' => $event->post_type,
				'page'      => 'tickets-attendees',
				'event_id'  => $event->ID,
			],
			admin_url( 'edit.php' )
		);
		$attendees_report_link = sprintf(
			'<a href="%s" class="tec-tickets-admin-tickets-table-event-link" target="_blank" rel="nofollow noopener">%s</a>',
			esc_url( $attendees_report_url ),
			esc_html__( 'Attendees', 'event-tickets' )
		);

		$actions = [
			'orders'    => $orders_report_link,
			'attendees' => $attendees_report_link,
		];

		/**
		 * Filters the actions for the event in the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param array $actions The actions for the event in the Admin Tickets Table.
		 */
		$actions = apply_filters( 'tec_tickets_admin_tickets_table_event_actions', $actions, $event, $item );

		$event = sprintf( '%1$s %2$s', $edit_post_link, $this->row_actions( $actions ) );

		/**
		 * Filters the event for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $event The event for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item  The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_event', $event, $item );
	}

	/**
	 * Get the column start date value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_start( $item ): string {
		if ( $item instanceof WP_Post ) {
			return '-';
		}

		$date_format = get_option( 'date_format' );
		$ts          = $item->start_date();

		$start = sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( \Tribe__Date_Utils::reformat( $ts, 'c' ) ),
			esc_html( \Tribe__Date_Utils::reformat( $ts, $date_format . ' ' . get_option( 'time_format' ) ) ),
			esc_html( \Tribe__Date_Utils::reformat( $ts, $date_format ) )
		);

		/**
		 * Filters the start date for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $start The start date for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item  The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_start_date', $start, $item );
	}

	/**
	 * Get the column end date value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_end( $item ): string {
		if ( $item instanceof WP_Post ) {
			return '-';
		}

		$date_format = get_option( 'date_format' );
		$ts          = $item->end_date();

		$end = sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( \Tribe__Date_Utils::reformat( $ts, 'c' ) ),
			esc_html( \Tribe__Date_Utils::reformat( $ts, $date_format . ' ' . get_option( 'time_format' ) ) ),
			esc_html( \Tribe__Date_Utils::reformat( $ts, $date_format ) )
		);

		/**
		 * Filters the end date for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $end  The end date for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_end_date', $end, $item );
	}

	/**
	 * Get the column days_left value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_days_left( $item ): string {
		if ( $item instanceof WP_Post ) {
			return '-';
		}

		$datetime = $item->end_date( false );
		$now      = new DateTime();
		$interval = $now->diff( $datetime );

		if ( $interval->invert ) {
			return '-';
		}

		$days_left = (string) $interval->days;

		/**
		 * Filters the number of days left for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $days_left The number of days left for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item      The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_days_left', $days_left, $item );
	}

	/**
	 * Format currency.
	 *
	 * @since 5.14.0
	 *
	 * @param float $price    The price to format.
	 * @param int   $event_id The event/post ID.
	 *
	 * @return string
	 */
	protected function format_currency( $price, $event_id ) {
		/** @var Tribe__Tickets__Commerce__Currency $currency */
		$currency        = tribe( 'tickets.commerce.currency' );
		$currency_symbol = $currency->get_provider_symbol( Page::get_current_provider(), $event_id );
		$symbol_position = $currency->get_provider_symbol_position( Page::get_current_provider(), $event_id );
		$formatted_price = $currency->get_formatted_currency( number_format( (float) $price, 2 ), $event_id, Page::get_current_provider() );

		return 'prefix' === $symbol_position ? $currency_symbol . $formatted_price : $formatted_price . $currency_symbol;
	}

	/**
	 * Get the column price value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_price( $item ): string {
		if ( $item instanceof WP_Post ) {
			return '-';
		}

		$price = $this->format_currency( $item->price, $item->get_event() );

		/**
		 * Filters the price for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $price The price for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item  The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_price', $price, $item );
	}

	/**
	 * Get the column sold value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_sold( $item ): string {
		if ( $item instanceof WP_Post ) {
			return '-';
		}

		$sold = (string) $item->qty_sold();

		/**
		 * Filters the number of tickets sold for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $sold The number of tickets sold for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_sold', $sold, $item );
	}

	/**
	 * Get the column remaining value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_remaining( $item ): string {
		if ( $item instanceof WP_Post ) {
			return '-';
		}

		// If there is no event, do not attempt to calculate remaining tickets.
		if ( empty( $item->get_event() ) ) {
			return 0;
		}

		$available = $item->available();
		$remaining = $available < 0 ? '-' : (string) $available;

		/**
		 * Filters the number of tickets remaining for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $remaining The number of tickets remaining for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item      The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_remaining', $remaining, $item );
	}

	/**
	 * Get the column sales value.
	 *
	 * @since 5.14.0
	 *
	 * @param Tribe__Tickets__Ticket_Object|WP_Post $item The current item.
	 *
	 * @return string
	 */
	public function column_sales( $item ): string {
		if ( $item instanceof WP_Post ) {
			return '-';
		}

		$sales = $this->format_currency( ( $item->qty_sold() * $item->price ), $item->get_event() );

		/**
		 * Filters the total sales for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param string                        $sales The total sales for the Admin Tickets Table.
		 * @param Tribe__Tickets__Ticket_Object $item  The current item.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_column_sales', $sales, $item );
	}

	/**
	 * Modify the sort arguments.
	 *
	 * @since 5.14.0
	 *
	 * @param array $args The arguments used to query the tickets for the Admin Tickets Table.
	 *
	 * @return array
	 */
	public function modify_sort_args( $args ): array {
		$orderby = tribe_get_request_var( 'orderby', self::get_default_sort_by() );
		switch ( $orderby ) {
			case 'name':
				$args['orderby'] = 'post_title';
				break;
			case 'id':
				$args['orderby'] = 'ID';
				break;
			case 'start':
				$args['orderby']   = 'meta_value';
				$args['meta_key']  = '_ticket_start_date';
				$args['meta_type'] = 'DATETIME';
				break;
			case 'end':
				$args['orderby']   = 'meta_value';
				$args['meta_key']  = '_ticket_end_date';
				$args['meta_type'] = 'DATETIME';
				break;
			case 'days_left':
				$args['orderby']   = 'meta_value';
				$args['meta_key']  = '_ticket_end_date';
				$args['meta_type'] = 'DATETIME';
				break;
			case 'price':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price';
				break;
			case 'sold':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'total_sales';
				break;
		}

		$args['order'] = tribe_get_request_var( 'order', self::get_default_sort_order() );

		return $args;
	}

	/**
	 * Modify the filter arguments.
	 *
	 * @since 5.14.0
	 *
	 * @param array $args The arguments used to query the tickets for the Admin Tickets Table.
	 *
	 * @return array
	 */
	public function modify_filter_args( $args ) {
		$filter = tribe_get_request_var( Page::STATUS_KEY, self::get_default_status() );

		if ( empty( $filter ) || 'all' === $filter ) {
			return $args;
		}

		if ( ! isset( $args['meta_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query'] = [];
		}

		switch ( $filter ) {
			case 'active':
				$args['meta_query'][] = [
					'key'     => '_ticket_start_date',
					'value'   => current_time( 'mysql' ),
					'compare' => '<=',
					'type'    => 'DATETIME',
				];
				$args['meta_query'][] = [
					'key'     => '_ticket_end_date',
					'value'   => current_time( 'mysql' ),
					'compare' => '>',
					'type'    => 'DATETIME',
				];
				break;
			case 'past':
				$args['meta_query'][] = [
					'key'     => '_ticket_end_date',
					'value'   => current_time( 'mysql' ),
					'compare' => '<=',
					'type'    => 'DATETIME',
				];
				break;
			case 'upcoming':
				$args['meta_query'][] = [
					'key'     => '_ticket_start_date',
					'value'   => current_time( 'mysql' ),
					'compare' => '>',
					'type'    => 'DATETIME',
				];
				break;
			case 'discounted':
				$args['meta_query'][] = [
					'key'     => '_sale_price',
					'compare' => 'EXISTS',
				];
				$args['meta_query'][] = [
					'key'     => '_sale_price_start_date',
					'value'   => current_time( 'mysql' ),
					'compare' => '<',
					'type'    => 'DATETIME',
				];
				$args['meta_query'][] = [
					'key'     => '_sale_price_end_date',
					'value'   => current_time( 'mysql' ),
					'compare' => '>',
					'type'    => 'DATETIME',
				];
				break;
		}

		if ( count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		return $args;
	}

	/**
	 * Get the query args for the list table.
	 *
	 * @since 5.14.0
	 *
	 * @return array|bool
	 */
	public function get_query_args() {
		$current_page = $this->get_pagenum();
		$per_page     = $this->get_items_per_page( $this->per_page_option );

		$args = [
			'admin_tickets_list_table' => true,
			'offset'                   => ( $current_page - 1 ) * $per_page,
			'posts_per_page'           => $per_page,
			'return_total_found'       => true,
			'post_type'                => Page::get_current_post_type(),
			'post_status'              => 'any',
		];

		$args = $this->modify_filter_args( $args );
		$args = $this->modify_sort_args( $args );

		/**
		 * Filters the arguments used to query the tickets for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param array $args The arguments used to query the tickets for the Admin Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_query_args', $args );
	}

	/**
	 * Primes the queries for caching purposes.
	 *
	 * @since 5.14.0
	 *
	 * @param array $items The items to prime the queries for.
	 */
	public function prime_queries( $items ) {
		$event_ids   = wp_list_pluck( $items, 'event_id' );
		$event_query = new WP_Query(
			[
				'post__in'               => $event_ids,
				'posts_per_page'         => -1,
				'post_type'              => Tribe__Tickets__Main::instance()->post_types(),
				'post_status'            => 'any',
				'update_post_meta_cache' => true,
			]
		);
		if ( $event_query->found_posts ) {
			foreach ( $event_query->posts as $event ) {
				$this->events_by_id[ $event->ID ] = $event;
			}
		}

		// Only continue if we're looking at Tickets Commerce tickets.
		if ( Page::get_current_provider() !== TicketsCommerce\Module::class ) {
			return;
		}

		// @todo @codingmusician - Add query priming solutions for other providers.
		$ticket_ids             = wp_list_pluck( $items, 'ID' );
		$cache                  = tribe_cache();
		$attendees_by_ticket_id = $cache->get( 'tec_tickets_attendees_by_ticket_id' );
		if ( ! is_array( $attendees_by_ticket_id ) ) {
			$attendees_by_ticket_id = [];
		}

		foreach ( $ticket_ids as $ticket_id ) {
			$cache->set(
				'tec_tickets_quantities_by_status_' . $ticket_id,
				tribe( TicketsCommerce\Ticket::class )->get_status_quantity( $ticket_id )
			);
			$attendees_by_ticket_id[ $ticket_id ] = [];
		}

		$attendee_query = new WP_Query(
			[
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'posts_per_page' => 250,
				'post_type'      => $this->get_attendee_post_type(),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => [
					[
						'key'     => TicketsCommerce\Attendee::$ticket_relation_meta_key,
						'value'   => $ticket_ids,
						'compare' => 'IN',
					],
				],
			]
		);

		if ( 0 === $attendee_query->found_posts ) {
			return;
		}

		foreach ( $attendee_query->posts as $attendee ) {
			$ticket_id = get_post_meta( $attendee->ID, TicketsCommerce\Attendee::$ticket_relation_meta_key, true );
			if ( ! $ticket_id ) {
				continue;
			}

			$attendees_by_ticket_id[ $ticket_id ][] = $attendee;
		}

		$cache->set( 'tec_tickets_attendees_by_ticket_id', $attendees_by_ticket_id );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 5.14.0
	 */
	public function prepare_items() {

		add_filter( 'posts_clauses', [ $this, 'filter_query_clauses' ], 10, 2 );

		$args        = $this->get_query_args();
		$this->query = new WP_Query( $args );
		$total_items = $this->query->found_posts;
		$items       = $this->query->posts;

		remove_filter( 'posts_clauses', [ $this, 'filter_query_clauses' ], 10 );

		$this->prime_queries( $items );

		$provider = Page::get_current_provider_object();

		foreach ( $items as $i => $item ) {
			$ticket_object = $provider->get_ticket( $item->event_id, $item->ID );
			if ( ! empty( $ticket_object ) ) {
				$ticket_object->raw = $item;
			}
			$this->items[] = empty( $ticket_object ) ? $item : $ticket_object;
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

	/**
	 * Filter the query clauses.
	 *
	 * @since 5.14.0
	 *
	 * @param array    $clauses The query clauses.
	 * @param WP_Query $query   The WP_Query object.
	 *
	 * @return array
	 */
	public function filter_query_clauses( $clauses, $query ) {
		// Only modify if not the main query and is the Admin Tickets Table query.
		if ( $query->is_main_query() || empty( $query->query_vars['admin_tickets_list_table'] ) ) {
			return $clauses;
		}

		global $wpdb;

		$event_meta_key = $this->get_event_meta_key();

		// Add join clauses to retrieve the event title.
		$clauses['join'] .= $wpdb->prepare(
			" LEFT JOIN {$wpdb->postmeta} AS ticket_event ON ( {$wpdb->posts}.ID = ticket_event.post_id ) AND ticket_event.meta_key = %s ",
			$event_meta_key
		);
		$clauses['join'] .= " LEFT JOIN {$wpdb->posts} AS event_data ON event_data.ID = ticket_event.meta_value ";

		$clauses['fields'] .= ', event_data.ID AS event_id';

		// If there is no search, return the clauses.
		$search = tribe_get_request_var( 's' );
		if ( empty( $search ) ) {
			return $clauses;
		}

		// Add the event title to the fields.
		$clauses['fields'] .= ', event_data.post_title AS event_title';

		// Add the where clause.
		$clauses['where'] .= $wpdb->prepare(
			" AND ( {$wpdb->posts}.post_title LIKE %s OR event_data.post_title LIKE %s )",
			'%' . $wpdb->esc_like( $search ) . '%',
			'%' . $wpdb->esc_like( $search ) . '%'
		);

		return $clauses;
	}

	/**
	 * Get order page slug.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	protected function get_order_page_slug(): string {
		// Tickets Commerce has its own order page slug. All others are 'tickets-orders'.
		if ( Page::get_current_provider() === TicketsCommerce\Module::class ) {
			return TicketsCommerce\Reports\Orders::$page_slug;
		}

		return 'tickets-orders';
	}

	/**
	 * Get event meta key.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	protected function get_event_meta_key(): string {
		$provider_info = Page::get_provider_info();
		$provider      = Page::get_current_provider();

		if ( ! isset( $provider_info[ $provider ] ) || empty( $provider_info[ $provider ]['event_meta_key'] ) ) {
			return '';
		}

		return $provider_info[ $provider ]['event_meta_key'];
	}

	/**
	 * Get attendee post type based on provider.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	protected function get_attendee_post_type(): string {
		$provider_info = Page::get_provider_info();
		$provider      = Page::get_current_provider();

		if ( ! isset( $provider_info[ $provider ] ) || empty( $provider_info[ $provider ]['attendee_post_type'] ) ) {
			return '';
		}

		return $provider_info[ $provider ]['attendee_post_type'];
	}

	/**
	 * Display the filter and search input.
	 *
	 * @since 5.14.0
	 *
	 * @param string $which The location of the extra table nav.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$current_status = tribe_get_request_var( Page::STATUS_KEY, self::get_default_status() );

		$template = $this->get_template();
		$context  = [
			'list_table'           => $this,
			'status_options'       => $this->get_status_options(),
			'current_status'       => $current_status,
			'search_id'            => 'tec-tickets-admin-tickets-search-input',
			'search_value'         => tribe_get_request_var( 's' ),
			'show_provider_filter' => $this->show_ticket_provider_filter(),
			'provider_options'     => Page::get_provider_options(),
			'current_provider'     => Page::get_current_provider(),
		];

		$template->template( 'admin-tickets/filters', $context );
	}

	/**
	 * Get the default status options.
	 *
	 * @since 5.14.0
	 *
	 * @return array
	 */
	protected function get_status_options(): array {
		$status_options = [
			'active'     => esc_html__( 'Active Tickets', 'event-tickets' ),
			'past'       => esc_html__( 'Past Tickets', 'event-tickets' ),
			'upcoming'   => esc_html__( 'Upcoming Tickets', 'event-tickets' ),
			'discounted' => esc_html__( 'Discounted Tickets', 'event-tickets' ),
			'all'        => esc_html__( 'All Tickets', 'event-tickets' ),
		];

		/**
		 * Filters the status options for the Admin Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param array $status_options The status options for the Admin Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_status_options', $status_options );
	}

	/**
	 * Whether or not to display the ticket provider filter.
	 *
	 * @since 5.14.0
	 *
	 * @return boolean
	 */
	protected function show_ticket_provider_filter(): bool {
		$providers = Page::get_provider_info();

		// Only show if more than one provider.
		return count( $providers ) > 1;
	}

	/**
	 * @inheritDoc
	 */
	public function no_items() {
		esc_html_e( 'No tickets found for the active filter.', 'event-tickets' );
	}
}
