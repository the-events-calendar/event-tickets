<?php
/**
 * Registers the Deferred Ticket Save feature.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Main as Tickets_Main;

/**
 * Class Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
final class Controller extends Controller_Contract {
	/**
	 * The name of the constant, and of the environment variable, that switches the feature off.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const DISABLED = 'TEC_TICKETS_DEFERRED_SAVE_DISABLED';

	/**
	 * Whether the feature is active.
	 *
	 * Ticket changes are deferred to the post save on every ticketable post while the feature is
	 * active. It is active unless the constant, the environment variable or the filter switch it off.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the feature is active.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			// The constant to disable the feature is defined and it's truthy.
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			// The environment variable to disable the feature is truthy.
			return false;
		}

		/**
		 * Filters whether the Deferred Ticket Save feature is active.
		 *
		 * Note: this filter will only apply if the disable constant or env var
		 * are not set or are set to falsy values.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether the feature is active. Defaults to `true`.
		 */
		return (bool) apply_filters( 'tec_tickets_deferred_save_active', true );
	}

	/**
	 * Hooks the save entry points.
	 *
	 * The classic save hooks the generic `save_post`, with the post type checked when it fires, so a
	 * post type made ticketable after this registered is still covered. It is bound as a singleton so
	 * the container returns the same callback to `unregister()`.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Classic_Save::class );

		add_action( 'save_post', $this->container->callback( Classic_Save::class, 'on_save_post' ), Classic_Save::PRIORITY, 2 );

		$this->container->singleton( Block_Save::class );

		foreach ( Tickets_Main::instance()->post_types() as $post_type ) {
			add_action( "rest_after_insert_{$post_type}", $this->container->callback( Block_Save::class, 'on_rest_after_insert' ), Block_Save::PRIORITY, 3 );
			add_filter( "rest_prepare_{$post_type}", $this->container->callback( Block_Save::class, 'add_result_to_response' ), 10, 3 );
		}

		$this->container->register( Classic\Editor::class );
		$this->container->register( Classic\Notices::class );
	}

	/**
	 * Unhooks the save entry points.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'save_post', $this->container->callback( Classic_Save::class, 'on_save_post' ), Classic_Save::PRIORITY );

		foreach ( Tickets_Main::instance()->post_types() as $post_type ) {
			remove_action( "rest_after_insert_{$post_type}", $this->container->callback( Block_Save::class, 'on_rest_after_insert' ), Block_Save::PRIORITY );
			remove_filter( "rest_prepare_{$post_type}", $this->container->callback( Block_Save::class, 'add_result_to_response' ), 10 );
		}

		$this->container->get( Classic\Editor::class )->unregister();
		$this->container->get( Classic\Notices::class )->unregister();
	}
}
