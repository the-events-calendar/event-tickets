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
use WP_Post;
use Tribe__Tickets__Tickets;

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
	 * Controller constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The DI container.
	 * @param Attendee  $attendee   The Attendee data handler.
	 * @param Cart      $cart       The Cart data handler.
	 */
	public function __construct( Container $container, Attendee $attendee, Cart $cart ) {
		parent::__construct( $container );
		$this->attendee = $attendee;
		$this->cart     = $cart;
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
		}
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
}
