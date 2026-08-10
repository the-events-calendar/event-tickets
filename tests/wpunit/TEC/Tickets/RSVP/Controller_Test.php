<?php
/**
 * Tests for the main RSVP Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP;

use ReflectionMethod;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Migrations\RSVP_To_Tickets_Commerce;
use function TEC\Common\StellarWP\Migrations\migrations;

/**
 * Class Controller_Test
 *
 * @since TBD
 */
class Controller_Test extends Controller_Test_Case {
	/**
	 * The controller class to test.
	 *
	 * @var string
	 */
	protected $controller_class = Controller::class;

	/**
	 * While the subject of this test case is the main RSVP Controller, we also need
	 * to handle the sub-controllers managed by it.
	 *
	 * @var \class-string[]
	 */
	protected $sub_controller_classes = [
		V1\Controller::class,
	];

	public function test_is_active_always_returns_true(): void {
		$controller = $this->make_controller();
		$this->assertTrue( $controller->is_active() );
	}

	public function test_is_rsvp_enabled_by_default(): void {
		$controller = $this->make_controller();
		$this->assertTrue( $controller->is_rsvp_enabled() );
	}

	public function test_filter_can_disable_rsvp(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$this->assertFalse( $controller->is_rsvp_enabled() );
	}

	public function test_option_can_disable_rsvp(): void {
		// Must set option before creating controller instance.
		update_option( 'tec_tickets_rsvp_active', 0 );
		$controller = $this->make_controller();
		$this->assertFalse( $controller->is_rsvp_enabled() );
	}

	public function test_env_var_disables_rsvp(): void {
		putenv( Controller::DISABLED . '=1' );
		$controller = $this->make_controller();
		$this->assertFalse( $controller->is_rsvp_enabled() );
		putenv( Controller::DISABLED ); // Clean up.
	}

	public function test_registers_v1_controller_when_enabled(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Verify the V1 Controller bindings are present (this confirms registration).
		$this->assertInstanceOf(
			\Tribe__Tickets__RSVP::class,
			tribe( 'tickets.rsvp' )
		);

		// Verify RSVP repositories are registered.
		$this->assertTrue( tribe()->has( 'tickets.ticket-repository.rsvp' ) );
		$this->assertTrue( tribe()->has( 'tickets.attendee-repository.rsvp' ) );
	}

