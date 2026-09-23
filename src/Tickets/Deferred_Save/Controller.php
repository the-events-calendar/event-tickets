<?php
/**
 * Registers the Deferred Ticket Save feature and decides which posts use it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Event;

/**
 * Class Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the save entry points.
	 *
	 * The container already holds this controller as a singleton once it is registered.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->register( Classic_Save::class );
		$this->container->register( Block_Save::class );
		$this->container->register( Classic\Editor::class );
		$this->container->register( Classic\Notices::class );
		$this->container->register( Classic\Assets::class );
		$this->container->register( Block\Editor_Config::class );
	}

	/**
	 * Unregisters the feature.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Classic_Save::class )->unregister();
		$this->container->get( Block_Save::class )->unregister();
		$this->container->get( Classic\Editor::class )->unregister();
		$this->container->get( Classic\Notices::class )->unregister();
		$this->container->get( Classic\Assets::class )->unregister();
		$this->container->get( Block\Editor_Config::class )->unregister();
	}

	/**
	 * Whether ticket changes for a post are deferred to the post save.
	 *
	 * By default only a recurring event defers ticket writes, and only while Events
	 * Calendar Pro provides recurrence. Every other post keeps today's behavior.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post being saved. An occurrence ID is normalized to its event.
	 *
	 * @return bool Whether the post uses deferred ticket save.
	 */
	public function uses_deferred_save( int $post_id ): bool {
		$post_id = (int) Event::filter_event_id( $post_id, 'deferred_save' );

		// The function is ECP's; it is false for any post that is not a `tribe_events` post, unknown IDs included.
		$enabled = function_exists( 'tribe_is_recurring_event' ) && tribe_is_recurring_event( $post_id );

		/**
		 * Filters whether ticket changes for a post are deferred to the post save.
		 *
		 * Returning `false` for every post switches the Deferred Ticket Save feature off.
		 *
		 * @since TBD
		 *
		 * @param bool $enabled Whether the post uses deferred ticket save. By default only recurring events do.
		 * @param int  $post_id The ID of the post being saved, normalized to the event post ID.
		 */
		return (bool) apply_filters( 'tec_tickets_deferred_save_enabled', $enabled, $post_id );
	}
}
