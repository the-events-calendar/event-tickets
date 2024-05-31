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
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Seating\Meta;
use Tribe__Utils__Array as Arr;
/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Controller extends Controller_Contract {
	
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
		foreach ( $data['tickets'] as $key => $ticket_data ) {
			if ( ! isset( $ticket_data['seat_labels'] ) ) {
				continue;
			}
			
			$ticket_data['extra']['seats'] = $ticket_data['seat_labels'];
			
			$data['tickets'][ $key ] = $ticket_data;
		}
		
		return $data;
	}
	
	/**
	 * Saves the seat data for the attendee.
	 *
	 * @param Attendee                 $attendee               The generated attendee.
	 * @param \Tribe__Tickets__Tickets $ticket The ticket the attendee is generated for.
	 * @param \WP_Post                 $order              The order the attendee is generated for.
	 * @param Status_Interface         $new_status      New post status.
	 * @param Status_Interface|null    $old_status Old post status.
	 * @param array                    $item Which cart item this was generated for.
	 * @param int                      $i      Which Attendee index we are generating.
	 */
	public function save_seat_data_for_attendee( $attendee, $ticket, $order, $new_status, $old_status, $item, $i ) {
		$seats = Arr::get( $item, [ 'extra', 'seats' ], false );
		
		if ( empty( $seats ) || ! isset( $seats[ $i ] ) ) {
			return;
		}
		
		update_post_meta( $attendee->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, $seats[ $i ] );
	}
}
