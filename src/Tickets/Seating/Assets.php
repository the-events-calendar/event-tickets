<?php
/**
 * Handles the reegistration of assets common to all the Tickets Seating modules.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Asset;
use TEC\Tickets\Seating\Admin\Events\Associated_Events;
use TEC\Tickets\Seating\Admin\Maps_Layouts_Home_Page;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Orders\Seats_Report;
use Tribe__Tickets__Main as ET;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Assets.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating;
 */
class Assets extends Controller_Contract {
	/**
	 * Unregisters the controller by unsubscribing from WordPress hooks.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
	}

	/**
	 * Returns the utils data for the Seating feature.
	 *
	 * @since 5.16.0
	 *
	 * @return array{
	 *     links: array<string, string>,
	 *     localizedStrings: array<string, string>,
	 * } The utils data for the Seating feature.
	 */
	public function get_utils_data(): array {
		$localization = $this->container->get( Localization::class );
		$post         = get_post();

		return [
			'links'            => [
				'layouts'     => $this->container->get( Layouts::class )->get_url(),
				'layout-edit' => empty( $post ) ? '' : Seats_Report::get_link( $post ),
			],
			'localizedStrings' => [
				'capacity-form'  => $localization->get_capacity_form_strings(),
				'capacity-table' => [ 'seats-row-label' => _x( 'Assigned Seating', 'Capacity table row label for assigned seating tickets', 'event-tickets' ) ],
				'dashboard'      => [ 'seats-action-label' => _x( 'Seats', 'Ticket Dashboard actions', 'event-tickets' ) ],
				'maps'           => [
					'delete-confirmation' => _x( 'Are you sure you want to delete this map?', 'Confirmation message for deleting a map', 'event-tickets' ),
					'delete-failed'       => _x( 'Failed to delete the map.', 'Error message for deleting a map', 'event-tickets' ),
				],
				'layouts'        => [
					'add-failed'          => _x( 'Failed to add the new layout.', 'Error message for adding a layout', 'event-tickets' ),
					'edit-confirmation'   => _x( 'This layout is associated with {count} events. Changes will impact all existing events and may affect the seating assignment of active ticket holders.', 'Confirmation message for editing a layout with events', 'event-tickets' ),
					'duplicate-failed'    => _x( 'Failed to duplicate this layout.', 'Error message for duplicating a layout', 'event-tickets' ),
					'delete-confirmation' => _x( 'Are you sure you want to delete this layout? This cannot be undone.', 'Confirmation message for deleting a layout', 'event-tickets' ),
					'delete-failed'       => _x( 'Failed to delete the layout.', 'Error message for deleting a layout', 'event-tickets' ),
				],
				'service-errors' => $localization->get_service_error_strings(),
			],
		];
	}

	/**
	 * Gets the data for the currency asset.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
	 *
	 * @return void The utils asset is registered.
	 */
	private function register_utils_asset(): void {
		Asset::add(
			'tec-tickets-seating-utils',
			'utils.js',
			ET::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_localize_script( 'tec.tickets.seating.utils', [ $this, 'get_utils_data' ] )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Gets the data for the service bundle asset.
	 *
	 * @since 5.16.0
	 *
	 * @return array{
	 *     service: array{
	 *         baseUrl: string,
	 *         mapsHomeUrl: string,
	 *         layoutsHomeUrl: string,
	 *         associatedEventsUrl: string
	 *     }
	 * } The data for the service bundle asset.
	 */
	public function get_service_bundle_data(): array {
		$maps_layouts_home_page = $this->container->get( Maps_Layouts_Home_Page::class );

		return [
			'service' => [
				'baseUrl'             => $this->container->get( Service\Service::class )->get_frontend_url(),
				'mapsHomeUrl'         => $maps_layouts_home_page->get_maps_home_url(),
				'layoutsHomeUrl'      => $maps_layouts_home_page->get_layouts_home_url(),
				'associatedEventsUrl' => add_query_arg( [ 'page' => Associated_Events::SLUG ], admin_url( 'admin.php' ) ),
			],
		];
	}

	/**
	 * Registers the service bundle, used to communicate with the Service.
	 *
	 * @since 5.16.0
	 *
	 * @return void The service bundle script and styles are registered.
	 */
	private function register_service_bundle(): void {
		Asset::add(
			'tec-tickets-seating-service-bundle',
			'service.js',
			ET::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->set_dependencies(
				'tec-tickets-vendor-babel',
				'wp-i18n',
				'tec-tickets-seating-utils',
				'tec-tickets-seating-ajax'
			)
			->add_to_group( 'tec-tickets-seating' )
			->add_localize_script( 'tec.tickets.seating', [ $this, 'get_service_bundle_data' ] )
			->register();
	}

	/**
	 * Registers the currency asset, used to format currency values.
	 *
	 * @since 5.16.0
	 *
	 * @return void The currency asset is registered.
	 */
	private function register_currency_asset(): void {
		Asset::add(
			'tec-tickets-seating-currency',
			'currency.js',
			ET::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_localize_script( 'tec.tickets.seating.currency', [ $this, 'get_currency_data' ] )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}
}
