<?php
/**
 * The controller for the Seating Orders.
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */

namespace TEC\Tickets\Seating\Orders;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Admin\Attendees\Page as Attendee_Page;
use TEC\Tickets\Seating\Admin\Ajax;
use TEC\Tickets\Seating\Ajax_Checks;
use TEC\Tickets\Seating\Built_Assets;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe__Tabbed_View as Tabbed_View;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;
use WP_Query;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Template as Template;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Controller extends Controller_Contract {
	use Built_Assets;
	use Ajax_Checks;

	/**
	 * A reference to Attendee data handler
	 *
	 * @since TBD
	 *
	 * @var Attendee
	 */
	private Attendee $attendee;

	/**
	 * A reference to Cart data handler
	 *
	 * @since TBD
	 *
	 * @var Cart
	 */
	private Cart $cart;

	/**
	 * A reference to the seat selection session handler
	 *
	 * @since TBD
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
	 * Controller constructor.
	 *
	 * @since TBD
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
	 * @since TBD
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
		
		add_action( 'tec_tickets_commerce_flag_action_generated_attendees', [ $this, 'confirm_all_reservations' ] );
		add_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES, [ $this, 'fetch_attendees_by_post' ] );
		
		add_filter( 'tec_tickets_commerce_get_attendee', [ $this, 'filter_attendee_object' ] );
		add_action( 'tribe_template_before_include:tickets/emails/template-parts/body/ticket/ticket-name', [ $this, 'include_seat_info_in_email' ], 10, 3 );
		add_filter( 'tribe_template_html:tickets/tickets/my-tickets/ticket-information', [ $this, 'inject_seat_info_in_my_tickets' ], 10, 4 );
		
		$this->register_assets();
	}

	/**
	 * Unregisters all the hooks and implementations.
	 *
	 * @since TBD
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
		remove_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES, [ $this, 'fetch_attendees_by_post' ] );
		
		remove_filter( 'tec_tickets_commerce_get_attendee', [ $this, 'filter_attendee_object' ] );
		remove_action( 'tribe_template_before_include:tickets/emails/template-parts/body/ticket/ticket-name', [ $this, 'include_seat_info_in_email' ], 10, 3 );
		remove_filter( 'tribe_template_html:tickets/tickets/my-tickets/ticket-information', [ $this, 'inject_seat_info_in_my_tickets' ], 10, 4 );
		remove_filter( 'tec_tickets_commerce_attendee_to_delete', [ $this, 'handle_attendee_delete' ] );
	}

	/**
	 * Filters the page title for the seat tab.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_seats_report_tab() {
		$this->seats_report->register_tab();
	}
	
	/**
	 * Registers the seat report page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_seats_report_page() {
		$this->seats_report->register_seats_page();
	}

	/**
	 * Adds seat tab slug to the tab slug map.
	 *
	 * @since TBD
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
	 * @since TBD
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

		add_filter( 'tribe_tickets_attendees_show_title', '__return_false' );

		$report_tab = new Seats_Tab( $tabbed_view );
		$report_tab->set_url( Seats_Report::get_link( $post ) );
		$tabbed_view->register( $report_tab );
	}

	/**
	 * Handles the seat selection for the cart.
	 *
	 * @since TBD
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
	 * @param WP_Post       $attendee   The generated attendee.
	 * @param Ticket_Object $ticket     The ticket the attendee is generated for.
	 *
	 * @return void
	 */
	public function save_seat_data_for_attendee( $attendee, $ticket ): void {
		$this->cart->save_seat_data_for_attendee( $attendee, $ticket );
	}

	/**
	 * Adds the attendee seat column to the attendee list.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * Confirms all the reservations contained in the Session cookie.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function confirm_all_reservations(): void {
		$this->session->confirm_all_reservations();
	}
	
	/**
	 * Handle attendee delete.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_assets(): void {
		Asset::add(
			'tec-tickets-seating-admin-seats-report',
			$this->built_asset_url( 'admin/seatsReport.js' ),
			Tickets_Main::VERSION
		)
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->enqueue_on( Seats_Report::$asset_action )
			->add_localize_script(
				'tec.tickets.seating.admin.seatsReport.data',
				fn() => $this->get_localized_data( get_the_ID() )
			)
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-seats-report-style',
			$this->built_asset_url( 'admin/seatsReport.css' ),
			Tickets_Main::VERSION
		)
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( Seats_Report::$asset_action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->register();
	}

	/**
	 * Fetch attendees by post.
	 *
	 * @since TBD
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function fetch_attendees_by_post(): void {
		$post_id = (int) tribe_get_request_var( 'postId' );

		if ( ! $this->check_current_ajax_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$current_page = (int) tribe_get_request_var( 'page', 1 );

		$args = [
			'page'               => $current_page,
			'per_page'           => 50,
			'return_total_found' => true,
			'order'              => 'DESC',
		];

		$data                  = Tickets::get_attendees_by_args( $args, $post_id );
		$formatted             = [];
		$unknown_attendee_name = __( 'Unknown', 'event-tickets' );
		$associated_attendees  = array_reduce(
			$data['attendees'],
			function ( array $carry, array $attendee ): array {
				$carry[ $attendee['purchaser_id'] ]++;

				return $carry;
			},
			[]
		);

		foreach ( $data['attendees'] as $attendee ) {
			$id      = (int) $attendee['attendee_id'];
			$user_id = (int) ( $attendee['user_id'] ?? 0 );
			if ( $user_id > 0 ) {
				$user                       = get_user_by( 'id', $user_id );
				$attendee['purchaser_name'] = $user ? $user->display_name : $unknown_attendee_name;
			} else {
				$attendee['purchaser_name'] ??= $unknown_attendee_name;
			}

			$name = trim( $attendee['holder_name'] ?? '' );
			if ( ! $name ) {
				$name = $attendee['purchaser_name'];
			}
			$purchaser_id = $attendee['purchaser_id'];

			$formatted[] = [
				'id'            => $id,
				'name'          => $name,
				'purchaser'     => [
					'id'                  => $purchaser_id,
					'name'                => $attendee['purchaser_name'],
					'associatedAttendees' => $associated_attendees[ $purchaser_id ],
				],
				'ticketId'      => $attendee['product_id'],
				'seatTypeId'    => get_post_meta( $id, Meta::META_KEY_SEAT_TYPE, true ),
				'seatLabel'     => get_post_meta( $id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ),
				'reservationId' => get_post_meta( $id, Meta::META_KEY_RESERVATION_ID, true ),
			];
		}

		wp_send_json_success(
			[
				'attendees' => $formatted,
				'total'     => $data['total_found'],
			]
		);
	}
	
	/**
	 * Filters the default Attendee object to include seating data.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post   The attendee post object, decorated with a set of custom properties.
	 *
	 * @return WP_Post
	 */
	public function filter_attendee_object( WP_Post $post ): WP_Post {
		return $this->attendee->include_seating_data( $post );
	}
	
	/**
	 * Includes seating data in the email.
	 *
	 * @since TBD
	 *
	 * @param string                $file   The email file.
	 * @param array<string, string> $name   The email name.
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
	 * @since TBD
	 *
	 * @param string        $html The HTML content of ticket information.
	 * @param string        $file Complete path to include the PHP File.
	 * @param array<string> $name Template name.
	 * @param Template      $template Current instance of the Tribe__Template.
	 *
	 * @return string The HTML content of ticket information.
	 */
	public function inject_seat_info_in_my_tickets( $html, $file, $name, $template ): string {
		return $this->attendee->inject_seat_info_in_my_tickets( $html, $template );
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
}
