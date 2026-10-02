<?php

namespace TEC\Tickets\Relative_Sale_Dates;

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
	 * @test
	 */
	public function should_hook_the_ticket_save_and_unhook_it_when_unregistered(): void {
		$controller = $this->make_controller();
		$controller->register();
		$set_ticket_dates = $this->test_services->callback( Ticket_Save::class, 'set_ticket_dates' );
		$save_rule        = $this->test_services->callback( Ticket_Save::class, 'save_rule' );

		$this->assertSame( 10, has_action( 'tec_tickets_ticket_pre_save', $set_ticket_dates ) );
		$this->assertSame( 10, has_action( 'tec_tickets_ticket_upserted', $save_rule ) );

		$controller->unregister();

		$this->assertFalse( has_action( 'tec_tickets_ticket_pre_save', $set_ticket_dates ) );
		$this->assertFalse( has_action( 'tec_tickets_ticket_upserted', $save_rule ) );
	}
}
