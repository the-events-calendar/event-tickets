<?php
/**
 * Handles registering and setup for RSVP in Tickets Commerce.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */

namespace TEC\Tickets\Commerce\RSVP;

use TEC\Tickets\Commerce\REST\Ticket_Endpoint;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\RSVP\REST\Order_Endpoint;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */
class Controller extends Controller_Contract {
	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail if method belongs to the parent/abstract class.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the controller is active or not.
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Register the controller.
	 *
	 * @since   TBD
	 *
	 * @uses  Notices::register_admin_notices()
	 */
	public function do_register(): void {
		$this->container->singleton( Ticket_Endpoint::class );
		$this->container->singleton( REST\Order_Endpoint::class );

		$this->register_assets();
		$this->add_actions();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		$this->remove_actions();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider
	 *
	 * @since TBD
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Adds the actions required by the controller.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'add_meta_boxes', [ $this, 'configure' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Removes the actions required by the controller.
	 *
	 * @since TBD
	 */
	protected function remove_actions() {

	}

	/**
	 * Configures the RSVP metabox for the given post type.
	 *
	 * @since TBD
	 *
	 * @param string|null $post_type The post type to configure the metabox for.
	 */
	public function configure( $post_type = null ) {
		$this->container->make( Metabox::class )->configure( $post_type );
	}

	/**
	 * Register the REST API endpoints.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->container->make( Ticket_Endpoint::class )->register();
		$this->container->make( Order_Endpoint::class )->register();
	}
}
