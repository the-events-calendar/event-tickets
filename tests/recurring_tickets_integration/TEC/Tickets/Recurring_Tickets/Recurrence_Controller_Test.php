<?php

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Common\StellarWP\AdminNotices\AdminNotices;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Updates\Events;

class Recurrence_Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Recurrence_Controller::class;

	/**
	 * The ECP constants this test removed, to put back.
	 *
	 * @var array<string,string>
	 */
	private array $removed_constants = [];

	/**
	 * @after
	 */
	public function restore(): void {
		putenv( Recurrence_Controller::DISABLED );
		foreach ( $this->removed_constants as $constant => $value ) {
			uopz_redefine( Events::class, $constant, $value );
		}
		$this->removed_constants = [];
		AdminNotices::removeNotice( Recurrence_Controller::OUTDATED_ECP_NOTICE );
	}

	/**
	 * @test
	 */
	public function it_should_be_active_with_an_ecp_that_has_the_hooks(): void {
		$this->assertTrue( $this->make_controller()->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_be_off_with_the_kill_switch_and_say_nothing(): void {
		putenv( Recurrence_Controller::DISABLED . '=1' );

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $controller->is_active() );
		$this->assertFalse( Recurrence_Controller::is_registered() );
		$this->assertArrayNotHasKey( Recurrence_Controller::OUTDATED_ECP_NOTICE, $this->notices() );
	}

	/**
	 * @test
	 */
	public function it_should_be_off_on_an_older_ecp_and_ask_to_update_it(): void {
		$this->remove_ecp_constant( 'AFTER_TRANSFER_OCCURRENCES_ACTION' );

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $controller->is_active() );
		$this->assertFalse( Recurrence_Controller::is_registered() );
		$notice = $this->notices()[ Recurrence_Controller::OUTDATED_ECP_NOTICE ] ?? null;
		$this->assertNotNull( $notice, 'An older ECP is named in an admin notice.' );
		$this->assertStringContainsString( 'Events Calendar Pro', (string) $notice->getRenderTextOrCallback() );
		$this->assertTrue( $notice->isDismissible() );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_core_tier_on_when_it_is_off(): void {
		putenv( Recurrence_Controller::DISABLED . '=1' );

		$this->make_controller()->register();

		$this->assertTrue( Core_Controller::is_registered() );
	}

	/**
	 * Removes one of the constants an ECP with the soft-4830 hooks defines.
	 *
	 * @param string $constant The constant's name.
	 *
	 * @return void
	 */
	private function remove_ecp_constant( string $constant ): void {
		$this->removed_constants[ $constant ] = constant( Events::class . '::' . $constant );
		uopz_undefine( Events::class, $constant );
		$this->assertFalse( defined( Events::class . '::' . $constant ) );
	}

	/**
	 * @return array<string,object> The registered admin notices, by ID.
	 */
	private function notices(): array {
		$notices = [];
		foreach ( AdminNotices::getNotices() as $notice ) {
			$notices[ $notice->getId() ] = $notice;
		}

		return $notices;
	}
}
