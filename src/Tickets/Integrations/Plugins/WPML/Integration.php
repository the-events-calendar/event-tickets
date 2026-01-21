<?php
/**
 * WPML integration for Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\WPML
 */

namespace TEC\Tickets\Integrations\Plugins\WPML;

use TEC\Tickets\Integrations\Integration_Abstract;
use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;
use TEC\Tickets\Integrations\Plugins\WPML\Pages\Special_Page_Translator;
use TEC\Tickets\Integrations\Plugins\WPML\Meta\Meta_Sync;
use TEC\Tickets\Integrations\Plugins\WPML\Meta\Relationship_Meta_Translator;
use TEC\Tickets\Integrations\Plugins\WPML\Cart\Checkout_Cart_Fix;
use TEC\Tickets\Integrations\Plugins\WPML\Tickets\Ticket_Language_Assigner;
use TEC\Tickets\Integrations\Plugins\WPML\Tickets\Attendee_Aggregator;

/**
 * Class Integration
 *
 * Main integration entry point for WPML support in Event Tickets.
 *
 * @since TBD
 */
class Integration extends Integration_Abstract {

	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'wpml';
	}

	/**
	 * @inheritDoc
	 */
	public static function get_name(): string {
		return 'WPML';
	}

	/**
	 * @inheritDoc
	 */
	public static function get_type(): string {
		return 'plugin';
	}

	/**
	 * Check if WPML is available.
	 *
	 * @since TBD
	 *
	 * @return bool True if WPML is available, false otherwise.
	 */
	public static function is_wpml_available(): bool {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return false;
		}

		if ( ! class_exists( 'SitePress' ) ) {
			return false;
		}

		// Proxy check that WPML's translation APIs are loaded.
		if ( ! has_filter( 'wpml_object_id' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param bool $value Whether the integration should load.
	 */
	protected function filter_should_load( bool $value ): bool {
		$value = parent::filter_should_load( $value );

		if ( false === $value ) {
			return false;
		}

		return static::is_wpml_available();
	}

	/**
	 * Loads the integration.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function load(): void {
		$this->register_services();
	}

	/**
	 * Loads conditionals for the integration.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the integration should load.
	 */
	public function load_conditionals(): bool {
		return static::is_wpml_available();
	}

	/**
	 * Register hooks/services for this integration.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_services(): void {
		$this->container->singleton( Wpml_Adapter::class );

		$relationship_meta_keys = [
			'_tec_tickets_commerce_event',
			'_tribe_rsvp_for_event',
			'_tribe_wooticket_for_event',
		];

		$this->container->when( Relationship_Meta_Translator::class )
			->needs( '$meta_keys' )
			->give( $relationship_meta_keys );
		$this->container->singleton( Relationship_Meta_Translator::class );

		$this->container->singleton( Special_Page_Translator::class );
		$this->container->singleton( Attendee_Aggregator::class );
		$this->container->singleton( Ticket_Language_Assigner::class );

		$late_sync_meta_keys = [
			'_price',
			'_sku',
			'_tribe_ticket_show_description',
			'_ticket_start_date',
			'_ticket_start_time',
			'_ticket_end_date',
			'_ticket_end_time',
			'_stock',
			'_stock_status',
			'_backorders',
			'_manage_stock',
			'_sale_price_checked',
			'_sale_price_start_date',
			'_sale_price_end_date',
		];

		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		if ( isset( $tickets_handler->key_capacity ) ) {
			$late_sync_meta_keys[] = $tickets_handler->key_capacity;
		}

		$late_sync_meta_keys[] = \Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE;

		$this->container->when( Meta_Sync::class )
			->needs( '$meta_keys' )
			->give( $late_sync_meta_keys );
		$this->container->singleton( Meta_Sync::class );

		$this->container->singleton( Checkout_Cart_Fix::class );

		$services = [
			Checkout_Cart_Fix::class,
			Relationship_Meta_Translator::class,
			Special_Page_Translator::class,
			Attendee_Aggregator::class,
			Ticket_Language_Assigner::class,
			Meta_Sync::class,
		];

		foreach ( $services as $service ) {
			$this->container->get( $service )->register();
		}
	}
}
