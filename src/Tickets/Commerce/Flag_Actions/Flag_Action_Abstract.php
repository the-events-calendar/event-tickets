<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Emails\Order_Type_Trait;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Flag Action Abstract.
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
abstract class Flag_Action_Abstract implements Flag_Action_Interface {
	use Order_Type_Trait;

	/**
	 * When will this particular flag wil be triggered
	 *
	 * @since 5.1.9
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Which flags are associated and will trigger this action.
	 *
	 * @since 5.1.9
	 *
	 * @var string[]
	 */
	protected $flags = [];

	/**
	 * Which Post Types we check for this flag action.
	 *
	 * @since 5.1.9
	 *
	 * @var string[]
	 */
	protected $post_types;

	/**
	 * Which order types this flag action applies to.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $order_contexts = [
		Order_Context::ALL,
	];

	/**
	 * Marks a given order with all the flags for this given status update.
	 * The value of those markers is the time where the update happened.
	 *
	 * @since 5.1.10
	 *
	 * @param Status_Interface      $new_status New status.
	 * @param null|Status_Interface $old_status Old status.
	 * @param \WP_Post              $post       Order post object.
	 */
	protected function mark( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		foreach ( $this->get_flags( $post ) as $flag ) {
			$marker_meta_key = Order::get_flag_action_marker_meta_key( $flag, $new_status );
			add_post_meta( $post->ID, $marker_meta_key, tec_get_current_milliseconds() );
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.29.0 Made $post explicitly nullable.
	 */
	public function get_flags( ?\WP_Post $post = null ) {
		$flags = $this->flags;

		/**
		 * Allows the modifications of which flags will trigger this Action.
		 *
		 * @since 5.1.10
		 *
		 * @param string[] $flags       Which flags will trigger this action.
		 * @param \WP_Post $post        Post object.
		 * @param static   $action_flag Instance of action flag we are triggering.
		 */
		return apply_filters( 'tec_tickets_commerce_flag_actions_get_flags', $flags, $post, $this );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_priority() {
		return $this->priority;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_post_types() {
		return $this->post_types;
	}

	/**
	 * Gets the order type contexts this flag action applies to.
	 *
	 * @since TBD Filters on a per-class tag (`get_order_context_filter_tag()`) instead of one name
	 *            shared by every subclass, so each flag action can be targeted individually.
	 *
	 * @return string[]
	 */
	public function get_order_contexts() {
		$contexts = $this->order_contexts;

		/**
		 * Allows modifications of which order types will trigger this action.
		 *
		 * @since TBD
		 *
		 * @param string[] $contexts    Which order types will trigger this action.
		 * @param static   $action_flag Instance of action flag we are triggering.
		 */
		return apply_filters( $this->get_order_context_filter_tag(), $contexts, $this );
	}

	/**
	 * Builds the class-unique filter tag used by `get_order_contexts()`.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_order_context_filter_tag(): string {
		return 'tec_tickets_commerce_flag_actions_get_order_contexts_' . strtolower( str_replace( '\\', '_', static::class ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD Now also checks `is_correct_order_context()` so a flag action can be scoped to
	 *            RSVP-only or ticket-only orders.
	 */
	public function should_trigger( Status_Interface $new_status, $old_status, $post ) {
		if ( ! $this->has_flags( $new_status, 'AND', $post ) ) {
			return false;
		}

		if ( ! $this->is_correct_post_type( $post ) ) {
			return false;
		}

		if ( ! $this->is_correct_order_context( $post ) ) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.29.0 Made $post explicitly nullable.
	 */
	public function has_flags( Status_Interface $status, $operator = 'AND', ?\WP_Post $post = null ) {
		return $status->has_flags( $this->get_flags( $post ), $operator, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_correct_post_type( \WP_Post $post ) {
		return in_array( $post->post_type, $this->get_post_types(), true );
	}

	/**
	 * Determines whether the order matches this action's registered order context(s).
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $order The decorated order post object.
	 *
	 * @return bool
	 */
	public function is_correct_order_context( \WP_Post $order ): bool {
		$contexts = $this->get_order_contexts();

		if ( in_array( Order_Context::ALL, $contexts, true ) ) {
			return true;
		}

		$is_rsvp      = $this->is_rsvp_order( $order );
		$wants_rsvp   = in_array( Order_Context::RSVP_V2, $contexts, true );
		$wants_ticket = in_array( Order_Context::TICKET, $contexts, true );

		if ( $wants_rsvp && $wants_ticket ) {
			return true;
		}

		return $wants_rsvp ? $is_rsvp : ! $is_rsvp;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD The post is now resolved to its decorated order via `tec_tc_get_order()` before
	 *            `should_trigger()` runs, so order-context checks can read `$order->items`.
	 */
	public function maybe_handle( Status_Interface $new_status, $old_status, $post ) {
		/**
		 * @todo For now Flag actions are only for order, so we use `tec_tc_get_order()` but if in the future we add any
		 *       other post types to the mix we will need to provide a way to pass the post via a formatting method.
		 */
		$post = tec_tc_get_order( $post );

		if ( ! $this->should_trigger( $new_status, $old_status, $post ) ) {
			return;
		}

		$this->handle( $new_status, $old_status, $post );

		// After handling we mark this order with the flags from this action.
		$this->mark( $new_status, $old_status, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	public function hook() {
		foreach ( $this->get_flags() as $flag ) {
			add_action( "tec_tickets_commerce_order_status_flag_{$flag}", [ $this, 'maybe_handle' ], $this->get_priority(), 3 );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	abstract public function handle( Status_Interface $new_status, $old_status, \WP_Post $post );
}
