<?php

namespace TEC\Tickets\Commerce\RSVP;

use \TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\QR
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
		//$this->container->singleton( Settings::class );

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
	 * Adds the actions required by the controller.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'add_meta_boxes', [ $this, 'configure' ] );
	}

	/**
	 * Removes the actions required by the controller.
	 *
	 * @since TBD
	 */
	protected function remove_actions() {

	}

	public function configure( $post_type = null ) {
		$this->container->make( Metabox::class )->configure( $post_type );
	}

}
