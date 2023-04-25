<?php

namespace Tribe\Tickets;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\CT1\CT1_Fixtures;
use Tribe__Tickets__Attendees_Table as Attendees_Table;

class Attendees_TableTest extends \Codeception\TestCase\WPTestCase {
	use CT1_Fixtures;
	use Attendee_Maker;
	use Ticket_Maker;

	private $wp_screen_backup;

	private function set_wp_screen(): void {
		global $wp_screen, $hook_suffix;
		$this->wp_screen_backup = $wp_screen;
		$hook_suffix            = 'edit.php';
		$wp_screen              = \WP_Screen::get();
	}

	private function disable_promoter_trigger(): void {
		remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );
	}

	public function _setUp() {
		parent::_setUp();
		$this->enable_provisional_id_normalizer();
		$this->set_wp_screen();
		$this->disable_promoter_trigger();
		$GLOBALS['hook_suffix'] = 'tribe_events_page_tickets-attendees';
	}

	public function _tearDown() {
		$GLOBALS['wp_screen'] = $this->wp_screen_backup;
		parent::_tearDown();
		$this->disable_provisional_id_normalizer();
	}

	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );
	}

	private function make_instance() {
		return new Attendees_Table();
	}

	public function create_order_for_ticket( $ticket_id, $quantity = 5 ) {
		// create order.
		$cart = new Cart();
		$cart->get_repository()->add_item( $ticket_id, $quantity );

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test' . uniqid() . '@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$completed = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		return $order;
	}

	/**
	 * It should allow fetching ticket attendees by event.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_provisional_id() {
		$post       = $this->given_a_migrated_single_event();
		$post_id    = $post->ID;
		$quantity   = 4;
		$occurrence = Occurrence::find_by_post_id( $post_id );

		// Create a faux provisional id.
		$provisional_id = $occurrence->occurrence_id + $this->get_provisional_id_base();
		$ticket_a_id    = $this->create_tc_ticket( $post_id, 10 );

		$this->create_order_for_ticket( $ticket_a_id, $quantity );

		$_GET['event_id'] = $provisional_id;
		$table            = $this->make_instance();

		$table->prepare_items();
		$attendee_ids = wp_list_pluck( $table->items, 'attendee_id' );

		$this->assertNotEmpty( $attendee_ids );
		$this->assertEquals( $quantity, count( $attendee_ids ) );
		$this->assertEquals( $table->get_pagination_arg( 'total_items' ), count( $attendee_ids ) );
	}
}
