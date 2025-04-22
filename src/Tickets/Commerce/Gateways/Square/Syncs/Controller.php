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
use TEC\Tickets\Commerce\Gateways\Square\Settings;
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
	 * The settings.
	 *
	 * @since TBD
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The container.
	 * @param Merchant  $merchant  The merchant.
	 * @param Settings  $settings  The settings.
	 */
	public function __construct( Container $container, Merchant $merchant, Settings $settings ) {
		parent::__construct( $container );
		$this->merchant = $merchant;
		$this->settings = $settings;
	}

	/**
	 * Whether the controller is active or not.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return $this->merchant->is_connected() && $this->settings->is_inventory_sync_enabled();
	}

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->singleton( Remote_Objects::class );
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
