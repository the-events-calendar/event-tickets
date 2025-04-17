<?php
/**
 * Controller for Square syncs.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Controller extends Controller_Contract {
	/**
	 * The merchant.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The container.
	 * @param Merchant  $merchant  The merchant.
	 */
	public function __construct( Container $container, Merchant $merchant ) {
		parent::__construct( $container );
		$this->merchant = $merchant;
	}

	/**
	 * Whether the controller is active or not.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return $this->merchant->is_connected();
	}

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->register( Tickets_Sync::class );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Tickets_Sync::class )->unregister();
	}
}
