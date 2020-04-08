<?php
/**
 * Class that detects if given post
 * is displaying the Attendee List.
 */

namespace Tribe\Tickets\Events;

use Exception;
use InvalidArgumentException;
use Tribe__Tickets_Plus__Attendees_List;
use WP_Post;

/**
 * Class Attendee_List_Display
 *
 * @since 4.12.0
 *
 * @package Tribe\Tickets\Events
 */
class Attendee_List_Display {

	/**
	 * @var string Meta name to control whether the Attendee List
	 *             meta was changed by a shortcode in the content.
	 */
	protected static $attendee_list_by_shortcode = 'tribe_tickets_attendee_list_triggered_by_shortcode';

	/**
	 * Check if given event is hiding the attendees list.
	 *
	 * @param int|WP_Post $post The Post being checked.
	 *
	 * @throws InvalidArgumentException Could not determine if given event is hiding or showing the Attendee List.
	 *
	 * @return bool True if event is hiding the attendees list. False otherwise.
	 */
	public function is_event_hiding_attendee_list( $post ) {
		// Attendees list is a Plus feature, if ET Plus is not present it will always be hidden.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return true;
		}

		if ( is_numeric( $post ) ) {
			$post = WP_Post::get_instance( $post );
		}

		if ( ! $post instanceof WP_Post ) {
			/*
			 * There's no way to apply a sensible default in this scenario.
			 *
			 * Let's throw an exception so the client calling this function deals
			 * with it according to the >> caller << context.
			 */
			throw new InvalidArgumentException( '$post should be either a post ID or a WP_Post object.' );
		}

		return (bool) Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $post );
	}

	/**
	 * @filter tribe_tickets_plus_hide_attendees_list_optout 10 1
	 *
	 * @see \Tribe\Tickets\Events\Events_Service_Provider::hooks
	 *
	 * @param bool $should_hide Whether the optout form should be hidden or not.
	 *
	 * @return bool
	 */
	public function should_hide_optout( $should_hide ) {
		try {
			global $post;

			return $this->is_event_hiding_attendee_list( $post );
		} catch ( Exception $e ) {
			// Eg: global $post not a WP_Post object.
			return $should_hide;
		}
	}

	/**
	 * Determines whether this post is displaying the Attendees List.
	 *
	 * @action save_post_tribe_events 10 1
	 *
	 * @see \Tribe\Tickets\Events\Events_Service_Provider::hooks
	 *
	 * @retyrn void
	 *
	 * @param int|WP_Post $post The Post being checked.
	 */
	public function maybe_update_attendee_list_hide_meta( $post ) {
		// Attendees list is a Plus feature, if ET Plus is not present we don't have to update the meta.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		// Early bail: is an autosave or auto-draft.
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}

		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}

		// Early bail: Invalid post.
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$this->track_shortcode_driven_meta( $post );

		if ( $this->is_using_blocks() ) {
			$is_showing_attendee_list = $this->is_showing_attendee_list_with_blocks( $post );
		} else {
			$is_showing_attendee_list = $this->is_showing_attendee_list_with_classical_editor( $post );
		}

		/**
		 * Returns true if the post is displaying a list of attendees.
		 *
		 * You can use this filter to let the system know that you're displaying
		 * the Attendee List in some other way.
		 *
		 * @since TBD
		 *
		 * @param bool    $is_showing_attendee_list Whether the post is showing the attendee list or not
		 * @param WP_Post $post The WP_Post object being checked
		 * @param bool    $is_using_blocks Whether the post is using Blocks or not
		 *
		 * @return bool
		 */
		$is_showing_attendee_list = (bool) apply_filters(
			'tribe_tickets_event_is_showing_attendee_list',
			$is_showing_attendee_list,
			$post,
			$this->is_using_blocks()
		);

		update_post_meta( $post->ID, Tribe__Tickets_Plus__Attendees_List::HIDE_META_KEY, $is_showing_attendee_list );
	}

	/**
	 * This keeps track of whether the Attendee List is being displayed becase of a shortcode
	 * in the content, and acts accordingly if said shortcode is removed.
	 *
	 * @param WP_Post $post The Post being checked.
	 *
	 * @return void
	 */
	private function track_shortcode_driven_meta( WP_Post $post ) {
		$is_visible_by_meta                  = Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $post ) === false;
		$has_attendee_list_shortcode         = has_shortcode( $post->post_content, 'tribe_attendees_list' );
		$has_attendee_list_by_shortcode_meta = get_post_meta( $post->ID, self::$attendee_list_by_shortcode, true ) === 'yes';

		/*
		 * If what triggers the Attendee List to display is a shortcode in the content,
		 * let's save this piece of information so that we revert it when the shortcode is removed.
		 */
		if ( ! $is_visible_by_meta && $has_attendee_list_shortcode ) {
			update_post_meta( $post->ID, self::$attendee_list_by_shortcode, 'yes' );
		}

		/*
		 * The shortcode that triggered the Attendee List to display is no longer in the content.
		 * Thus, we shall update the "Attendee List" meta to "hidden" again.
		 */
		if ( $has_attendee_list_by_shortcode_meta && ! $has_attendee_list_shortcode ) {
			update_post_meta( $post->ID, self::$attendee_list_by_shortcode, 'no' );

			add_filter( 'tribe_tickets_event_is_showing_attendee_list', '__return_false' );
		}
	}

	/**
	 * @return bool
	 */
	private function is_using_blocks() {
		$should_load_blocks          = tribe( 'editor' )->should_load_blocks();
		$is_blocks_active_for_events = tribe_is_truthy( tribe_get_option( 'toggle_blocks_editor', false ) );
		$is_using_blocks             = $should_load_blocks && $is_blocks_active_for_events;

		/**
		 * Whether post content is being served through blocks
		 * or the classical editor.
		 *
		 * @since TBD
		 *
		 * @param bool $is_using_blocks True if using blocks. False if using the classical editor.
		 */
		$is_using_blocks = (bool) apply_filters( 'tribe_is_using_blocks', $is_using_blocks );

		return $is_using_blocks;
	}

	/**
	 * @param WP_Post $post The Post being checked.
	 *
	 * @return bool
	 */
	private function is_showing_attendee_list_with_blocks( WP_Post $post ) {
		$has_attendee_list_block     = has_block( 'tribe/attendees', $post );
		$has_attendee_list_shortcode = has_shortcode( $post->post_content, 'tribe_attendees_list' );

		return $has_attendee_list_block || $has_attendee_list_shortcode;
	}

	/**
	 * @param WP_Post $post The Post being checked.
	 *
	 * @return bool
	 */
	private function is_showing_attendee_list_with_classical_editor( WP_Post $post ) {
		$is_visible_by_meta          = Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $post ) === false;
		$has_attendee_list_shortcode = has_shortcode( $post->post_content, 'tribe_attendees_list' );

		return $is_visible_by_meta || $has_attendee_list_shortcode;
	}

}
