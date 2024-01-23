<?php
/**
 * Controls the basic, common, features of the Flexible Tickets project.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use TEC\Events_Pro\Custom_Tables\V1\Series\Provider as Series_Provider;

/**
 * Class Base.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Base extends Controller {

	/**
	 * Registers the controller services and implementations.
	 *
	 * @since 5.8.0
	 */
	protected function do_register(): void {
		$this->container->singleton( Repositories\Ticket_Groups::class, Repositories\Ticket_Groups::class );
		$this->container->singleton( Repositories\Posts_And_Ticket_Groups::class, Repositories\Posts_And_Ticket_Groups::class );

		tribe_asset(
			tribe( 'tickets.main' ),
			'tec-tickets-flexible-tickets-style',
			'flexible-tickets.css',
			[],
			null,
			[
				'groups' => [
					'flexible-tickets',
				],
			],
		);

		// Remove the filter that would prevent Series from appearing among the ticket-able post types.
		$series_provider = $this->container->get( Series_Provider::class );
		remove_action( 'init', [ $series_provider, 'remove_series_from_ticketable_post_types' ] );

		// Remove the filter that would prevent Series from being ticket-able in CT1.
		remove_filter( 'tribe_tickets_settings_post_types', [
			$series_provider,
			'filter_remove_series_post_type'
		] );

		$this->handle_first_activation();
	}

	/**
	 * Unregisters the controller services and implementations.
	 *
	 * @since 5.8.0
	 */
	public function unregister(): void {
		// Restore the filter that would prevent Series from appearing among the ticket-able post types.
		$series_provider = $this->container->get( Series_Provider::class );
		if ( ! has_action( 'init', [ $series_provider, 'remove_series_from_ticketable_post_types' ] ) ) {
			add_action( 'init', [ $series_provider, 'remove_series_from_ticketable_post_types' ] );
		}

		// Restore the filter that would prevent Series from being ticket-able in CT1.
		add_filter( 'tribe_tickets_settings_post_types', [
			$series_provider,
			'filter_remove_series_post_type'
		] );
	}

	/**
	 * If the Flexible Tickets feature has never been activated, then make Series ticketable by default.
	 *
	 * @since 5.8.0
	 *
	 * @return void On first activation, Series are made ticketable by default.
	 */
	private function handle_first_activation(): void {
		$is_first_activation = tribe_get_option( 'flexible_tickets_activated', false ) === false;

		if ( ! $is_first_activation ) {
			return;
		}

		tribe_update_option( 'flexible_tickets_activated', true );
		$ticketable   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = Series::POSTTYPE;
		tribe_update_option(
			'ticket-enabled-post-types',
			array_values( array_unique( $ticketable ) )
		);
	}
}