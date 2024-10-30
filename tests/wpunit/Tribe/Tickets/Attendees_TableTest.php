<?php

namespace Tribe\Tickets;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Attendees_Table as Attendees_Table;
use Tribe__Tickets__Data_API as Data_API;

class Attendees_TableTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		$GLOBALS['hook_suffix'] = 'tribe_events_page_tickets-attendees';
	}

	private function make_instance() {
		return new Attendees_Table();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Attendees_Table::class, $sut );
	}

	/**
	 * It should allow fetching ticket attendees by event.
	 *
	 * @todo @bordoni fix this particular test to be more consistent [TECENG-37]
	 * @link https://theeventscalendar.atlassian.net/browse/TECENG-37
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_event() {
		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 25, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 25, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$paypal_attendee_ids2 = $this->create_many_attendees_for_ticket( 25, $paypal_ticket_id2, $post_id2 );
		$rsvp_attendee_ids2   = $this->create_many_attendees_for_ticket( 25, $rsvp_ticket_id2, $post_id2 );

		/*
		 Attendees will be ordered by `post_date` and `post_title`. Since we're creating the Attendees with a factory,
		the `post_date` will be the same for pretty much all of them. The purpose of the test is to assert we get the
		correct Attendees so we make the order deterministic among posts that would have an undetermined order by
		setting the `post_title` to a string including a progressive number.
		 */
		foreach (
			[
				...$paypal_attendee_ids,
				...$rsvp_attendee_ids,
				...$paypal_attendee_ids2,
				...$rsvp_attendee_ids2
			] as $k => $id
		) {
			wp_update_post( [
				'ID'         => $id,
				'post_title' => 'Attendee #' . str_pad( $k, '3', '0', STR_PAD_LEFT ),
			] );
		}

		$sut = $this->make_instance();

		$_GET['event_id'] = $post_id;
		$_GET['paged']    = 1;

		$sut->prepare_items();
		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$expected_attendee_array = array_merge( $paypal_attendee_ids, $rsvp_attendee_ids );
		// Sort descending by ID, to match the order.
		rsort( $expected_attendee_array );

		$expected_attendee_ids = array_slice( $expected_attendee_array, 0, $sut->get_pagination_arg( 'per_page' ) );

		$this->assertEquals( $expected_attendee_ids, $attendee_ids );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ) ), $sut->get_pagination_arg( 'total_items' ) );

		$_GET['event_id'] = $post_id2;
		$_GET['paged']    = 1;
		$sut->prepare_items();
		$attendee_ids2 = wp_list_pluck( $sut->items, 'attendee_id' );

		$expected_attendee_array2 = array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 );
		// Sort descending by ID, to match the order.
		rsort( $expected_attendee_array2 );

		$expected_attendee_ids2 = array_slice( $expected_attendee_array2, 0, $sut->get_pagination_arg( 'per_page' ) );

		$this->assertEquals( $expected_attendee_ids2, $attendee_ids2 );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 ) ), $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by name search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_name_search() {
		$post_id = $this->factory->post->create();

		$rsvp_name_meta_key = tribe( 'tickets.rsvp' )->full_name;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_name_meta_key, 'Robbbbbbbbbbbbb Tester' );

		$sut = $this->make_instance();

		$_GET['search']                      = 'robbbbb';
		$_POST['tribe_attendee_search_type'] = 'purchaser_name';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by email search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_email_search() {
		$post_id = $this->factory->post->create();

		$rsvp_email_meta_key = tribe( 'tickets.rsvp' )->email;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_email_meta_key, 'rob@likestests.com' );

		$sut = $this->make_instance();

		$_GET['search']                      = 'likestests';
		$_POST['tribe_attendee_search_type'] = 'purchaser_email';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by ticket search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_ticket_search() {
		$post_id = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$sut = $this->make_instance();

		$_GET['search']                      = $rsvp_ticket_id;
		$_POST['tribe_attendee_search_type'] = 'product_id';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( $rsvp_attendee_ids, $attendee_ids );
		$this->assertEquals( count( $rsvp_attendee_ids ), $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by security code search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_security_code_search() {
		$post_id = $this->factory->post->create();

		$rsvp_security_code_meta_key = tribe( 'tickets.rsvp' )->security_code;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_security_code_meta_key, 'robba' );

		$sut = $this->make_instance();

		$_GET['search']                      = 'robba';
		$_POST['tribe_attendee_search_type'] = 'security_code';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by user search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_user_search() {
		$post_id = $this->factory->post->create();

		$rsvp_user_meta_key = tribe( 'tickets.rsvp' )->attendee_user_id;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_user_meta_key, '1234567890' );

		$sut = $this->make_instance();

		$_GET['search']                      = '1234567890';
		$_POST['tribe_attendee_search_type'] = 'user';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by order status search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_order_status_search() {
		$post_id = $this->factory->post->create();

		$rsvp_user_meta_key = tribe( 'tickets.rsvp' )->attendee_user_id;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$sut = $this->make_instance();

		$_GET['search']                      = 'yes';
		$_POST['tribe_attendee_search_type'] = 'order_status';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( $rsvp_attendee_ids, $attendee_ids );
		$this->assertEquals( 5, $sut->get_pagination_arg( 'total_items' ) );
	}

	public function test_display(): void {
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		$post_id = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $post_id );

		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';

		$table = $this->make_instance();
		$table->prepare_items();

		$attendee_ids  = wp_list_pluck( $table->items, 'attendee_id' );
		$attendee_data = array_reduce( $attendee_ids, function ( array $carry, int $id ) {
			foreach (
				[
					'_tribe_tpp_security_code',
					'_tribe_tpp_full_name',
					'_tribe_tpp_email',
					'_tribe_rsvp_security_code',
					'_tribe_rsvp_full_name',
					'_tribe_rsvp_email',
				] as $meta_key
			) {
				$meta_value = get_post_meta( $id, $meta_key, true );

				if ( empty( $meta_value ) ) {
					continue;
				}

				$carry[] = esc_html( $meta_value );
				$carry[] = $meta_value;
			}

			return $carry;
		}, [] );

		ob_start();
		$table->display();
		$html = ob_get_clean();

		// Stabilize snapshots.
		$html = str_replace( $attendee_data, 'ATTENDEE_DATA', $html );
		$html = str_replace( [
			...$attendee_ids,
			$post_id,
			$paypal_ticket_id,
			$rsvp_ticket_id,
			...$rsvp_attendee_ids,
			...$paypal_attendee_ids
		], 'POST_ID', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
