<?php
/**
 * The controller for the Seating Orders.
 *
 * @since 5.16.0
 *
 * @package TEC/Tickets/Seating/Orders
 */

namespace TEC\Tickets\Seating\Orders;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\Asset;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Admin\Attendees\Page as Attendee_Page;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Seating\Admin\Ajax;
use TEC\Tickets\Seating\Ajax_Methods;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe__Tabbed_View as Tabbed_View;
use Tribe__Template as Template;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;
use WP_Query;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Controller
 *
 * @since 5.16.0
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Controller extends Controller_Contract {
	use Ajax_Methods;

	/**
	 * A reference to Attendee data handler
	 *
	 * @since 5.16.0
	 *
	 * @var Attendee
	 */
	private Attendee $attendee;

	/**
	 * A reference to Cart data handler
	 *
	 * @since 5.16.0
	 *
	 * @var Cart
	 */
	private Cart $cart;

	/**
	 * A reference to the seat selection session handler
	 *
	 * @since 5.16.0
	 *
	 * @var Session
	 */
	private Session $session;

	/**
	 * A reference to Sessions Table handler.
	 *
	 * @var Sessions
	 */
	private Sessions $sessions;

	/**
	 * A reference to Seats Report handler.
	 *
	 * @var Seats_Report
	 */
	private Seats_Report $seats_report;

	/**
	 * A reference to the Reservations object.
	 *
	 * @since 5.16.0
	 *
	 * @var Reservations
	 */
	private Reservations $reservations;

	/**
	 * Controller constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param Container    $container    The DI container.
	 * @param Attendee     $attendee     The Attendee data handler.
	 * @param Cart         $cart         The Cart data handler.
	 * @param Reservations $reservations The Reservations object.
	 * @param Session      $session      The seat selection session handler.
	 * @param Sessions     $sessions     A reference to the Sessions table handler.
	 * @param Seats_Report $seats_report The seats report handler.
	 */
	public function __construct(
		Container $container,
		Attendee $attendee,
		Cart $cart,
		Reservations $reservations,
		Session $session,
		Sessions $sessions,
		Seats_Report $seats_report
	) {
		parent::__construct( $container );
		$this->attendee     = $attendee;
		$this->cart         = $cart;
		$this->reservations = $reservations;
		$this->session      = $session;
		$this->sessions     = $sessions;
		$this->seats_report = $seats_report;
	}

	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_commerce_cart_prepare_data', [ $this, 'handle_seat_selection' ] );
		add_action(
			'tec_tickets_commerce_flag_action_generated_attendee',
			[ $this, 'save_seat_data_for_attendee' ],
			10,
			2
		);

		// Add attendee seat data column to the attendee list.
		if ( tribe_get_request_var( 'page' ) === 'tickets-attendees' || tribe( Attendee_Page::class )->is_on_page() ) {
			add_filter( 'tribe_tickets_attendee_table_columns', [ $this, 'add_attendee_seat_column' ], 10, 2 );
			add_filter( 'tribe_events_tickets_attendees_table_column', [ $this, 'render_seat_column' ], 10, 3 );
			add_filter( 'tec_tickets_attendees_table_sortable_columns', [ $this, 'include_seat_column_as_sortable' ] );
			add_filter( 'tribe_repository_attendees_query_args', [ $this, 'handle_sorting_seat_column' ], 10, 3 );
			add_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'remove_move_row_action' ], 10, 2 );
		}

		if ( is_admin() ) {
			add_filter( 'tec_tickets_commerce_reports_tabbed_view_tab_map', [ $this, 'include_seats_tab' ] );
			add_action(
				'tec_tickets_commerce_reports_tabbed_view_after_register_tab',
				[ $this, 'register_seat_tab' ],
				10,
				2
			);
			add_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', [ $this, 'register_seat_tab' ], 10, 2 );
			add_action( 'init', [ $this, 'register_seats_report_tab' ] );
			add_action( 'admin_menu', [ $this, 'register_seats_report_page' ] );
			add_filter( 'tec_tickets_commerce_reports_tabbed_page_title', [ $this, 'filter_seat_tab_title' ], 10, 3 );
			add_filter( 'post_row_actions', [ $this, 'add_seats_row_action' ], 10, 2 );
		}
		// Attendee delete handler.
		add_filter( 'tec_tickets_commerce_attendee_to_delete', [ $this, 'handle_attendee_delete' ] );

		add_action( 'tec_tickets_commerce_flag_action_generated_attendees', [ $this, 'confirm_all_reservations' ], 10, 4 );
		add_action(
			'tec_tickets_commerce_order_status_flag_complete',
			[ $this, 'confirm_all_reservations_on_completion' ]
		);
		add_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES, [ $this, 'fetch_attendees_by_post' ] );
		add_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED, [ $this, 'update_reservation' ] );
		add_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_UPDATED, [ $this, 'update_reservation' ] );

		add_filter( 'tec_tickets_commerce_get_attendee', [ $this, 'filter_attendee_object' ] );
		add_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/ticket/ticket-name',
			[ $this, 'include_seat_info_in_email' ],
			10,
			3
		);
		add_filter(
			'tribe_template_html:tickets/tickets/my-tickets/ticket-information',
			[ $this, 'inject_seat_info_in_my_tickets' ],
			10,
			4
		);

		add_filter(
			'tribe_template_html:tickets/components/attendees-list/attendees/attendee/ticket',
			[ $this, 'inject_seat_info_in_order_success_page' ],
			10,
			4
		);
		add_filter( 'pre_do_shortcode_tag', [ $this, 'filter_pre_do_shortcode_tag' ], 10, 4 );
		add_filter( 'tec_tickets_attendees_page_render_context', [ $this, 'adjust_attendee_page_render_context_for_seating' ], 10, 3 );

		$this->register_assets();
	}

	/**
	 * Before Tickets Commerce Checkout shortcode is rendered.
	 *
	 * @param false|string $output Short-circuit return value. Either false or the value to replace the shortcode with.
	 * @param string       $tag Shortcode name.
	 * @param array        $attr Shortcode attributes array, can be empty if the original arguments string cannot be parsed.
	 * @param array        $m Regular expression match array.
	 *
	 * @return bool|string Short-circuit return value.
	 */
	public function filter_pre_do_shortcode_tag( $output, $tag, $attr, $m ) {
		// If not checkout Shortcode then bail.
		if ( Checkout_Shortcode::get_wp_slug() !== $tag ) {
			return $output;
		}

		$this->cart->maybe_clear_cart_for_empty_session();

		return $output;
	}

	/**
	 * Unregisters all the hooks and implementations.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_cart_prepare_data', [ $this, 'handle_seat_selection' ] );
		remove_action(
			'tec_tickets_commerce_flag_action_generated_attendee',
			[ $this, 'save_seat_data_for_attendee' ]
		);

		// Remove attendee seat data column from the attendee list.
		remove_filter( 'tribe_tickets_attendee_table_columns', [ $this, 'add_attendee_seat_column' ] );
		remove_filter( 'tribe_events_tickets_attendees_table_column', [ $this, 'render_seat_column' ] );
		remove_filter( 'tec_tickets_attendees_table_sortable_columns', [ $this, 'include_seat_column_as_sortable' ] );
		remove_filter( 'tribe_repository_attendees_query_args', [ $this, 'handle_sorting_seat_column' ] );
		remove_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'remove_move_row_action' ] );

		remove_filter( 'tec_tickets_commerce_reports_tabbed_view_tab_map', [ $this, 'include_seats_tab' ] );
		remove_action( 'tec_tickets_commerce_reports_tabbed_view_after_register_tab', [ $this, 'register_seat_tab' ] );
		remove_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', [ $this, 'register_seat_tab' ] );
		remove_action( 'init', [ $this, 'register_seats_report_tab' ] );
		remove_action( 'admin_menu', [ $this, 'register_seats_report_page' ] );
		remove_filter( 'tec_tickets_commerce_reports_tabbed_page_title', [ $this, 'filter_seat_tab_title' ] );
		remove_filter( 'post_row_actions', [ $this, 'add_seats_row_action' ] );

		remove_action( 'tec_tickets_commerce_flag_action_generated_attendees', [ $this, 'confirm_all_reservations' ] );
		remove_action(
			'tec_tickets_commerce_order_status_flag_complete',
			[ $this, 'confirm_all_reservations_on_completion' ]
		);
		remove_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES, [ $this, 'fetch_attendees_by_post' ] );
		remove_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED, [ $this, 'update_reservation' ] );
		remove_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_UPDATED, [ $this, 'update_reservation' ] );

		remove_filter( 'tec_tickets_commerce_get_attendee', [ $this, 'filter_attendee_object' ] );
		remove_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/ticket/ticket-name',
			[ $this, 'include_seat_info_in_email' ],
			10,
			3
		);
		remove_filter(
			'tribe_template_html:tickets/tickets/my-tickets/ticket-information',
			[ $this, 'inject_seat_info_in_my_tickets' ],
			10,
			4
		);
		remove_filter(
			'tribe_template_html:tickets/components/attendees-list/attendees/attendee/ticket',
			[ $this, 'inject_seat_info_in_order_success_page' ],
			10,
			4
		);
		remove_filter( 'tec_tickets_commerce_attendee_to_delete', [ $this, 'handle_attendee_delete' ] );
		remove_filter( 'pre_do_shortcode_tag', [ $this, 'filter_pre_do_shortcode_tag' ] );
		remove_filter( 'tec_tickets_attendees_page_render_context', [ $this, 'adjust_attendee_page_render_context_for_seating' ] );
	}

	/**
	 * Filters the page title for the seat tab.
	 *
	 * @since 5.16.0
	 *
	 * @param string $title     The page title.
	 * @param int    $post_id   The post ID.
	 * @param string $page_type The page type.
	 *
	 * @return string
	 */
	public function filter_seat_tab_title( $title, $post_id, $page_type ): string {
		if ( Seats_Report::$page_slug !== $page_type ) {
			return $title;
		}
		// Translators: %1$s: the post/event title, %2$d: the post/event ID.
		$title = _x( 'Seats for: %1$s [#%2$d]', 'seat report screen heading', 'event-tickets' );

		return sprintf( $title, get_the_title( $post_id ), $post_id );
	}

	/**
	 * Registers the seat reports.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function register_seats_report_tab() {
		$this->seats_report->register_tab();
	}

	/**
	 * Registers the seat report page.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function register_seats_report_page() {
		$this->seats_report->register_seats_page();
	}

	/**
	 * Adds seat tab slug to the tab slug map.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,string> $tab_map The tab slug map.
	 *
	 * @return array<string,string>
	 */
	public function include_seats_tab( array $tab_map = [] ): array {
		$tab_map[ Seats_Report::$page_slug ] = Seats_Report::$tab_slug;

		return $tab_map;
	}

	/**
	 * Registers the seat tab.
	 *
	 * @since 5.16.0
	 *
	 * @param Tabbed_View $tabbed_view The tabbed view.
	 * @param WP_Post     $post        The post.
	 *
	 * @return void
	 */
	public function register_seat_tab( $tabbed_view, $post ) {
		if ( ! $post ) {
			return;
		}

		if ( ! tec_tickets_seating_enabled( $post->ID ) ) {
			return;
		}

		add_filter( 'tribe_tickets_attendees_show_title', '__return_false' );

		$report_tab = new Seats_Tab( $tabbed_view );
		$report_tab->set_url( Seats_Report::get_link( $post ) );
		$tabbed_view->register( $report_tab );
	}

	/**
	 * Handles the seat selection for the cart.
	 *
	 * @since 5.16.0
	 *
	 * @param array $data The data to prepare for the cart.
	 *
	 * @return array The prepared data.
	 */
	public function handle_seat_selection( array $data ): array {
		return $this->cart->handle_seat_selection( $data );
	}

	/**
	 * Saves the seat data for the attendee.
	 *
	 * @param WP_Post       $attendee The generated attendee.
	 * @param Ticket_Object $ticket   The ticket the attendee is generated for.
	 *
	 * @return void
	 */
	public function save_seat_data_for_attendee( $attendee, $ticket ): void {
		$this->cart->save_seat_data_for_attendee( $attendee, $ticket );
	}

	/**
	 * Adds the attendee seat column to the attendee list.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,string> $columns  The columns for the Attendees table.
	 * @param int                  $event_id The event ID.
	 *
	 * @return array<string,string> The filtered columns for the Attendees table.
	 */
	public function add_attendee_seat_column( array $columns, int $event_id ): array {
		return $this->attendee->add_attendee_seat_column( $columns, $event_id );
	}

	/**
	 * Renders the seat column for the attendee list.
	 *
	 * @since 5.16.0
	 *
	 * @param string              $value  Row item value.
	 * @param array<string,mixed> $item   Row item data.
	 * @param string              $column Column name.
	 *
	 * @return string The rendered column.
	 */
	public function render_seat_column( $value, $item, $column ) {
		return $this->attendee->render_seat_column( $value, $item, $column );
	}

	/**
	 * Include seats into sortable columns list.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,string> $columns The column names.
	 *
	 * @return array<string,string> The filtered columns.
	 */
	public function include_seat_column_as_sortable( array $columns ): array {
		return $this->attendee->filter_sortable_columns( $columns );
	}

	/**
	 * Handle seat column sorting.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,mixed> $query_args An array of the query arguments the query will be initialized with.
	 * @param WP_Query            $query      The query object, the query arguments have not been parsed yet.
	 * @param Attendee_Repository $repository This repository instance.
	 *
	 * @return array<string,mixed> The query args.
	 */
	public function handle_sorting_seat_column( $query_args, $query, $repository ): array {
		return $this->attendee->handle_sorting_seat_column( $query_args, $query, $repository );
	}

	/**
	 * Remove the move row action.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,mixed> $default_row_actions The default row actions list.
	 * @param array<string,mixed> $item                The attendee item array.
	 *
	 * @return array<string,mixed> The filtered row actions.
	 */
	public function remove_move_row_action( $default_row_actions, $item ) {
		return $this->attendee->remove_move_row_action( $default_row_actions, $item );
	}

	/**
	 * Confirms all the reservations contained in the Session cookie on generation of an Attendee.
	 * If the order status is complete, it will also delete the token session.
	 *
	 * @since 5.16.0
	 * @since 5.17.0 - Refactored to pass a bool variable to confirm_all_reservations.
	 *
	 * @param array<Attendee>          $attendees  The generated attendees, unused.
	 * @param \Tribe__Tickets__Tickets $ticket     The ticket the attendee is generated for, unused.
	 * @param \WP_Post                 $order      The order the attendee is generated for, unused.
	 * @param Status_Interface         $new_status New post status.
	 *
	 * @return void
	 */
	public function confirm_all_reservations( $attendees, $ticket, $order, $new_status ): void {
		$incomplete_flags = array_intersect( $new_status->get_flags(), [ 'incomplete', 'count_incomplete' ] );
		$delete_session   = count( $incomplete_flags ) === 0;
		$this->session->confirm_all_reservations( $delete_session );
	}

	/**
	 * Handle attendee delete.
	 *
	 * @since 5.16.0
	 *
	 * @param int $attendee_id The attendee ID.
	 *
	 * @return int The attendee ID.
	 */
	public function handle_attendee_delete( int $attendee_id ): int {
		return $this->attendee->handle_attendee_delete( $attendee_id, $this->reservations );
	}

	/**
	 * Get the localized data for the report.
	 *
	 * @since 5.16.0
	 *
	 * @param int|null $post_id The post ID.
	 *
	 * @return array<string, string> The localized data.
	 */
	public function get_localized_data( ?int $post_id = null ): array {
		$post_id = $post_id ?: tribe_get_request_var( 'post_id' );

		if ( ! $post_id ) {
			return [];
		}

		return [
			'postId'      => $post_id,
			'seatTypeMap' => tribe( Frontend::class )->build_seat_type_map( $post_id ),
		];
	}

	/**
	 * Registers the assets used by the Seats Report tab under individual event views.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	private function register_assets(): void {
		Asset::add(
			'tec-tickets-seating-admin-seats-report',
			'admin/seatsReport.js',
			Tickets_Main::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->enqueue_on( Seats_Report::$asset_action )
			->add_localize_script(
				'tec.tickets.seating.admin.seatsReportData',
				fn() => $this->get_localized_data( get_the_ID() )
			)
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-seats-report-style',
			'admin/style-seatsReport.css',
			Tickets_Main::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( Seats_Report::$asset_action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->register();
	}

	/**
	 * Fetch attendees by post.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function fetch_attendees_by_post(): void {
		$post_id = (int) tribe_get_request_var( 'postId' );

		if ( ! $this->check_current_ajax_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$current_batch = (int) tribe_get_request_var( 'currentBatch', 1 );

		/**
		 * Filters the number of Attendees to fetch per page when replying to the Fetch Attendees AJAX request.
		 *
		 * @since 5.16.0
		 *
		 * @param int $per_page The number of attendees to fetch per page.
		 * @param int $post_id  The post ID.
		 */
		$per_page = apply_filters( 'tec_tickets_seating_fetch_attendees_per_page', 50, $post_id );

		$data = Tickets::get_attendees_by_args(
			[
				'page'               => $current_batch,
				'per_page'           => $per_page,
				'return_total_found' => true,
				'order'              => 'DESC',
			],
			$post_id
		);

		$total_found   = $data['total_found'] ?? 0;
		$total_batches = (int) ( ceil( $total_found / $per_page ) );
		$attendees     = $data['attendees'] ?? [];

		if ( 0 === $total_found ) {
			wp_send_json_success(
				[
					'attendees'    => [],
					'totalBatches' => $total_batches,
					'currentBatch' => $current_batch,
					'nextBatch'    => false,
				]
			);

			return;
		}

		$formatted_attendees = $this->attendee->format_many( $attendees );

		wp_send_json_success(
			[
				'attendees'    => $formatted_attendees,
				'totalBatches' => $total_batches,
				'currentBatch' => $current_batch,
				'nextBatch'    => $current_batch === $total_batches ? false : $current_batch + 1,
			]
		);
	}

	/**
	 * Filters the default Attendee object to include seating data.
	 *
	 * @since 5.16.0
	 *
	 * @param WP_Post $post The attendee post object, decorated with a set of custom properties.
	 *
	 * @return WP_Post
	 */
	public function filter_attendee_object( WP_Post $post ): WP_Post {
		return $this->attendee->include_seating_data( $post );
	}

	/**
	 * Includes seating data in the email.
	 *
	 * @since 5.16.0
	 *
	 * @param string                $file     The email file.
	 * @param array<string, string> $name     The email name.
	 * @param Template              $template The email context.
	 *
	 * @return void
	 */
	public function include_seat_info_in_email( $file, $name, $template ): void {
		$this->attendee->include_seat_info_in_email( $template );
	}

	/**
	 * Inject seating label with ticket name on My Tickets page.
	 *
	 * @since 5.16.0
	 *
	 * @param string        $html     The HTML content of ticket information.
	 * @param string        $file     Complete path to include the PHP File.
	 * @param array<string> $name     Template name.
	 * @param Template      $template Current instance of the Tribe__Template.
	 *
	 * @return string The HTML content of ticket information.
	 */
	public function inject_seat_info_in_my_tickets( $html, $file, $name, $template ): string {
		return $this->attendee->inject_seat_info_in_my_tickets( $html, $template );
	}

	/**
	 * Inject seating label with ticket name on Order success page.
	 *
	 * @since 5.16.0
	 *
	 * @param string        $html     The HTML content of ticket information.
	 * @param string        $file     Complete path to include the PHP File.
	 * @param array<string> $name     Template name.
	 * @param Template      $template Current instance of the Tribe__Template.
	 *
	 * @return string The HTML content of ticket information.
	 */
	public function inject_seat_info_in_order_success_page( $html, $file, $name, $template ): string {
		return $this->attendee->inject_seat_info_in_order_success_page( $html, $template );
	}

	/**
	 * Updates an Attendee reservation from AJAX data.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function update_reservation(): void {
		$post_id = (int) tribe_get_request_var( 'postId' );

		if ( ! $this->check_current_ajax_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$json = $this->get_request_json();

		if ( ! (
			is_array( $json )
			&& tribe_sanitize_deep( $json )
			&& isset(
				$json['attendeeId'],
				$json['reservationId'],
				$json['seatTypeId'],
				$json['seatLabel']
			)
		) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		$attendee_id = (int) $json['attendeeId'];

		if ( isset( $json['ticketId'] ) ) {
			$new_ticket_id           = $json['ticketId'];
			$attendee_to_ticket_keys = array_values( tribe_attendees()->attendee_to_ticket_keys() );
			global $wpdb;
			$attendee_to_ticket_keys_list = DB::prepare(
				implode( ',', array_fill( 0, count( $attendee_to_ticket_keys ), '%s' ) ),
				$attendee_to_ticket_keys
			);
			$current_ticket_id            = DB::get_var(
				DB::prepare(
					"SELECT meta_value FROM %i WHERE post_id = %d AND meta_key IN ({$attendee_to_ticket_keys_list})",
					$wpdb->postmeta,
					$attendee_id
				)
			);

			if ( $current_ticket_id != $new_ticket_id ) {
				$move_tickets = Tickets_Main::instance()->move_tickets();
				$moved        = $move_tickets->move_tickets(
					[ $attendee_id ],
					$new_ticket_id,
					$post_id,
					$post_id
				);

				if ( ! $moved ) {
					wp_send_json_error(
						[
							'message' => 'Failed to move the attendee to the new ticket.',
						],
						500
					);

					return;
				}
			}
		}

		update_post_meta( $attendee_id, Meta::META_KEY_RESERVATION_ID, $json['reservationId'] );
		update_post_meta( $attendee_id, Meta::META_KEY_SEAT_TYPE, $json['seatTypeId'] );
		update_post_meta( $attendee_id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, $json['seatLabel'] );

		if ( ! empty( $json['sendUpdateToAttendee'] ) ) {
			$provider = tribe_tickets_get_ticket_provider( $attendee_id );
			if ( $provider ) {
				$sent = $provider->send_tickets_email_for_attendees(
					[ $attendee_id ],
					[
						'post_id' => $post_id,
					]
				);

				if ( empty( $sent ) ) {
					wp_send_json_error(
						[
							'error' => 'Failed to send the update mail.',
						]
					);

					return;
				}
			}
		}

		$attendees = Tickets::get_attendees_by_args(
			[
				'page'     => 1,
				'per_page' => 1,
				'by'       => [ 'id' => $attendee_id ],
			],
			$post_id
		);
		$formatted = $this->attendee->format_many( $attendees['attendees'] ?? [] );

		wp_send_json_success( $formatted[0] );
	}

	/**
	 * Display row actions in the post listing for seats.
	 *
	 * @param array<string,string> $actions The action items.
	 * @param WP_Post              $post The post object.
	 *
	 * @return array<string,string> The action items.
	 */
	public function add_seats_row_action( array $actions, $post ): array {
		return $this->seats_report->add_seats_row_action( $actions, $post );
	}

	/**
	 * Adjust the attendee page render context for seating.
	 *
	 * @param array<string,mixed>  $render_context The render context.
	 * @param int                  $post_id        The post ID.
	 * @param array<Ticket_Object> $tickets        The tickets.
	 *
	 * @return array<string,mixed> The adjusted render context.
	 */
	public function adjust_attendee_page_render_context_for_seating( $render_context, $post_id, $tickets ) {
		if ( ! ( is_array( $render_context ) && is_numeric( $post_id ) && is_array( $tickets ) ) ) {
			return $render_context;
		}

		if ( ! tec_tickets_seating_enabled( $post_id ) ) {
			return $render_context;
		}

		return $this->attendee->adjust_attendee_page_render_context_for_seating( $render_context, (int) $post_id, $tickets );
	}

	/**
	 * On completion of a TC Order, confirm all the reservations and clear the session.
	 *
	 * @since 5.17.0
	 *
	 * @return void
	 */
	public function confirm_all_reservations_on_completion(): void {
		// Attendees needing the session information will likely be generated after, warmup the session cache now.
		$this->cart->warmup_caches();

		$this->session->confirm_all_reservations();
	}
}