	public function test_registers_rsvp_disabled_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();
		$this->assertInstanceOf( RSVP_Disabled::class, tribe( 'tickets.rsvp' ) );
	}

	public function test_registers_null_object_repositories_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		$this->assertInstanceOf(
			Repositories\Ticket_Repository_Disabled::class,
			tribe( 'tickets.ticket-repository.rsvp' )
		);
		$this->assertInstanceOf(
			Repositories\Attendee_Repository_Disabled::class,
			tribe( 'tickets.attendee-repository.rsvp' )
		);
	}

	public function test_unregister_delegates_to_v1_controller(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Verify the V1 Controller bindings are present, confirming registration.
		$this->assertInstanceOf(
			\Tribe__Tickets__RSVP::class,
			tribe( 'tickets.rsvp' )
		);

		// Get a reference to the registered RSVP instance.
		$rsvp = tribe( 'tickets.rsvp' );

		// Verify hooks are registered.
		$this->assertGreaterThan(
			0,
			has_action( 'init', [ $rsvp, 'init' ] ),
			'RSVP init hook should be registered'
		);

		$controller->unregister();

		// Verify V1 Controller hooks are removed after unregistration.
		$this->assertFalse(
			has_action( 'init', [ $rsvp, 'init' ] ),
			'RSVP init hook should be unregistered'
		);
	}

	public function test_unregister_does_not_fail_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		// Should not throw any errors.
		$controller->unregister();

		// If we get here without exception, test passes.
		$this->assertTrue( true );
	}

	public function test_add_rsvp_disabled_editor_config_sets_flag(): void {
		$controller = $this->make_controller();
		$config     = $controller->add_rsvp_disabled_editor_config( [] );

		$this->assertTrue( $config['tickets']['rsvpDisabled'] );
		$this->assertStringContainsString( 'page=tec-tickets-settings&tab=migrations', $config['tickets']['migrationsTabUrl'] );
	}

	public function test_add_rsvp_disabled_editor_config_preserves_existing_tickets_config(): void {
		$controller = $this->make_controller();
		$config     = $controller->add_rsvp_disabled_editor_config( [
			'tickets' => [ 'someOtherKey' => 'value' ],
		] );

		$this->assertSame( 'value', $config['tickets']['someOtherKey'] );
		$this->assertTrue( $config['tickets']['rsvpDisabled'] );
	}

	public function test_add_rsvp_disabled_editor_config_preserves_other_config_keys(): void {
		$controller = $this->make_controller();
		$config     = $controller->add_rsvp_disabled_editor_config( [
			'common' => [ 'key' => 'value' ],
		] );

		$this->assertSame( [ 'key' => 'value' ], $config['common'] );
		$this->assertTrue( $config['tickets']['rsvpDisabled'] );
	}

	public function test_disable_rsvp_form_toggle_sets_rsvp_false(): void {
		$controller = $this->make_controller();
		$enabled    = $controller->disable_rsvp_form_toggle( [ 'rsvp' => true, 'tc' => true ] );

		$this->assertFalse( $enabled['rsvp'] );
	}

	public function test_disable_rsvp_form_toggle_preserves_other_forms(): void {
		$controller = $this->make_controller();
		$enabled    = $controller->disable_rsvp_form_toggle( [ 'rsvp' => true, 'tc' => true ] );

		$this->assertTrue( $enabled['tc'] );
	}

	public function test_disable_rsvp_form_toggle_sets_rsvp_migrating_true_while_migration_in_progress(): void {
		tribe_update_option( Controller::VERSION_OPTION_KEY, Controller::DISABLED );

		$controller = $this->make_controller();
		$enabled    = $controller->disable_rsvp_form_toggle( [ 'rsvp' => true ] );

		$this->assertTrue( $enabled['rsvp_migrating'], 'rsvp_migrating should be true while the migration is running or paused.' );

		tribe_remove_option( Controller::VERSION_OPTION_KEY );
	}

	public function test_disable_rsvp_form_toggle_sets_rsvp_migrating_false_when_migration_completed(): void {
		tribe_update_option( Controller::VERSION_OPTION_KEY, Controller::VERSION_2 );

		$controller = $this->make_controller();
		$enabled    = $controller->disable_rsvp_form_toggle( [ 'rsvp' => true ] );

		$this->assertFalse( $enabled['rsvp_migrating'], 'rsvp_migrating should be false once migration has completed (RSVP button should be hidden, not disabled).' );

		tribe_remove_option( Controller::VERSION_OPTION_KEY );
	}

	public function test_is_migration_in_progress_false_by_default(): void {
		tribe_remove_option( Controller::VERSION_OPTION_KEY );

		$this->assertFalse( Controller::is_migration_in_progress() );
	}

	public function test_is_migration_in_progress_true_when_version_option_disabled(): void {
		tribe_update_option( Controller::VERSION_OPTION_KEY, Controller::DISABLED );

		$this->assertTrue( Controller::is_migration_in_progress() );

		tribe_remove_option( Controller::VERSION_OPTION_KEY );
	}

	/**
	 * Runs a callback with `rsvp-to-tc` absent from the registry, reproducing the state the version
	 * detection actually runs in: `Tribe__Tickets__Main::bind_implementations()` calls
	 * `maybe_activate_tickets_commerce()` before the provider that registers the migration.
	 *
	 * The migration is always restored, so a failure here cannot cascade into other tests.
	 *
	 * @param callable $callback The callback to run.
	 *
	 * @return mixed The callback return value.
	 */
	private function without_registered_rsvp_migration( callable $callback ) {
		$registry = migrations()->get_registry();
		$registry->offsetUnset( 'rsvp-to-tc' );

		try {
			return $callback();
		} finally {
			$registry->register( 'rsvp-to-tc', RSVP_To_Tickets_Commerce::class );
		}
	}

	/**
	 * Runs the private version detection.
	 *
	 * @return string The detected version.
	 */
	private function detect_version(): string {
		$method = new ReflectionMethod( Controller::class, 'detect_version_from_migration_status' );
		$method->setAccessible( true );

		return $method->invoke( null );
	}

	/**
	 * Runs the private version resolution, which also persists the result.
	 *
	 * @return string The resolved version.
	 */
	private function resolve_and_persist_version(): string {
		$method = new ReflectionMethod( Controller::class, 'get_version_from_migration_status' );
		$method->setAccessible( true );

		return $method->invoke( null );
	}

	/**
	 * Creates a legacy V1 RSVP ticket.
	 *
	 * @return int The ticket post ID.
	 */
	private function given_a_v1_rsvp_ticket(): int {
		return wp_insert_post(
			[
				'post_type'   => 'tribe_rsvp_tickets',
				'post_title'  => 'Legacy RSVP ticket',
				'post_status' => 'publish',
			]
		);
	}

	public function test_detects_v1_when_migration_not_registered_and_v1_tickets_exist(): void {
		tribe_remove_option( Controller::VERSION_OPTION_KEY );
		$this->given_a_v1_rsvp_ticket();

		$version = $this->without_registered_rsvp_migration(
			function () {
				return $this->detect_version();
			}
		);

		$this->assertSame(
			Controller::VERSION_1,
			$version,
			'A site still holding V1 RSVP tickets must stay on V1 when the migration is not in the registry yet, otherwise every legacy RSVP ticket and attendee becomes unreadable.'
		);
	}

	public function test_detects_v2_when_migration_not_registered_and_no_v1_tickets_exist(): void {
		tribe_remove_option( Controller::VERSION_OPTION_KEY );

		$version = $this->without_registered_rsvp_migration(
			function () {
				return $this->detect_version();
			}
		);

		$this->assertSame(
			Controller::VERSION_2,
			$version,
			'With no V1 RSVP data on the site the migration is genuinely done (or gone), so V2 is correct.'
		);
	}

	public function test_does_not_persist_v2_when_migration_not_registered_and_v1_tickets_exist(): void {
		tribe_remove_option( Controller::VERSION_OPTION_KEY );
		$this->given_a_v1_rsvp_ticket();

		$version = $this->without_registered_rsvp_migration(
			function () {
				return $this->resolve_and_persist_version();
			}
		);

		$this->assertSame( Controller::VERSION_1, $version );
		$this->assertSame(
			Controller::VERSION_1,
			tribe_get_option( Controller::VERSION_OPTION_KEY ),
			'The detected version is persisted permanently, so persisting V2 here would strand the site on a version that cannot read its own RSVP data.'
		);
	}

	public function test_detects_v1_when_migration_is_registered_and_pending(): void {
		tribe_remove_option( Controller::VERSION_OPTION_KEY );
		$this->given_a_v1_rsvp_ticket();

		$this->assertSame(
			Controller::VERSION_1,
			$this->detect_version(),
			'A registered, applicable, never-run migration is pending, which means the site is still on V1.'
		);
	}

	public function test_register_disabled_hooks_editor_config_filter(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		$this->assertNotFalse(
			has_filter( 'tribe_editor_config', [ $controller, 'add_rsvp_disabled_editor_config' ] ),
			'tribe_editor_config filter should be registered when RSVP is disabled'
		);
	}

	public function test_register_disabled_hooks_ticket_forms_filter(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		$this->assertNotFalse(
			has_filter( 'tec_tickets_enabled_ticket_forms', [ $controller, 'disable_rsvp_form_toggle' ] ),
			'tec_tickets_enabled_ticket_forms filter should be registered when RSVP is disabled'
		);
	}

	public function test_unregister_removes_editor_config_filter_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		// Confirm filter is hooked before unregister.
		$this->assertNotFalse(
			has_filter( 'tribe_editor_config', [ $controller, 'add_rsvp_disabled_editor_config' ] )
		);

		$controller->unregister();

		$this->assertFalse(
			has_filter( 'tribe_editor_config', [ $controller, 'add_rsvp_disabled_editor_config' ] ),
			'tribe_editor_config filter should be removed after unregister'
		);
	}

	public function test_unregister_removes_ticket_forms_filter_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		// Confirm filter is hooked before unregister.
		$this->assertNotFalse(
			has_filter( 'tec_tickets_enabled_ticket_forms', [ $controller, 'disable_rsvp_form_toggle' ] )
		);

		$controller->unregister();

		$this->assertFalse(
			has_filter( 'tec_tickets_enabled_ticket_forms', [ $controller, 'disable_rsvp_form_toggle' ] ),
			'tec_tickets_enabled_ticket_forms filter should be removed after unregister'
		);
	}
}
