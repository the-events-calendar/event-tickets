<?php
/**
 * Handles the reegistration of assets common to all the Tickets Seating modules.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Assets.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating;
 */
class Assets extends Controller_Contract {
	use Built_Assets;

	/**
	 * Unregisters the controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_utils_asset();
		$this->register_service_bundle();

	}

	/**
	 * Returns the utils data for the Seating feature.
	 *
	 * @since TBD
	 *
	 * @return array{
	 *     links: array<string, string>,
	 *     localizedStrings: array<string, string>,
	 * } The utils data for the Seating feature.
	 */
	public function get_utils_data(): array {
		return [
			'links'            => [
				'layouts' => $this->container->get( Layouts::class )->get_url(),
			],
			'localizedStrings' => [
				'capacity-form' => $this->container->get( Localization::class )->get_capacity_form_strings(),
			],
		];
	}

	/**
	 * Registers the utils asset.
	 *
	 * @since TBD
	 *
	 * @return void The utils asset is registered.
	 */
	private function register_utils_asset(): void {
		Asset::add(
			'tec-tickets-seating-utils',
			$this->built_asset_url( 'utils.js' ),
			Tickets::VERSION
		)
			->add_localize_script( 'tec.seating.utils', [ $this, 'get_utils_data' ] )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Registers the service bundle, used to communicate with the Service.
	 *
	 * @since TBD
	 *
	 * @return void The service bundle script and styles are registered.
	 */
	private function register_service_bundle(): void {
		$data = fn() => [
			'service'          => [
				'baseUrl' => $this->container->get( Service\Service::class )->get_frontend_url(),
			],
			'localizedStrings' => [
				'service-errors' => $this->container->get( Localization::class )->get_service_error_strings(),
			]
		];

		Asset::add(
			'tec-tickets-seating-service-bundle',
			$this->built_asset_url( 'service.js' ),
			Tickets::VERSION
		)
			->set_dependencies(
				'wp-i18n',
				'tribe-tickets-gutenberg-vendor', // Not actually about Block Editor, but transpiling.
				'tec-tickets-seating-utils'
			)
			->add_to_group( 'tec-tickets-seating' )
			->add_localize_script( 'tec.seating', $data )
			->register();
	}
}