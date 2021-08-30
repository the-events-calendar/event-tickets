<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Flag Action Interface.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
interface Flag_Action_Interface {
	/**
	 * Gets the flags that we could trigger this flag action for.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return string[]
	 */
	public function get_flags( \WP_Post $post );

	/**
	 * Gets the post types that we could trigger this flag action for.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	public function get_post_types();

	/**
	 * Which priority we will hook this particular flag action.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_priority();

	/**
	 * Determines if a transition of status will trigger this flag action.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface      $new_status New post status.
	 * @param Status_Interface|null $old_status Old post status.
	 * @param \WP_Post              $post       Post object.
	 *
	 * @return bool
	 */
	public function should_trigger( Status_Interface $new_status, $old_status, $post );

	/**
	 * Determines if a given status has the correct action flag to trigger.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface $status   Which status we are checking for.
	 * @param string           $operator Which conditional we are using for checking.
	 * @param \WP_Post         $post     Post object.
	 *
	 * @return bool
	 */
	public function has_flags( Status_Interface $status, $operator = 'AND', \WP_Post $post = null );

	/**
	 * Determines if a given post object is the correct post type to trigger this flag action
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function is_correct_post_type( \WP_Post $post );

	/**
	 * Handles the action flag execution.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface      $new_status New post status.
	 * @param Status_Interface|null $old_status Old post status.
	 * @param \WP_Post              $post       Post object.
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post );

	/**
	 * Triggers the handle method if should_trigger method is true.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface      $new_status New post status.
	 * @param Status_Interface|null $old_status Old post status.
	 * @param \WP_Post              $post       Post object.
	 */
	public function maybe_handle( Status_Interface $new_status, $old_status, $post );

	/**
	 * Handles the hooking of a given flag action to the correct actions in WP.
	 *
	 * @since TBD
	 */
	public function hook();
}