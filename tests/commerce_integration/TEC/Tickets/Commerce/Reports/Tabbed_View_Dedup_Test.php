<?php

namespace TEC\Tickets\Commerce\Reports;

use Codeception\TestCase\WPTestCase;
use Tribe__Tabbed_View;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

/**
 * Regression: on the legacy Attendees admin page, both Tickets Commerce and
 * Event Tickets Plus (WooCommerce) used to register an "Orders" tab on the
 * same tabbed view, rendering "Orders | Orders" side by side.
 *
 * `Tabbed_View::register_tabs()` should now defer to ETP's WooCommerce Orders tab when it is already registered.
 */
class Tabbed_View_Dedup_Test extends WPTestCase {

	use With_Tickets_Commerce;
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * Event Tickets Plus is intentionally not loaded by the commerce_integration suite so ET can
	 * be exercised standalone. Load a minimal stub of ETP's WooCommerce Orders tab class with
	 * the exact FQN the production code checks against, so the `instanceof` branch can be tested.
	 *
	 * In production, when ETP is absent PHP evaluates `$tab instanceof \Missing\Class` as `false`
	 * without fataling, so `Tabbed_View::woo_orders_tab_is_registered()` is safe either way.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once dirname( __DIR__, 5 ) . '/_support/Stubs/Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab_Stub.php';
	}

	/**
	 * Builds a tabbed view with a real Tickets Commerce ticket on a post so
	 * `Module::post_has_tickets()` is truthy for the target event.
	 */
	private function tabbed_view_for_event(): array {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'post' ] );
		$this->create_tc_ticket( $event_id, 10 );

		$_REQUEST['event_id'] = $event_id;
		$_REQUEST['post_id']  = $event_id;

		$tabbed_view = new Tribe__Tabbed_View();

		return [ $tabbed_view, get_post( $event_id ) ];
	}

	public function test_registers_tc_orders_tab_when_no_woo_tab_present(): void {
		[ $tabbed_view, $post ] = $this->tabbed_view_for_event();

		( new Tabbed_View() )->register_tabs( $tabbed_view, $post );

		$slugs = array_map( static fn( $t ) => $t->get_slug(), $tabbed_view->get_tabs() );

		$this->assertContains( Orders::$tab_slug, $slugs );
	}

	public function test_skips_tc_orders_tab_when_woo_tab_already_registered(): void {
		[ $tabbed_view, $post ] = $this->tabbed_view_for_event();

		$woo_tab = new \Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab( $tabbed_view );
		$woo_tab->set_url( '#' );
		$tabbed_view->register( $woo_tab );

		( new Tabbed_View() )->register_tabs( $tabbed_view, $post );

		$slugs = array_map( static fn( $t ) => $t->get_slug(), $tabbed_view->get_tabs() );

		$this->assertNotContains( Orders::$tab_slug, $slugs, 'Tickets Commerce Orders tab should be skipped when the WooCommerce Orders tab is already registered.' );
	}

	public function test_legacy_hook_is_bound_at_priority_20(): void {
		// The dedup relies on TC registering after ETP on the legacy hook. Locking the
		// priority in a test guards against accidental reordering.
		$priority = has_action(
			'tribe_tickets_orders_tabbed_view_register_tab_right',
			[ tribe( Tabbed_View::class ), 'register_tabs' ]
		);

		$this->assertSame( 20, $priority );
	}
}
