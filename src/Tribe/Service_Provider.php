<?php

use TEC\Tickets\Commerce\Custom_Tables\V1\Provider as ET_CT1_Provider;
use TEC\Tickets\Admin\Upsell as Ticket_Upsell;

/**
 * Class Tribe__Tickets__Service_Provider
 *
 * Provides the Events Tickets Plus service.
 *
 * This class should handle implementation binding, builder functions and hooking for any first-level hook and be
 * devoid of business logic.
 *
 * @since 4.6
 */
class Tribe__Tickets__Service_Provider extends \TEC\Common\Contracts\Service_Provider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.6.4 Added `register_on_action` for `tec_events_custom_tables_v1_fully_activated` to activate ET CT1 logic when TEC CT1 is fully activated.
	 * @since 4.6
	 */
	public function register() {
		$this->container->singleton( 'tickets.assets', Tribe__Tickets__Assets::class );
		$this->container->singleton( 'tickets.handler', 'Tribe__Tickets__Tickets_Handler' );
		$this->container->singleton( 'tickets.attendees', 'Tribe__Tickets__Attendees', [ 'hook' ] );
		$this->container->singleton( 'tickets.version', 'Tribe__Tickets__Version', [ 'hook' ] );
		$this->container->singleton( 'tickets.metabox', 'Tribe__Tickets__Metabox', [ 'hook' ] );

		// Query Vars
		$this->container->singleton( 'tickets.query', 'Tribe__Tickets__Query', [ 'hook' ] );

		// Tribe Data API Init
		$this->container->singleton( 'tickets.data_api', 'Tribe__Tickets__Data_API' );

		// Ticket view handler.
		$this->container->singleton( 'tickets.tickets-view', Tribe__Tickets__Tickets_View::class );

		// View links, columns and screen options
		$this->container->singleton( 'tickets.admin.views', 'Tribe__Tickets__Admin__Views', [ 'hook' ] );
		$this->container->singleton( 'tickets.admin.columns', 'Tribe__Tickets__Admin__Columns', [ 'hook' ] );
		$this->container->singleton( 'tickets.admin.screen-options', 'Tribe__Tickets__Admin__Screen_Options', [ 'hook' ] );
		$this->container->singleton( 'tickets.admin.settings.display', 'Tribe__Tickets__Admin__Display_Settings', [ 'hook' ] );

		// Status Manager
		$this->container->singleton( 'tickets.status', 'Tribe__Tickets__Status__Manager', [ 'hook' ] );

		// Editor
		$this->container->singleton( 'tickets.editor', 'Tribe__Tickets__Editor', [ 'hook' ] );

		$this->container->singleton( 'tickets.admin.notices', 'Tribe__Tickets__Admin__Notices', [ 'hook' ] );

		// Upsell
		$this->container->singleton( Ticket_Upsell::class, Ticket_Upsell::class, [ 'hooks' ] );

		// Attendees Table
		$this->container->singleton( 'tickets.admin.attendees_table', 'Tribe__Tickets__Attendees_Table' );

		// Migration queues.
		$this->container->singleton( 'tickets.migration.queue_4_12', \Tribe\Tickets\Migration\Queue_4_12::class, [ 'hooks' ] );

		// Enabled ET CT1 when TEC CT1 is fully activated.
		$this->container->register_on_action( 'tec_events_custom_tables_v1_fully_activated', ET_CT1_Provider::class );

		$this->load();
	}

	/**
	 * Any hooking for any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 4.6
	 */
	protected function load() {
		tribe( 'tickets.query' );
		tribe( 'tickets.handler' );
		tribe( 'tickets.attendees' );
		tribe( 'tickets.version' );
		tribe( 'tickets.metabox' );
		tribe( 'tickets.status' );
		tribe( 'tickets.editor' );

		// Migration queues.
		tribe( 'tickets.migration.queue_4_12' );

		if ( is_admin() ) {
			tribe( 'tickets.admin.views' );
			tribe( 'tickets.admin.columns' );
			tribe( 'tickets.admin.screen-options' );
			tribe( 'tickets.admin.notices' );
			tribe( 'tickets.admin.settings.display' );
		}
	}
}
