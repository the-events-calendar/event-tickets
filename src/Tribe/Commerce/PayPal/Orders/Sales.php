<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Orders__Sales
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Orders__Sales {

	/**
	 * @var \Tribe__Cache
	 */
	protected $cache;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Orders__Sales constructor.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Cache|null $cache
	 */
	public function __construct( Tribe__Cache $cache = null ) {
		$this->cache = null === $cache ? new Tribe__Cache() : $cache;
	}

	/**
	 * Returns the revenue for a single attendee.
	 *
	 * @since TBD
	 *
	 * @param array $attendee The attendee details.
	 *
	 * @return int
	 */
	public function get_revenue_for_attendee( array $attendee ) {
		$revenue = $this->get_unfiltered_revenue_for_attendee( $attendee );

		/**
		 * Filters the revenue for a specific attendee.
		 *
		 * @since TBD
		 *
		 * @param int   $revenue  The revenue for this attendee.
		 * @param array $attendee The attendee details.
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_attendee_revenue', $revenue, $attendee );
	}

	/**
	 * Returns the unfiltered revenue for an attendee.
	 *
	 * @since TBD
	 *
	 * @param array $attendee
	 *
	 * @return int
	 */
	protected function get_unfiltered_revenue_for_attendee( $attendee ) {
		$product_id = Tribe__Utils__Array::get( $attendee, 'product_id', false );

		if ( ! filter_var( $product_id, FILTER_VALIDATE_INT ) ) {
			return 0;
		}

		$cached = $this->cache[ $product_id ];

		if ( false !== $cached ) {
			return $cached;
		}

		if ( ! $this->is_order_completed( $attendee ) ) {
			return 0;
		}

		$revenue = get_post_meta( $product_id, '_price', true );

		if ( ! filter_var( $revenue, FILTER_VALIDATE_INT ) ) {
			return 0;
		}

		return (int) $revenue;
	}

	/**
	 * Whether an attendee has been assigned a completed order status or not.
	 *
	 * @since TBD
	 *
	 * @param array $attendee
	 *
	 * @return bool
	 */
	public function is_order_completed( array $attendee ) {
		$order_status = Tribe__Utils__Array::get( $attendee, 'order_status', false );

		if ( false === $order_status || ! in_array( $order_status, $this->get_revenue_generating_order_stati(), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the filtered list of ticket stati that should be taken into account when calculating revenue.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function get_revenue_generating_order_stati() {
		$revenue_generating_order_stati = array( 'completed' );

		/**
		 * Filters the list of ticket stati that should be taken into account when calculating revenue.
		 *
		 * @since TBD
		 *
		 * @param  array $revenue_generating_order_stati
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_revenue_generating_order_stati', $revenue_generating_order_stati );
	}

	/**
	 * Returns the total revenue provided a list of attendees.
	 *
	 * @since TBD
	 *
	 * @param array $attendees
	 *
	 * @return int
	 */
	public function get_revenue_for_attendees( array $attendees ) {
		$revenue = array_sum( array_map( array( $this, 'get_revenue_for_attendee' ), $attendees ) );

		/**
		 * Filters the revenue for a list of attendees.
		 *
		 * @since TBD
		 *
		 * @param int   $revenue   The revenue for these attendees.
		 * @param array $attendees The attendees details.
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_attendees_revenue', $revenue, $attendees );
	}

	/**
	 * Returns the amount this attendee represents in sales terms.
	 *
	 * @since TBD
	 *
	 * @param array $attendee
	 *
	 * @return int
	 */
	public function get_sale_for_attendee( array $attendee ) {
		$sales_count = $this->is_order_completed( $attendee ) ? 1 : 0;

		/**
		 * Filters the sales count for an attendee.
		 *
		 * @since TBD
		 *
		 * @param int   $sales_count The sales count for this attendee; defaults to `1` per attendee
		 *                           with a sales generating order status.
		 * @param array $attendee    The attendee details.
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_attendee_sales_count', $sales_count, $attendee );
	}

	/**
	 * Returns the amount of sales for a list of attendees.
	 *
	 * @since TBD
	 *
	 * @param array $attendees
	 *
	 * @return int
	 */
	public function get_sales_for_attendees( $attendees ) {
		$sales = array_sum( array_map( array( $this, 'get_sale_for_attendee' ), $attendees ) );

		/**
		 * Filters the sales for a list of attendees.
		 *
		 * @since TBD
		 *
		 * @param int   $sales     The sales for these attendees.
		 * @param array $attendees The attendees details.
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_attendees_sales', $sales, $attendees );
	}

	/**
	 * Filters a list of attendees returning only those with not completed orders.
	 *
	 * @since TBD
	 *
	 * @param array $attendees The list of attendees to filter.
	 *
	 * @return array A list of attendees with not completed orders.
	 */
	public function filter_not_completed( array $attendees ) {
		return array_diff( $attendees, $this->filter_completed( $attendees ) );
	}

	/**
	 * Filters a list of attendees returning only those with completed orders.
	 *
	 * @since TBD
	 *
	 * @param array $attendees The list of attendees to filter.
	 *
	 * @return array A list of attendees with completed orders.
	 */
	public function filter_completed( array $attendees ) {
		return array_filter( $attendees, array( $this, 'is_order_completed' ) );
	}

	/**
	 * Whether the current ticket is a PayPal one or not.
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return bool
	 */
	public function is_paypal_ticket( Tribe__Tickets__Ticket_Object $ticket ) {
		return $ticket->provider_class === 'Tribe__Tickets__Commerce__PayPal__Main';
	}

	/**
	 * Filters an array of tickets to return only those that have least one sale.
	 *
	 * @since TBD
	 *
	 * @param array $tickets
	 *
	 * @return array
	 */
	public function filter_sold_tickets( array $tickets ) {
		return array_filter( $tickets, array( $this, 'has_sold' ) );
	}

	/**
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return bool
	 */
	public function has_sold( Tribe__Tickets__Ticket_Object $ticket ) {
		return $ticket->qty_sold() > 0;
	}

	public function get_tickets_breakdown_for( array $tickets ) {
		$breakdown = array(
			__( 'Completed', 'event-tickets' )     => array(
				'total' => array_sum( array_map( array( $this, 'get_ticket_completed_total' ), $tickets ) ),
				'qty'   => array_sum( array_map( array( $this, 'get_ticket_completed_qty' ), $tickets ) ),
			),
			__( 'Not completed', 'event-tickets' ) => array(
				'total' => array_sum( array_map( array( $this, 'get_ticket_not_completed_total' ), $tickets ) ),
				'qty'   => array_sum( array_map( array( $this, 'get_ticket_not_completed_qty' ), $tickets ) ),
			),
		);

		return $breakdown;
	}

	/**
	 * Returns the total revenue from completed orders for the ticket.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return int
	 */
	protected function get_ticket_completed_total(Tribe__Tickets__Ticket_Object $ticket){
		return (int)$ticket->qty_sold()	* (int)$ticket->price;
	}

	/**
	 * Returns the total number of completed orders for the ticket.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return int
	 */
	protected function get_ticket_completed_qty(Tribe__Tickets__Ticket_Object $ticket){
		return $ticket->qty_sold();
	}

	/**
	 * Returns the total revenue from not completed orders for the ticket.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return int
	 */
	protected function get_ticket_not_completed_total(Tribe__Tickets__Ticket_Object $ticket){
		return (int)$ticket->qty_pending() * (int)$ticket->price;
	}

	/**
	 * Returns the total number of not completed orders for the ticket.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return int
	 */
	protected function get_ticket_not_completed_qty( Tribe__Tickets__Ticket_Object $ticket ) {
		return $ticket->qty_pending();
	}
}