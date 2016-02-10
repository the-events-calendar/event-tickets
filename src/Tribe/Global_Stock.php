<?php
class Tribe__Tickets__Global_Stock {
	/**
	 * Post meta key used to store the global stock flag.
	 */
	const GLOBAL_STOCK_ENABLED = '_tribe_ticket_use_global_stock';

	/**
	 * Post meta key used to store the actual global stock level.
	 */
	const GLOBAL_STOCK_LEVEL = '_tribe_ticket_global_stock_level';

	/**
	 * Flag used to indicate that a ticket will use the global stock.
	 */
	const GLOBAL_STOCK_MODE = 'global';

	/**
	 * Flag used to indicate that a ticket will use the global stock,
	 * but that a cap has been placed on the total number of sales for
	 * this ticket type.
	 */
	const CAPPED_STOCK_MODE = 'capped';

	/**
	 * Flag used to indicate that, if global stock is in effect for
	 * an event, the specific ticket this flag is applied to will
	 * maintain it's own inventory rather than draw from the global
	 * pool.
	 */
	const OWN_STOCK_MODE = 'own';

	/**
	 * @var int $post_id
	 */
	protected $post_id;

	/**
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
	 * @param bool $yes
	 */
	public function enable( $yes = true ) {
		update_post_meta( $this->post_id, self::GLOBAL_STOCK_ENABLED, (bool) $yes );
	}

	/**
	 * Disables global stock control for the current post.
	 *
	 * As a convenience, false can be passed to this method to enable rather
	 * than disable global stock.
	 *
	 * @var bool $yes
	 */
	public function disable( $yes = true ) {
		update_post_meta( $this->post_id, self::GLOBAL_STOCK_ENABLED, ! (bool) $yes );
	}

	/**
	 * Indicates if global stock is enabled for this post.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_post_meta( $this->post_id, self::GLOBAL_STOCK_ENABLED, true );
	}

	/**
	 * Sets the global stock level for the current post.
	 *
	 * @param int $quantity
	 */
	public function set_stock_level( $quantity ) {
		update_post_meta( $this->post_id, self::GLOBAL_STOCK_LEVEL, (int) $quantity );
	}

	/**
	 * Returns the post's global stock.
	 *
	 * @return int
	 */
	public function get_stock_level() {
		return (int) get_post_meta( $this->post_id, self::GLOBAL_STOCK_LEVEL, true );
	}
}