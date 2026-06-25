<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * SMTNC-345: a disabled Tickets Commerce module must resolve as an INACTIVE provider even after
 * something constructs it (e.g. ETP's Orders Tabbed View), so the Attendees inventory path cannot
 * reach the unloaded tec_tc_* helpers and fatal.
 *
 * @group commerce
 */
class Provider_Active_TC_Disabled_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/** @var string|false TEC_TICKETS_COMMERCE as the suite left it before this test. */
	private $original_tc_env;

	public function _setUp() {
		parent::_setUp();
		// A defined constant overrides the env var and would defeat disable_tc().
		$this->assertFalse( defined( 'TEC_TICKETS_COMMERCE' ), 'TEC_TICKETS_COMMERCE must not be defined.' );
		$this->original_tc_env = getenv( 'TEC_TICKETS_COMMERCE' );
	}

	public function _tearDown() {
		if ( false === $this->original_tc_env ) {
			putenv( 'TEC_TICKETS_COMMERCE' );
		} else {
			putenv( 'TEC_TICKETS_COMMERCE=' . $this->original_tc_env );
		}
		parent::_tearDown();
	}

	/**
	 * tec_tickets_commerce_is_enabled() reads the env var before the filter, so only putenv flips it
	 * in this suite -- a __return_false filter is ignored.
	 */
	private function disable_tc(): void {
		putenv( 'TEC_TICKETS_COMMERCE=0' );
	}

	/**
	 * Event + stock-managed TC ticket + attendee (TC on), then force-construct the Module so it
	 * self-registers into Tribe__Tickets__Tickets::$active_modules -- exactly the state ETP creates
	 * via Reports\Tabbed_View::register_tabs() ( tribe( Module::class ) ) on the Attendees screen.
	 *
	 * @return array{event_id:int, ticket_id:int}
	 */
	private function make_active_tc_ticket(): array {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'post' ] );
		$ticket_id = $this->with_capacity( 25 )->create_tc_ticket( $event_id, 20 );
		$this->create_attendee_for_ticket( $ticket_id, $event_id );

		tribe( Module::class );

		return compact( 'event_id', 'ticket_id' );
	}

	/**
	 * The fix: a constructed-but-disabled Commerce module is not an active provider.
	 * Fails on HEAD (is_provider_active returns true once the Module is in the modules list).
	 *
	 * @test
	 */
	public function it_reports_commerce_inactive_when_tc_disabled() {
		$this->make_active_tc_ticket();

		$this->disable_tc();

		$this->assertFalse(
			tribe_tickets_is_provider_active( Module::class ),
			'A disabled Tickets Commerce module must not be an active provider, even once constructed.'
		);
	}

	/**
	 * The guard must not over-reach: with TC enabled the Module resolves active as before.
	 *
	 * @test
	 */
	public function it_reports_commerce_active_when_tc_enabled() {
		$this->make_active_tc_ticket();

		$this->assertTrue(
			tribe_tickets_is_provider_active( Module::class ),
			'Tickets Commerce must resolve as active when enabled.'
		);
	}

	/**
	 * The crash path: Ticket_Object::inventory() resolves the provider; with the fix it now comes back
	 * inactive, so inventory() takes its empty-provider fallback instead of fetching attendees -- no fatal.
	 *
	 * @test
	 */
	public function it_does_not_fatal_on_inventory_when_tc_disabled() {
		[ 'event_id' => $event_id, 'ticket_id' => $ticket_id ] = $this->make_active_tc_ticket();
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket );

		$this->disable_tc();

		$threw     = false;
		$inventory = null;
		try {
			$inventory = $ticket->inventory();
		} catch ( \Throwable $e ) {
			$threw = true;
		}

		$this->assertFalse( $threw, 'Inventory must not throw when Tickets Commerce is disabled.' );
		$this->assertIsInt( $inventory, 'Inventory falls back to a numeric value when the provider is inactive.' );
	}
}
