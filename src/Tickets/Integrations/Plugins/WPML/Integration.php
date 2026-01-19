<?php
/**
 * WPML integration for Event Tickets.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations\WPML
 */

namespace TEC\Tickets\Integrations\Plugins\WPML;

use TEC\Tickets\Integrations\Integration_Abstract;

/**
 * Class Integration
 *
 * Main integration entry point for WPML support in Event Tickets.
 * Handles service registration and lifecycle management.
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
	 * Check if WPML is available and properly configured.
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

		if ( ! has_filter( 'wpml_object_id' ) ) {
			return false;
		}

		// Meta_Sync relies on wpml_sync_custom_field action.
		if ( ! has_action( 'wpml_sync_custom_field' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
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
	 * This is the primary hook-in point required by the Common integration system.
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
	 * Keep this minimal: if some hooks should only load in admin or frontend, add them here.
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
		// Register WPML adapter first as a singleton so other services can depend on it.
		$this->container->singleton( Wpml_Adapter::class );

		$relationship_meta_keys = [
			'_tec_tickets_commerce_event',
			'_tribe_rsvp_for_event',
			'_tribe_wooticket_for_event',
		];

		// Register services in the container for proper DI.
		// The container will automatically inject Wpml_Adapter into constructors.
		$this->container->singleton( Relationship_Meta_Translator::class );
		$this->container->when( Relationship_Meta_Translator::class )
			->needs( '$meta_keys' )
			->give( $relationship_meta_keys );

		$this->container->singleton( Special_Page_Translator::class );
		$this->container->singleton( Attendee_Aggregator::class );
		$this->container->singleton( Ticket_Language_Assigner::class );

		// Meta keys that are updated AFTER wp_update_post() (line 615 in Ticket.php).
		// These need our updated_postmeta hook because WPML syncs during after_save_post
		// which fires before these meta updates complete.
		$late_sync_meta_keys = [
			'_price',                                    // Line 636
			'_sku',                                      // Line 667
			'_tribe_ticket_show_description',            // Line 627
			'_ticket_start_date',                        // Line 644
			'_ticket_start_time',                        // Line 644
			'_ticket_end_date',                          // Line 644
			'_ticket_end_time',                          // Line 644
			'_stock',                                    // Line 768
			'_stock_status',                             // Line 769
			'_backorders',                               // Line 770
			'_manage_stock',                             // Line 771
			'_sale_price_checked',                       // Line 1179
			'_sale_price',                               // Line 1194
			'_sale_price_start_date',                    // Line 1213
			'_sale_price_end_date',                      // Line 1219
		];

		// Add capacity key dynamically (stored in Tickets_Handler).
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		if ( isset( $tickets_handler->key_capacity ) ) {
			$late_sync_meta_keys[] = $tickets_handler->key_capacity; // Line 799
		}

		// Add global stock mode key.
		$late_sync_meta_keys[] = \Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE; // Line 767

		// Register Meta_Sync service with only late-write keys.
		$this->container->singleton( Meta_Sync::class );
		$this->container->when( Meta_Sync::class )
			->needs( '$meta_keys' )
			->give( $late_sync_meta_keys );

		// Register checkout cart fix to handle language context issues.
		$this->container->singleton( Checkout_Cart_Fix::class );
		$this->container->get( Checkout_Cart_Fix::class )->register();

		// Register string registrar to ensure translatable strings are registered with WPML.
		$this->container->singleton( String_Registrar::class );
		$this->container->get( String_Registrar::class )->register();

		// Register hooks for each service.
		$this->container->get( Relationship_Meta_Translator::class )->register();
		$this->container->get( Special_Page_Translator::class )->register();
		$this->container->get( Attendee_Aggregator::class )->register();
		$this->container->get( Ticket_Language_Assigner::class )->register();
		$this->container->get( Meta_Sync::class )->register();
	}
}
