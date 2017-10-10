<?php
/**
 * Management for Global Stock for tickets
 *
 * @since  4.1
 */
class Tribe__Tickets__Global_Stock {
	/**
	 * Post meta key used to store the global stock flag.
	 *
	 * @since 4.1
	 *
	 * @var   string
	 */
	const GLOBAL_STOCK_ENABLED = '_tribe_ticket_use_global_stock';

	/**
	 * Post meta key used to store the actual global stock level.
	 *
	 * @since 4.1
	 *
	 * @var   string
	 */
	const GLOBAL_STOCK_LEVEL = '_tribe_ticket_global_stock_level';

	/**
	 * Flag used to indicate that a ticket will use the global stock.
	 *
	 * @since 4.1
	 *
	 * @var   string
	 */
	const GLOBAL_STOCK_MODE = 'global';

	/**
	 * Flag used to indicate that a ticket will use the global stock,
	 * but that a cap has been placed on the total number of sales for
	 * this ticket type.
	 *
	 * @since 4.1
	 *
	 * @var   string
	 */
	const CAPPED_STOCK_MODE = 'capped';

	/**
	 * Flag used to indicate that, if global stock is in effect for
	 * an event, the specific ticket this flag is applied to will
	 * maintain it's own inventory rather than draw from the global
	 * pool.
	 *
	 * @since 4.1
	 *
	 * @var   string
	 */
	const OWN_STOCK_MODE = 'own';

	/**
	 * Post meta key used to store the ticket global stock mode.
	 *
	 * @since TBD
	 *
	 * @var   string
	 */
	const TICKET_STOCK_MODE = '_tribe_ticket_global_stock_mode';

	/**
	 * Post meta key used to store the ticket global stock cap.
	 *
	 * @since TBD
	 *
	 * @var   string
	 */
	const TICKET_STOCK_CAP = '_tribe_ticket_global_stock_cap';

	/**
	 * Which post we are dealing with for this instance of stock
	 *
	 * @since TBD
	 *
	 * @var   int $post_id
	 */
	public $post_id;

	/**
	 * Creates an instance for a given Event
	 *
	 * @since  4.1
	 *
	 * @param int $post_id
	 */
	public function __construct( $post_id ) {
		$this->post_id = absint( $post_id );
	}

	/**
	 * Enables global stock control for the current post.
	 *
	 * As a convenience, false can be passed to this method to disable rather
	 * than enable global stock.
	 *
	 * @since  4.1
	 * @since  TBD Added a return so we can check if it was enabled correctly
	 *
	 * @param  bool $yes
	 *
	 * @return bool
	 */
	public function enable( $yes = true ) {
		return update_post_meta( $this->post_id, self::GLOBAL_STOCK_ENABLED, tribe_is_truthy( $yes ) );
	}

	/**
	 * Disables global stock control for the current post.
	 *
	 * As a convenience, false can be passed to this method to enable rather
	 * than disable global stock.
	 *
	 * @since  4.1
	 * @since  TBD Added a return so we can check if it was enabled correctly
	 *
	 * @param  bool $yes
	 *
	 * @return bool
	 */
	public function disable( $yes = true ) {
		return update_post_meta( $this->post_id, self::GLOBAL_STOCK_ENABLED, ! tribe_is_truthy( $yes ) );
	}

	/**
	 * Indicates if global stock is enabled for this post.
	 *
	 * @since  4.1
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return tribe_is_truthy( get_post_meta( $this->post_id, self::GLOBAL_STOCK_ENABLED, true ) );
	}

	/**
	 * Sets the global stock level for the current post.
	 *
	 * @since  4.1
	 * @since  TBD  Added a Return
	 *
	 * @param  int $quantity
	 *
	 * @return bool
	 */
	public function set_stock_level( $quantity ) {
		$status = update_post_meta( $this->post_id, self::GLOBAL_STOCK_LEVEL, (int) $quantity );

		/**
		 * Fires when the global stock level is set/changed.
		 *
		 * @since  4.1
		 * @since  TBD Added $status param
		 *
		 * @param  int  $post_id
		 * @param  int  $quantity
		 * @param  bool $status
		 */
		do_action( 'tribe_tickets_global_stock_level_changed', $this->post_id, $quantity, $status );

		return $status;
	}

	/**
	 * Returns the post's global stock.
	 *
	 * @since  4.1
	 *
	 * @return int
	 */
	public function get_stock_level() {
		return (int) get_post_meta( $this->post_id, self::GLOBAL_STOCK_LEVEL, true );
	}

	/**
	 * Returns a count of the number of global ticket sales for this event.
	 *
	 * @since  4.1
	 *
	 * @return int
	 */
	public function tickets_sold() {
		$sales = 0;
		$tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $this->post_id );

		foreach ( $tickets as $ticket ) {
			/**
			 * @var Tribe__Tickets__Ticket_Object $ticket
			 */
			switch ( $ticket->global_stock_mode() ) {
				case self::CAPPED_STOCK_MODE:
				case self::GLOBAL_STOCK_MODE:
					$sales += (int) $ticket->qty_sold();
				break;
			}
		}

		return $sales;
	}
}
