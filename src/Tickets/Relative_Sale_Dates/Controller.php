<?php
/**
 * Relative Sale Dates controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Registers the Relative Sale Dates feature and holds the switch that turns all of it off.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Controller extends Controller_Contract {
	/**
	 * The name of the constant, and of the environment variable, that disables the feature when truthy.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_RELATIVE_SALE_DATES_DISABLED';

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_pre_save', $this->container->callback( Ticket_Save::class, 'set_ticket_dates' ) );
		remove_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'save_rule' ) );
		remove_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'write_resolved_dates' ), 20 );
		remove_filter( 'tec_tickets_ticket_data_validation', $this->container->callback( Ticket_Save::class, 'validate_ticket_data' ) );
		remove_action( 'tec_events_custom_tables_v1_after_save_occurrences', $this->container->callback( Event_Listener::class, 'update_ticket_dates' ) );
		remove_filter( 'tec_tickets_ticket_end_date_follows_event_start', $this->container->callback( Event_Listener::class, 'filter_end_date_follows_event_start' ) );
		remove_action( 'tec_tickets_tickets_duplicated', $this->container->callback( Event_Listener::class, 'copy_rules_to_duplicates' ) );
		remove_action( 'tec_tickets_tickets_duplicated', $this->container->callback( Event_Listener::class, 'update_duplicated_tickets' ), 20 );
		remove_filter( 'tec_tickets_rest_single_ticket_add_data', $this->container->callback( Rest::class, 'map_block_editor_rule' ) );
		remove_filter( 'tribe_tickets_rest_api_ticket_data', $this->container->callback( Rest::class, 'add_rule_to_block_editor_ticket_data' ) );
		remove_filter( 'tec_rest_swagger_ticket_request_body_definition', $this->container->callback( Rest::class, 'add_rule_to_definition' ) );
		remove_filter( 'tec_rest_swagger_ticket_definition', $this->container->callback( Rest::class, 'add_rule_to_definition' ) );
		remove_filter( 'tec_rest_schema_filter', $this->container->callback( Rest::class, 'keep_a_rule_sent_as_null' ) );
		remove_filter( 'tec_rest_v1_tec_tc_ticket_transform_entity', $this->container->callback( Rest::class, 'add_rule_to_tec_rest_api_ticket' ) );
		$this->container->get( Classic_Panel_Data::class )->unregister();
	}

	/**
	 * Determines whether the feature is active.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the feature is active.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			return false;
		}

		/**
		 * Filters whether the Relative Sale Dates feature is active.
		 *
		 * Only applies when neither the disabling constant nor the environment variable is set. It is read while the
		 * plugins load, so add it from a plugin or a must-use plugin: one added in a theme's `functions.php` comes too
		 * late and is ignored.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether the feature is active. Default `true`.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_relative_sale_dates_active', true ) );
	}

	/**
	 * Registers the controller.
	 *
	 * `Ticket_Save` is bound as a singleton so the container returns the same callbacks to `unregister()`.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		// The tickets handler is bound only by name: autowiring its class would build a second one, which hooks again.
		$this->container->singleton( Rule_Store::class, static fn() => new Rule_Store( tribe( 'tickets.handler' ) ) );
		$this->container->singleton( Ticket_Dates::class );
		$this->container->singleton( Ticket_Save::class );

		add_action( 'tec_tickets_ticket_pre_save', $this->container->callback( Ticket_Save::class, 'set_ticket_dates' ), 10, 3 );
		add_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'save_rule' ), 10, 3 );
		// After the rule is stored, and before Ticket_Actions schedules the sales actions from the ticket dates, at 1000.
		add_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'write_resolved_dates' ), 20, 2 );
		add_filter( 'tec_tickets_ticket_data_validation', $this->container->callback( Ticket_Save::class, 'validate_ticket_data' ), 10, 3 );

		$this->container->singleton( Event_Listener::class );

		add_action( 'tec_events_custom_tables_v1_after_save_occurrences', $this->container->callback( Event_Listener::class, 'update_ticket_dates' ) );
		add_filter( 'tec_tickets_ticket_end_date_follows_event_start', $this->container->callback( Event_Listener::class, 'filter_end_date_follows_event_start' ), 10, 2 );
		add_action( 'tec_tickets_tickets_duplicated', $this->container->callback( Event_Listener::class, 'copy_rules_to_duplicates' ) );
		// After the rules are copied to the duplicates.
		add_action( 'tec_tickets_tickets_duplicated', $this->container->callback( Event_Listener::class, 'update_duplicated_tickets' ), 20, 2 );

		$this->container->singleton( Rest::class );

		add_filter( 'tec_tickets_rest_single_ticket_add_data', $this->container->callback( Rest::class, 'map_block_editor_rule' ), 10, 3 );
		add_filter( 'tribe_tickets_rest_api_ticket_data', $this->container->callback( Rest::class, 'add_rule_to_block_editor_ticket_data' ) );
		add_filter( 'tec_rest_swagger_ticket_request_body_definition', $this->container->callback( Rest::class, 'add_rule_to_definition' ) );
		add_filter( 'tec_rest_swagger_ticket_definition', $this->container->callback( Rest::class, 'add_rule_to_definition' ) );
		add_filter( 'tec_rest_schema_filter', $this->container->callback( Rest::class, 'keep_a_rule_sent_as_null' ), 10, 3 );
		add_filter( 'tec_rest_v1_tec_tc_ticket_transform_entity', $this->container->callback( Rest::class, 'add_rule_to_tec_rest_api_ticket' ) );

		$this->container->register( Classic_Panel_Data::class );
	}
}
