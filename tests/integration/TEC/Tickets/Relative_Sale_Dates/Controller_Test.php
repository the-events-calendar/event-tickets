<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;

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
	 * @return Generator<string,array{0: string, 1: string, 2: string, 3: int}>
	 */
	public function hook_provider(): Generator {
		yield 'ticket dates set before the save' => [ 'tec_tickets_ticket_pre_save', Ticket_Save::class, 'set_ticket_dates', 10 ];
		yield 'rule stored after the save' => [ 'tec_tickets_ticket_upserted', Ticket_Save::class, 'save_rule', 10 ];
		yield 'resolved dates written after the save' => [ 'tec_tickets_ticket_upserted', Ticket_Save::class, 'write_resolved_dates', 20 ];
		yield 'ticket data validated' => [ 'tec_tickets_ticket_data_validation', Ticket_Save::class, 'validate_ticket_data', 10 ];
		yield 'event date meta added' => [ 'added_post_meta', Event_Listener::class, 'mark_moved_event', 10 ];
		yield 'event date meta updated' => [ 'updated_postmeta', Event_Listener::class, 'mark_moved_event', 10 ];
		yield 'saved event tickets updated' => [ 'wp_after_insert_post', Event_Listener::class, 'update_saved_event_tickets', 10 ];
		yield 'moved event tickets updated' => [ 'tec_shutdown', Event_Listener::class, 'update_moved_event_tickets', 10 ];
		yield 'legacy end date sync filtered' => [ 'tec_tickets_ticket_end_date_follows_event_start', Event_Listener::class, 'filter_end_date_follows_event_start', 10 ];
		yield 'rules copied to duplicates' => [ 'tec_tickets_tickets_duplicated', Event_Listener::class, 'copy_rules_to_duplicates', 10 ];
		yield 'duplicated tickets updated' => [ 'tec_tickets_tickets_duplicated', Event_Listener::class, 'update_duplicated_tickets', 20 ];
		yield 'block editor rule mapped' => [ 'tec_tickets_rest_single_ticket_add_data', Rest::class, 'map_block_editor_rule', 10 ];
		yield 'block editor sale price rule mapped' => [ 'tec_tickets_rest_single_ticket_add_data', Rest::class, 'map_block_editor_sale_price_rule', 10 ];
		yield 'rule added to the block editor ticket' => [ 'tribe_tickets_rest_api_ticket_data', Rest::class, 'add_rule_to_block_editor_ticket_data', 10 ];
		yield 'sale price rule added to the block editor ticket' => [ 'tribe_tickets_rest_api_ticket_data', Rest::class, 'add_sale_price_rule_to_block_editor_ticket_data', 10 ];
		yield 'rule added to the request body definition' => [ 'tec_rest_swagger_ticket_request_body_definition', Rest::class, 'add_rule_to_definition', 10 ];
		yield 'rule added to the ticket definition' => [ 'tec_rest_swagger_ticket_definition', Rest::class, 'add_rule_to_definition', 10 ];
		yield 'rule sent as null kept' => [ 'tec_rest_schema_filter', Rest::class, 'keep_a_rule_sent_as_null', 10 ];
		yield 'stored rules kept in a TEC V1 update' => [ 'tec_tickets_rest_ticket_upsert_params', Rest::class, 'keep_stored_rules_in_tec_rest_api_update', 10 ];
		yield 'rule added to the TEC V1 ticket' => [ 'tec_rest_v1_tec_tc_ticket_transform_entity', Rest::class, 'add_rule_to_tec_rest_api_ticket', 10 ];
		yield 'rule added to the classic panel data' => [ 'tec_tickets_ticket_panel_data', Classic_Panel_Data::class, 'add_rule_to_panel_data', 10 ];
		yield 'sales window fields rendered' => [ 'tribe_template_include_html:tickets/admin-views/editor/panel/fields/dates', Editor::class, 'render_sales_window_fields', 10 ];
		yield 'tickets list dates context filtered' => [ 'tribe_template_context:tickets/admin-views/editor/list-row/available-dates', Editor::class, 'filter_available_dates_context', 10 ];
		yield 'sale price rule stored' => [ 'tec_tickets_commerce_after_save_ticket', Sale_Price_Save::class, 'save_rule', 10 ];
		yield 'sale price dates written' => [ 'tec_tickets_commerce_after_save_ticket', Sale_Price_Save::class, 'write_resolved_dates', 20 ];
		yield 'sale price data validated' => [ 'tec_tickets_ticket_data_validation', Sale_Price_Save::class, 'validate_ticket_data', 20 ];
		yield 'sale price fields rendered' => [ 'tribe_template_include_html:tickets/admin-views/commerce/metabox/sale-price', Sale_Price_Editor::class, 'render_sale_price_fields', 10 ];
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
}
