<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

class Controller_Test extends Controller_Test_Case {
	use With_Uopz;

	protected $controller_class = Controller::class;

	/**
	 * @after
	 */
	public function unset_disabled_env_var(): void {
		putenv( Controller::DISABLED );
	}

	/**
	 * @test
	 */
	public function should_be_active_by_default(): void {
		$controller = $this->make_controller();

		$this->assertTrue( $controller->is_active() );
	}

	/**
	 * @test
	 */
	public function should_not_be_active_when_the_constant_is_set(): void {
		$this->set_const_value( Controller::DISABLED, true );

		$controller = $this->make_controller();

		$this->assertFalse( $controller->is_active() );
	}

	/**
	 * @test
	 */
	public function should_be_active_when_the_constant_is_falsy(): void {
		$this->set_const_value( Controller::DISABLED, false );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->is_active() );
	}

	/**
	 * @test
	 */
	public function should_not_be_active_when_the_env_var_is_set(): void {
		putenv( Controller::DISABLED . '=1' );

		$controller = $this->make_controller();

		$this->assertFalse( $controller->is_active() );
	}

	/**
	 * @test
	 */
	public function should_not_be_active_when_the_filter_returns_false(): void {
		add_filter( 'tec_tickets_relative_sale_dates_active', '__return_false' );

		$controller = $this->make_controller();

		$this->assertFalse( $controller->is_active() );
	}

	/**
	 * @test
	 */
	public function should_not_register_when_disabled(): void {
		add_filter( 'tec_tickets_relative_sale_dates_active', '__return_false' );

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $controller::is_registered() );
	}

	/**
	 * The Tickets provider registers the controller on `tec_events_fully_loaded`: the rules resolve against event
	 * dates, so the feature exists only alongside The Events Calendar.
	 *
	 * @test
	 */
	public function should_register_only_once_the_events_calendar_has_fully_loaded(): void {
		global $wp_actions, $wp_filter;
		$fired = $wp_actions['tec_events_fully_loaded'] ?? 0;
		unset( $wp_actions['tec_events_fully_loaded'] );
		$callbacks_before = isset( $wp_filter['tec_events_fully_loaded'] ) ? $wp_filter['tec_events_fully_loaded']->callbacks : [];

		$this->test_services->register_on_action( 'tec_events_fully_loaded', Controller::class );

		$this->assertFalse( Controller::is_registered() );

		// Run only the registration the call added, not every callback of the action.
		foreach ( $wp_filter['tec_events_fully_loaded']->callbacks as $priority => $callbacks ) {
			foreach ( array_diff_key( $callbacks, $callbacks_before[ $priority ] ?? [] ) as $callback ) {
				call_user_func( $callback['function'] );
			}
		}
		$wp_actions['tec_events_fully_loaded'] = $fired;

		$this->assertTrue( Controller::is_registered() );

		$this->test_services->get( Controller::class )->unregister();
	}

	/**
	 * @return Generator<string,array{0: string, 1: string, 2: string, 3: int}>
	 */
	public function hook_provider(): Generator {
		yield 'ticket dates set before the save' => [ 'tec_tickets_ticket_pre_save', Ticket_Save::class, 'set_ticket_dates', 10 ];
		yield 'rule stored after the save' => [ 'tec_tickets_ticket_upserted', Ticket_Save::class, 'save_rule', 10 ];
		yield 'resolved dates written after the save' => [ 'tec_tickets_ticket_upserted', Ticket_Save::class, 'write_resolved_dates', 20 ];
		yield 'ticket data validated' => [ 'tec_tickets_ticket_data_validation', Ticket_Save::class, 'validate_ticket_data', 10 ];
		yield 'tickets updated once the event occurrences are saved' => [ 'tec_events_custom_tables_v1_after_save_occurrences', Event_Listener::class, 'update_ticket_dates', 10 ];
		yield 'event marked when a date meta is added' => [ 'added_post_meta', Event_Listener::class, 'mark_moved_event', 10 ];
		yield 'event marked when a date meta is updated' => [ 'updated_postmeta', Event_Listener::class, 'mark_moved_event', 10 ];
		yield 'tickets updated once the event save is done' => [ 'wp_after_insert_post', Event_Listener::class, 'update_saved_event_tickets', 10 ];
		yield 'tickets updated on shutdown' => [ 'tec_shutdown', Event_Listener::class, 'update_moved_event_tickets', 10 ];
		yield 'moved ticket updated' => [ 'tribe_tickets_ticket_type_moved', Event_Listener::class, 'update_moved_ticket', 10 ];
		yield 'legacy end date sync filtered' => [ 'tec_tickets_ticket_end_date_follows_event_start', Event_Listener::class, 'filter_end_date_follows_event_start', 10 ];
		yield 'rules copied to duplicates' => [ 'tec_tickets_tickets_duplicated', Event_Listener::class, 'copy_rules_to_duplicates', 10 ];
		yield 'duplicated tickets updated' => [ 'tec_tickets_tickets_duplicated', Event_Listener::class, 'update_duplicated_tickets', 20 ];
		yield 'block editor rule mapped' => [ 'tec_tickets_rest_single_ticket_add_data', Rest::class, 'map_block_editor_rule', 10 ];
		yield 'rule added to the block editor ticket' => [ 'tribe_tickets_rest_api_ticket_data', Rest::class, 'add_rule_to_block_editor_ticket_data', 10 ];
		yield 'rule added to the request body definition' => [ 'tec_rest_swagger_ticket_request_body_definition', Rest::class, 'add_rule_to_definition', 10 ];
		yield 'rule added to the ticket definition' => [ 'tec_rest_swagger_ticket_definition', Rest::class, 'add_rule_to_definition', 10 ];
		yield 'rule sent as null kept' => [ 'tec_rest_schema_filter', Rest::class, 'keep_a_rule_sent_as_null', 10 ];
		yield 'rule added to the TEC V1 ticket' => [ 'tec_rest_v1_tec_tc_ticket_transform_entity', Rest::class, 'add_rule_to_tec_rest_api_ticket', 10 ];
		yield 'rule param passed on as ticket data' => [ 'tec_tickets_rest_ticket_data_params', Rest::class, 'add_rule_to_ticket_data_params', 10 ];
		yield 'rule added to the classic panel data' => [ 'tec_tickets_ticket_panel_data', Classic_Panel_Data::class, 'add_rule_to_panel_data', 10 ];
		yield 'sales window fields rendered' => [ 'tribe_template_include_html:tickets/admin-views/editor/panel/fields/dates', Editor::class, 'render_sales_window_fields', 10 ];
	}

	/**
	 * @test
	 * @dataProvider hook_provider
	 */
	public function should_hook_the_service_and_unhook_it_when_unregistered( string $hook, string $service, string $method, int $priority ): void {
		$controller = $this->make_controller();
		$controller->register();
		$callback = $this->test_services->callback( $service, $method );

		$this->assertSame( $priority, has_filter( $hook, $callback ) );

		$controller->unregister();

		$this->assertFalse( has_filter( $hook, $callback ) );
	}

	/**
	 * A second tickets handler would hook the ticket saves again from its constructor.
	 *
	 * @test
	 */
	public function should_build_the_rule_store_with_the_tickets_handler_already_in_use(): void {
		$controller = $this->make_controller();
		$controller->register();
		$tickets_handler = tribe( 'tickets.handler' );

		$this->test_services->get( Rule_Store::class );

		$this->assertSame( [ spl_object_id( $tickets_handler ) ], $this->get_hooked_tickets_handlers() );
	}

	/**
	 * Gets the tickets handlers that hooked their unlimited term name on `init`.
	 *
	 * @return int[] The object IDs of the hooked tickets handlers.
	 */
	private function get_hooked_tickets_handlers(): array {
		$handlers = [];

		foreach ( $GLOBALS['wp_filter']['init']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof Tickets_Handler ) {
					$handlers[] = spl_object_id( $callback['function'][0] );
				}
			}
		}

		return array_values( array_unique( $handlers ) );
	}
}
