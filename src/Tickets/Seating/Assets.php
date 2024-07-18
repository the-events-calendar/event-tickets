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
use TEC\Tickets\Seating\Admin\Ajax;
use TEC\Tickets\Seating\Admin\Maps_Layouts_Home_Page;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use Tribe__Tickets__Main as ET;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Assets.
 *
 * @since TBD
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
				'layouts'     => $this->container->get( Layouts::class )->get_url(),
				'layout-edit' => Layout_Edit::get_edit_url_by_post( get_the_ID() ),
			],
			'localizedStrings' => [
				'capacity-form' => $this->container->get( Localization::class )->get_capacity_form_strings(),
				'dashboard'     => [ 'seats-action-label' => _x( 'Seats', 'Ticket Dashboard actions', 'event-tickets' ) ],
				'maps'          => [
					'delete-confirmation' => _x( 'Are you sure you want to delete this map?', 'Confirmation message for deleting a map', 'event-tickets' ),
					'delete-failed'       => _x( 'Failed to delete the map.', 'Error message for deleting a map', 'event-tickets' ),
				],
				'layouts'       => [
					'delete-confirmation' => _x( 'Are you sure you want to delete this layout?', 'Confirmation message for deleting a layout', 'event-tickets' ),
					'delete-failed'       => _x( 'Failed to delete the layout.', 'Error message for deleting a layout', 'event-tickets' ),
					'edit-confirmation'   => _x( 'This layout is associated with {count} events. Changes will impact all existing events and may affect the seating assignment of active ticket holders.', 'Confirmation message for editing a layout with events', 'event-tickets' ),
				],
			],
		];
	}

	/**
	 * Gets the data for the currency asset.
	 *
	 * @since TBD
	 *
	 * @return array{
	 *     symbol: string,
	 *     position: string,
	 *     decimalSeparator: string,
	 *     decimalNumbers: int,
	 *     thousandSeparator: string
	 * } The data for the currency asset.
	 */
	public function get_currency_data(): array {
		$post_id = get_the_ID();

		$provider = Tickets::get_event_ticket_provider_object( $post_id );
		/** @var \Tribe__Tickets__Commerce__Currency $currency */
		$currency = tribe( 'tickets.commerce.currency' );

		return [
			'symbol'            => html_entity_decode( $currency->get_provider_symbol( $provider, $post_id ) ),
			'position'          => $currency->get_provider_symbol_position( $provider, $post_id ),
			'decimalSeparator'  => $currency->get_currency_decimal_point( $provider ),
			'decimalNumbers'    => $currency->get_currency_number_of_decimals(),
			'thousandSeparator' => $currency->get_currency_thousands_sep( $provider ),
		];
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
		$this->register_currency_asset();
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
			ET::VERSION
		)
			->add_localize_script( 'tec.tickets.seating.utils', [ $this, 'get_utils_data' ] )
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
		$data = function () {
			$maps_layouts_home_page = $this->container->get( Maps_Layouts_Home_Page::class );

			return [
				'service'          => [
					'baseUrl'        => $this->container->get( Service\Service::class )->get_frontend_url(),
					'mapsHomeUrl'    => $maps_layouts_home_page->get_maps_home_url(),
					'layoutsHomeUrl' => $maps_layouts_home_page->get_layouts_home_url(),
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'ajaxNonce'      => wp_create_nonce( Ajax::NONCE_ACTION ),
				],
				'localizedStrings' => [
					'service-errors' => $this->container->get( Localization::class )->get_service_error_strings(),
				],
			];
		};

		Asset::add(
			'tec-tickets-seating-service-bundle',
			$this->built_asset_url( 'service.js' ),
			ET::VERSION
		)
			->set_dependencies(
				'wp-i18n',
				'tribe-tickets-gutenberg-vendor', // Not actually about Block Editor, but transpiling.
				'tec-tickets-seating-utils'
			)
			->add_to_group( 'tec-tickets-seating' )
			->add_localize_script( 'tec.tickets.seating', $data )
			->register();
	}

	/**
	 * Registers the currency asset, used to format currency values.
	 *
	 * @since TBD
	 *
	 * @return void The currency asset is registered.
	 */
	private function register_currency_asset(): void {
		Asset::add(
			'tec-tickets-seating-currency',
			$this->built_asset_url( 'currency.js' ),
			ET::VERSION
		)
			->add_localize_script( 'tec.tickets.seating.currency', [ $this, 'get_currency_data' ] )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}
}
