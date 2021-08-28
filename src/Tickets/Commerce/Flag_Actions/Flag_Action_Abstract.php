<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Status\Status_Interface;


/**
 * Class Flag Action Abstract.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
abstract class Flag_Action_Abstract implements Flag_Action_Interface {
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
	 * {@inheritDoc}
	 */
	public function get_flags() {
		return $this->flags;
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
	 * {@inheritDoc}
	 */
	public function should_trigger( Status_Interface $new_status, $old_status, $post ) {
		if ( ! $this->has_flags( $new_status ) ) {
			return false;
		}

		if ( ! $this->is_correct_post_type( $post ) ) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function has_flags( Status_Interface $status ) {
		return $status->has_flags( $this->get_flags() );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_correct_post_type( \WP_Post $post ) {
		return in_array( $post->post_type, $this->get_post_types(), true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function maybe_handle( Status_Interface $new_status, $old_status, $post ) {
		if ( ! $this->should_trigger( $new_status, $old_status, $post ) ) {
			return;
		}
		/**
		 * @todo For now Flag actions are only for order, so we use `tec_tc_get_order()` but if in the future we add any
		 *       other post types to the mix we will need to provide a way to pass the post via a formatting method.
		 */
		$post = tec_tc_get_order( $post );

		$this->handle( $new_status, $old_status, $post );
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