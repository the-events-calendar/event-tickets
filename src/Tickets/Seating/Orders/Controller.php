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
use TEC\Tickets\Admin\Attendees\Page as Attendee_Page;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Service\Reservations;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use Tribe__Tickets__Tickets;
use WP_Post;
use WP_Query;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Controller extends Controller_Contract {
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
	 * Controller constructor.
	 *
	 * @since TBD
	 *
	 * @param Container    $container    The DI container.
	 * @param Attendee     $attendee     The Attendee data handler.
	 * @param Cart         $cart         The Cart data handler.
	 * @param Session      $session      The seat selection session handler.
	 */
	public function __construct(
		Container $container,
		Attendee $attendee,
		Cart $cart,
		Reservations $reservations,
		Session $session
	) {
		parent::__construct( $container );
		$this->attendee = $attendee;
		$this->cart     = $cart;
		$this->reservations = $reservations;
		$this->session = $session;
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
		add_action( 'tec_tickets_commerce_flag_action_generated_attendee', [ $this, 'save_seat_data_for_attendee' ], 10, 7 );

		// Add attendee seat data column to the attendee list.
		if ( tribe_get_request_var( 'page' ) === 'tickets-attendees' || tribe( Attendee_Page::class )->is_on_page() ) {
			add_filter( 'tribe_tickets_attendee_table_columns', [ $this, 'add_attendee_seat_column' ], 10, 2 );
			add_filter( 'tribe_events_tickets_attendees_table_column', [ $this, 'render_seat_column' ], 10, 3 );
			add_filter( 'tec_tickets_attendees_table_sortable_columns', [ $this, 'include_seat_column_as_sortable' ] );
			add_filter( 'tribe_repository_attendees_query_args', [ $this, 'handle_sorting_seat_column' ], 10, 3 );
			add_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'remove_move_row_action' ], 10, 2 );
		}

		add_action( 'tec_tickets_commerce_flag_action_generated_attendee', [ $this, 'confirm_all_reservations' ] );
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
		remove_action( 'tec_tickets_commerce_flag_action_generated_attendee', [ $this, 'save_seat_data_for_attendee' ] );

		// Remove attendee seat data column from the attendee list.
		remove_filter( 'tribe_tickets_attendee_table_columns', [ $this, 'add_attendee_seat_column' ] );
		remove_filter( 'tribe_events_tickets_attendees_table_column', [ $this, 'render_seat_column' ] );
		remove_filter( 'tec_tickets_attendees_table_sortable_columns', [ $this, 'include_seat_column_as_sortable' ] );
		remove_filter( 'tribe_repository_attendees_query_args', [ $this, 'handle_sorting_seat_column' ] );
		remove_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'remove_move_row_action' ] );

		remove_action( 'tec_tickets_commerce_flag_action_generated_attendee', [ $this, 'confirm_all_reservations' ] );
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
	 * @param WP_Post                 $attendee               The generated attendee.
	 * @param Tribe__Tickets__Tickets $ticket The ticket the attendee is generated for.
	 * @param WP_Post                 $order              The order the attendee is generated for.
	 * @param Status_Interface        $new_status      New post status.
	 * @param Status_Interface|null   $old_status Old post status.
	 * @param array                   $item Which cart item this was generated for.
	 * @param int                     $i      Which Attendee index we are generating.
	 *
	 * @return void
	 */
	public function save_seat_data_for_attendee( $attendee, $ticket, $order, $new_status, $old_status, $item, $i ): void {
		$this->cart->save_seat_data_for_attendee( $attendee, $ticket, $order, $new_status, $old_status, $item, $i );
	}

	/**
	 * Adds the attendee seat column to the attendee list.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $columns The columns for the Attendees table.
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
	 * @param WP_Query            $query The query object, the query arguments have not been parsed yet.
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
	 * @param array<string,mixed> $item The attendee item array.
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
}
