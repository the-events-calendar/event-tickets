<?php
// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class Tribe__Tickets__Attendees_CSV_Export {
	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return self
	 *
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance instanceof self ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Hook the necessary filters and Actions!
	 * @return void
	 */
	public static function hook() {
		$myself = self::instance();

		add_filter( 'tribe_events_tickets_attendees_csv_items', array( $myself, 'add_attendees_columns' ), 4 );

		return $myself;
	}

	/**
	 * Add CSV Item columns to the array
	 *
	 * @param  array  $items
	 * @return array
	 */
	public function add_attendees_columns( $items ) {

		$count = 0;
		foreach ( $items as &$item ) {
			// Add the header columns
			if ( 1 === ++$count ) {
				$item[] = esc_attr__( 'Customer Email Address', 'event-tickets-plus' );
				$item[] = esc_attr__( 'Customer Name', 'event-tickets-plus' );
			}
			// Populate the new columns in each subsequent row
			else {
				// Clean a Bit of the ID
				$order_id = absint( $item[0] );

				// Forget about non valid ids
				if ( $order_id <= 0 ) {
					continue;
				}

				// Assumes that the order ID lives in the first column
				$order = wc_get_order( $order_id );
				$item[] = $order->billing_email;
				$item[] = $order->billing_first_name . ' ' . $order->billing_last_name;
			}
		}

		var_dump( $items );
		exit;


		return $items;
	}

}
