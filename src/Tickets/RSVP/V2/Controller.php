<?php
/**
 * V2 RSVP Controller - TC-based implementation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Controller extends Controller_Contract {

	/**
	 * Store hook callbacks for clean unregistration.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected array $hooks = [];

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Constants::class );

		// Register assets.
		$this->register_assets();

		// Add actions and filters.
		$this->add_actions();
		$this->add_filters();

		/**
		 * Fires after the RSVP V2 controller has been registered.
		 *
		 * This action allows other plugins (e.g., ET+) to register their
		 * own V2 RSVP components after the core V2 infrastructure is ready.
		 *
		 * @since TBD
		 */
		do_action( 'tec_tickets_rsvp_v2_registered' );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
		$this->remove_filters();
	}

	/**
	 * Register V2 assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_assets(): void {
		$this->container->register( Assets::class );
	}

	/**
	 * Add actions.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function add_actions(): void {
		// Will be implemented when porting full Controller.
	}

	/**
	 * Remove actions.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function remove_actions(): void {
		foreach ( $this->hooks as $key => $hook ) {
			if ( 'action' === $hook['type'] ) {
				remove_action( $hook['tag'], $hook['callback'], $hook['priority'] );
				unset( $this->hooks[ $key ] );
			}
		}
	}

	/**
	 * Add filters.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function add_filters(): void {
		// Will be implemented when porting full Controller.
	}

	/**
	 * Remove filters.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function remove_filters(): void {
		foreach ( $this->hooks as $key => $hook ) {
			if ( 'filter' === $hook['type'] ) {
				remove_filter( $hook['tag'], $hook['callback'], $hook['priority'] );
				unset( $this->hooks[ $key ] );
			}
		}
	}

	/**
	 * Add a hook and track it for unregistration.
	 *
	 * @since TBD
	 *
	 * @param string   $type     'action' or 'filter'.
	 * @param string   $tag      The hook tag.
	 * @param callable $callback The callback.
	 * @param int      $priority The priority.
	 * @param int      $args     Number of arguments.
	 *
	 * @return void
	 */
	protected function add_hook( string $type, string $tag, callable $callback, int $priority = 10, int $args = 1 ): void {
		if ( 'action' === $type ) {
			add_action( $tag, $callback, $priority, $args );
		} else {
			add_filter( $tag, $callback, $priority, $args );
		}

		$this->hooks[] = [
			'type'     => $type,
			'tag'      => $tag,
			'callback' => $callback,
			'priority' => $priority,
		];
	}
}
