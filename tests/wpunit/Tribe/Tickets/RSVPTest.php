<?php

namespace Tribe\Tickets;

use Prophecy\Argument;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Tickets_Handler as Handler;
use Tribe__Tickets__Tickets_View as Tickets_View;

class RSVPTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;
	use Attendee_Maker;
	use RSVP_Ticket_Maker;

	/**
	 * @var Tickets_View
	 */
	protected $tickets_view;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->tickets_view = new Tickets_View();

		// we always need an event, build it during setup.
		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();

		// Tribe__Tickets__Tickets_Handler handler for easier access
		$this->handler = new Handler;

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post', 'tribe_events' ];
		} );

		// let's avoid die()s
		add_filter( 'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		} );

		// let's avoid confirmation emails
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

		$this->unset_POST();
	}

	public function dont_die() {
		// no-op, go on
	}

	public function tearDown() {
		// your tear down methods here
		// refresh the event ID for each test.
		wp_delete_post( $this->event_id, true );
		unset( $this->event_id );

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( RSVP::class, $sut );
	}

	private function make_instance() {
		/** @var RSVP $instance */
		$instance = ( new \ReflectionClass( RSVP::class ) )->newInstanceWithoutConstructor();
		$instance->set_tickets_view( $this->tickets_view );

		return $instance;
	}

	/**
	 * @test
	 * it should decrease stock by 1 when an attendee is going
	 *
	 * @since 4.7.4
	 */
	public function it_should_decrease_stock_by_1_when_an_attendee_is_going() {;
		$ticket_id = $this->make_stock_ticket( 10, $this->event_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$this->assertEquals( 9, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @test
	 * it should increase sales by 1 when an attendee is going
	 */
	public function it_should_increase_sales_by_1_when_an_attendee_is_going() {;
		$ticket_id = $this->make_sales_ticket( 10, $this->event_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$this->assertEquals( 11, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should not increase sales when an attendee is not going
	 */
	public function it_should_not_increase_sales_when_an_attendee_is_not_going() {;
		$ticket_id = $this->make_sales_ticket( 10, $this->event_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'no' ] ), false );

		$this->assertEquals( 10, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should not increase sales when an attendee is not going
	 *
	 * @since 4.7.4
	 */
	public function it_should_not_decrease_stock_when_an_attendee_is_not_going() {;
		$ticket_id = $this->make_stock_ticket( 10, $this->event_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'no' ] ), false );

		$this->assertEquals( 10, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @test
	 * it should increase sales by the status stock size
	 */
	public function it_should_increase_sales_by_the_status_stock_size() {
		add_filter( 'event_tickets_rsvp_options', function ( $options ) {
			return array_merge( $options, [
					'yes-plus-one' => [ 'label' => 'Yes plus one', 'decrease_stock_by' => 2 ],
				] );
		} );

		$ticket_id = $this->setup_POST( 'yes-plus-one', 10 );

		$sut = $this->make_instance();
		$sut->maybe_generate_tickets();

		$this->assertEquals( 12, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should decrease sales by 1 when attendee status changes from going to not going
	 */
	public function it_should_decrease_sales_by_1_when_attendee_status_changes_from_going_to_not_going() {;
		$ticket_id = $this->make_stock_ticket( 10, $this->event_id );

		list( $data, $ticket_id, $order_id, $this->event_id ) = $this->make_data( 'yes', 'no', 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $this->event_id );
		$total_sales = get_post_meta( $ticket_id, 'total_sales', true );

		$this->assertEquals( 9, $total_sales );
	}

	/**
	 * @test
	 * it should decrease sales by 1 when attendee status changes from going to not going
	 *
	 * @since 4.7.4
	 */
	public function it_should_increase_stock_by_1_when_attendee_status_changes_from_going_to_not_going() {
		list( $data, $ticket_id, $order_id, $this->event_id ) = $this->make_data( 'yes', 'no', 1, 9 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $this->event_id );

		$this->assertEquals( 10, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @test
	 * it should increase sales by 1 when attendee status changes from not going to going
	 */
	public function it_should_increase_sales_by_1_when_attendee_status_changes_from_not_going_to_going() {;
		$ticket_id = $this->make_stock_ticket( 0, $this->event_id );


		list( $data, $ticket_id, $order_id, $this->event_id ) = $this->make_data( 'no', 'yes', 0, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $this->event_id );
		// checking things

		$this->assertEquals( 1, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should increase sales by 1 when attendee status changes from not going to going
	 *
	 * @since 4.7.4
	 */
	public function it_should_decrease_stock_by_1_when_attendee_status_changes_from_not_going_to_going() {
		list( $data, $ticket_id, $order_id, $this->event_id ) = $this->make_data( 'no', 'yes', 0, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $this->event_id );

		$this->assertEquals( 9, get_post_meta( $ticket_id, '_stock', true ) );
	}

	public function stati_stocks_provider() {
		return [
			// $a_status_stock_size, $b_status_stock_size, $old_status, $new_status, $diff
			[ 1, 2, 'a', 'b', 1 ],
			[ 2, 1, 'a', 'b', - 1 ],
			[ 1, 1, 'a', 'b', 0 ],
			[ 2, 2, 'a', 'b', 0 ],
			[ 0, 0, 'a', 'b', 0 ],
			[ 0, 1, 'a', 'b', 1 ],
			[ 1, 0, 'a', 'b', - 1 ],
			[ 5, 4, 'a', 'b', - 1 ],
			[ 4, 5, 'a', 'b', 1 ],
		];
	}

	/**
	 * @test
	 * it should update the sales according to old and new status stock sizes
	 * @dataProvider stati_stocks_provider
	 *
	 * @since        4.7.4
	 */
	public function it_should_update_the_sales_according_to_old_and_new_status_stock_sizes( $a_status_stock_size, $b_status_stock_size, $old_status, $new_status, $diff ) {
		$stati = [
			'a' => [ 'label' => 'a', 'decrease_stock_by' => $a_status_stock_size ],
			'b' => [ 'label' => 'b', 'decrease_stock_by' => $b_status_stock_size ],
		];

		add_filter( 'event_tickets_rsvp_options', function ( $options ) use ( $stati ) {
			return array_merge( $options, $stati );
		} );

		list( $data, $ticket_id, $order_id, $this->event_id ) = $this->make_data( $old_status, $new_status, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $this->event_id );

		$this->assertEquals( 10 + $diff, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should update the sales according to old and new status stock sizes
	 * @dataProvider stati_stocks_provider
	 */
	public function it_should_update_the_stock_according_to_old_and_new_status_stock_sizes( $a_status_stock_size, $b_status_stock_size, $old_status, $new_status, $diff ) {
		$stati = [
			'a' => [ 'label' => 'a', 'decrease_stock_by' => $a_status_stock_size ],
			'b' => [ 'label' => 'b', 'decrease_stock_by' => $b_status_stock_size ],
		];

		add_filter( 'event_tickets_rsvp_options', function ( $options ) use ( $stati ) {
			return array_merge( $options, $stati );
		} );

		list( $data, $ticket_id, $order_id, $this->event_id ) = $this->make_data( $old_status, $new_status, 0, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $this->event_id );

		// stock cannot exceed capacity
		$expected_stock = min( 10, 10 - $diff );

		$this->assertEquals( $expected_stock, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_all_attendees_by_attendee_id().
	 */
	public function it_should_return_attendees_from_get_all_attendees_by_attendee_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$attendee_id = $test_attendee['attendee_id'];

		$attendees = $sut->get_all_attendees_by_attendee_id( $attendee_id );
	}

	/**
	 * @test
	 *
	 * It should return attendee from get_attendee().
	 */
	public function it_should_return_attendee_from_get_attendee() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$attendee_formatted = $sut->get_attendee( $test_attendee['attendee_id'], $post_id );

		$this->assertArrayHasKey( 'optout', $attendee_formatted );
		$this->assertArrayHasKey( 'ticket', $attendee_formatted );
		$this->assertArrayHasKey( 'attendee_id', $attendee_formatted );
		$this->assertArrayHasKey( 'security', $attendee_formatted );
		$this->assertArrayHasKey( 'product_id', $attendee_formatted );
		$this->assertArrayHasKey( 'check_in', $attendee_formatted );
		$this->assertArrayHasKey( 'order_status', $attendee_formatted );
		$this->assertArrayHasKey( 'order_status_label', $attendee_formatted );
		$this->assertArrayHasKey( 'user_id', $attendee_formatted );
		$this->assertArrayHasKey( 'ticket_sent', $attendee_formatted );

		$this->assertArrayHasKey( 'holder_name', $attendee_formatted );
		$this->assertArrayHasKey( 'holder_email', $attendee_formatted );
		$this->assertArrayHasKey( 'order_id', $attendee_formatted );
		$this->assertArrayHasKey( 'ticket_id', $attendee_formatted );
		$this->assertArrayHasKey( 'qr_ticket_id', $attendee_formatted );
		$this->assertArrayHasKey( 'security_code', $attendee_formatted );

		$this->assertArrayHasKey( 'attendee_meta', $attendee_formatted );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_array().
	 */
	public function it_should_return_attendees_from_get_attendees_array() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$attendees = $sut->get_attendees_array( $post_id );

		$this->assertCount( 10, $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_by_id().
	 */
	public function it_should_return_attendees_from_get_attendees_by_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$attendees = $sut->get_attendees_by_id( $post_id );

		$this->assertCount( 10, $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_by_id() for order ID.
	 */
	public function it_should_return_attendees_from_get_attendees_by_id_for_order_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$order_id = $test_attendee['order_id'];

		$attendees = $sut->get_attendees_by_id( $order_id );

		$this->assertCount( 1, $attendees );
		$this->assertEquals( [ $test_attendee ], $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_by_id() for ticket ID.
	 */
	public function it_should_return_attendees_from_get_attendees_by_id_for_ticket_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$attendees = $sut->get_attendees_by_id( $ticket_id );

		$this->assertCount( 10, $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_event_id_from_attendee_id() for attendee ID.
	 */
	public function it_should_return_event_id_from_get_event_id_from_attendee_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$attendee_id = $test_attendee['attendee_id'];

		$event_id = $sut->get_event_id_from_attendee_id( $attendee_id );

		$this->assertEquals( $post_id, $event_id );
	}

	/**
	 * @test
	 *
	 * It should return count from get_attendees_count().
	 */
	public function it_should_return_count_from_get_attendees_count() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );

		$this->assertEquals( 10, $sut->get_attendees_count( $post_id ) );
	}

	/**
	 * @test
	 *
	 * It should return count from get_attendees_count_by_user().
	 */
	public function it_should_return_count_from_get_attendees_count_by_user() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$user_id_for_test = $this->factory()->user->create();

		// Generate some tickets while logged in as a test user.
		wp_set_current_user( $user_id_for_test );

		$this->assertCount( 10, $sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false ) );

		// Generate some tickets while logged in.
		wp_set_current_user( $user_id );

		$this->assertCount( 5, $sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false ) );

		$this->assertEquals( 5, $sut->get_attendees_count_by_user( $post_id, $user_id ) );
	}

	/**
	 * @test
	 *
	 * It should return count from get_attendees_count_going().
	 */
	public function it_should_return_count_from_get_attendees_count_going() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );
		$sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'no' ] ), false );

		$this->assertEquals( 10, $sut->get_attendees_count_going( $post_id ) );
	}

	/**
	 * @test
	 *
	 * It should return count from get_attendees_count_not_going().
	 */
	public function it_should_return_count_from_get_attendees_count_not_going() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data( 0, 20 );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ), false );
		$sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'no' ] ), false );

		$this->assertEquals( 5, $sut->get_attendees_count_not_going( $post_id ) );
	}

	/**
	 * Provider for RSVP steps testing.
	 *
	 * @return \Generator
	 */
	public function provider_rsvp_steps() {
		// Initial state.
		yield 'initial state' => [
			null,
		];

		// They choose Going.
		yield 'going' => [
			'going',
		];

		// They choose Not Going.
		yield 'not going' => [
			'not-going',
		];

		// They need the ARI form.
		yield 'ari' => [
			'ari',
		];

		// They complete the RSVP process.
		yield 'success' => [
			'success',
			[
				'attendee'   => $this->fake_attendee_details(),
				'product_id' => 0,
				'quantity'   => 2,
			],
		];

		// They complete the RSVP process.
		yield 'success with failure because of no data' => [
			'success',
			// Pass no data so that it won't process correctly.
			[],
			[
				'success' => false,
				'errors'  => [
					'Your RSVP was unsuccessful, please try again.',
				],
			],
		];

		// They choose to opt-in from success view.
		yield 'opt-in' => [
			'opt-in',
			[
				'opt_in'       => 1,
				'attendee_ids' => '',
				'opt_in_nonce' => '',
				'is_going'     => true,
			],
		];

		// They choose to opt-in from success view.
		yield 'opt-in with failure because of bad nonce' => [
			'opt-in',
			[
				'opt_in'       => 1,
				// No opt_in_nonce passed so it causes a problem.
				'attendee_ids' => '',
				'is_going'     => true,
			],
			[
				'success' => false,
				'errors'  => [
					'Unable to verify your opt-in request, please try again.',
				],
			],
		];
	}

	/**
	 * It should render the RSVP step.
	 *
	 * @test
	 * @dataProvider provider_rsvp_steps
	 *
	 * @param string|null $step              The RSVP step.
	 * @param null|array  $post_data         The data to include in the $_POST.
	 * @param null|array  $expected_response Expected response if not HTML.
	 */
	public function it_should_render_rsvp_step( $step, $post_data = null, $expected_response = null ) {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];

		if ( null !== $post_data ) {
			if ( isset( $post_data['product_id'] ) ) {
				$post_data['product_id'] = $ticket_id;
			}

			if ( isset( $post_data['quantity'] ) ) {
				$post_data[ 'quantity_' . $ticket_id ] = $post_data['quantity'];
			}

			if ( isset( $post_data['attendee_ids'] ) ) {
				$attendee_ids = $sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'going' ] ), false );
				$attendee_ids = implode( ',', $attendee_ids );

				$post_data['attendee_ids'] = $attendee_ids;

				if ( isset( $post_data['opt_in_nonce'] ) ) {
					$nonce_action = 'tribe-tickets-rsvp-opt-in-' . md5( $attendee_ids );

					$post_data['opt_in_nonce'] = wp_create_nonce( $nonce_action );
				}
			}

			$_POST = $post_data;
		}

		$html = $sut->render_rsvp_step( $ticket_id, $step );

		if ( null !== $expected_response ) {
			self::assertEquals( $expected_response, $html );

			return;
		}

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$driver->setTolerableDifferences( [ $post_id, $ticket_id ] );
		$driver->setTolerableDifferencesPrefixes( [
			'quantity_',
			'tribe-tickets-rsvp-name-',
			'tribe-tickets-rsvp-email-',
		] );

		$driver->setTimeDependentAttributes( [
			'data-rsvp-id',
			'data-product-id',
			'data-attendee-ids',
			'data-opt-in-nonce',
		] );

		// Handle ticket ID variations that tolerances won't handle
		$html = str_replace(
			[
				'[' . $ticket_id . ']',
				'"' . $ticket_id . '"',
			],
			[
				'[TICKET_ID]',
				'"TICKET_ID"',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * Provider for RSVP steps process testing.
	 *
	 * @return \Generator
	 */
	public function provider_rsvp_steps_for_process() {
		// Initial state.
		yield 'initial state' => [
			null,
			[],
			[
				'success' => null,
				'errors'  => [],
			],
		];

		// They choose Going.
		yield 'going' => [
			'going',
			[],
			[
				'success' => null,
				'errors'  => [],
			],
		];

		// They choose Not Going.
		yield 'not going' => [
			'not-going',
			[],
			[
				'success' => null,
				'errors'  => [],
			],
		];

		// They need the ARI form.
		yield 'ari' => [
			'ari',
			[],
			[
				'success' => null,
				'errors'  => [],
			],
		];

		// They complete the RSVP process.
		yield 'success' => [
			'success',
			[
				'attendee'   => $this->fake_attendee_details(),
				'product_id' => 0,
				'quantity'   => 2,
			],
			[
				'success'     => true,
				'errors'      => [],
				'opt_in_args' => [
					'is_going' => true,
					'checked' => false,
					'attendee_ids' => 'non-empty',
					'opt_in_nonce' => 'non-empty',
				],
			],
		];

		// They complete the RSVP process.
		yield 'success with failure because of no data' => [
			'success',
			// Pass no data so that it won't process correctly.
			[],
			[
				'success' => false,
				'errors'  => [
					'Your RSVP was unsuccessful, please try again.',
				],
			],
		];

		// They choose to opt-in from success view.
		yield 'opt-in' => [
			'opt-in',
			[
				'opt_in'       => 1,
				'attendee_ids' => '',
				'opt_in_nonce' => '',
				'is_going'     => true,
			],
			[
				'success' => true,
				'errors'  => [],
				'opt_in_args' => [
					'is_going' => true,
					'checked' => true,
					'attendee_ids' => 'non-empty',
					'opt_in_nonce' => 'non-empty',
				],
			],
		];

		// They choose to opt-in from success view.
		yield 'opt-in to opt-out' => [
			'opt-in',
			[
				'opt_in'       => 0,
				'attendee_ids' => '',
				'opt_in_nonce' => '',
				'is_going'     => true,
			],
			[
				'success' => true,
				'errors'  => [],
				'opt_in_args' => [
					'is_going' => true,
					'checked' => false,
					'attendee_ids' => 'non-empty',
					'opt_in_nonce' => 'non-empty',
				],
			],
		];

		// They choose to opt-in from success view.
		yield 'opt-in with failure because of bad nonce' => [
			'opt-in',
			[
				'opt_in'       => 1,
				// No opt_in_nonce passed so it causes a problem.
				'attendee_ids' => '',
			],
			[
				'success' => false,
				'errors'  => [
					'Unable to verify your opt-in request, please try again.',
				],
			],
		];
	}

	/**
	 * It should process the RSVP step.
	 *
	 * @test
	 * @dataProvider provider_rsvp_steps_for_process
	 *
	 * @param string|null $step              The RSVP step.
	 * @param array       $post_data         The data to include in the $_POST.
	 * @param array       $expected_response Expected response if not successful.
	 */
	public function it_should_process_rsvp_step( $step, $post_data, $expected_response ) {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];

		if ( null !== $post_data ) {
			if ( isset( $post_data['product_id'] ) ) {
				$post_data['product_id'] = $ticket_id;
			}

			if ( isset( $post_data['quantity'] ) ) {
				$post_data[ 'quantity_' . $ticket_id ] = $post_data['quantity'];
			}

			if ( isset( $post_data['attendee_ids'] ) ) {
				$attendee_ids = $sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'going' ] ), false );
				$attendee_ids = implode( ',', $attendee_ids );

				$post_data['attendee_ids'] = $attendee_ids;

				if ( isset( $post_data['opt_in_nonce'] ) ) {
					$nonce_action = 'tribe-tickets-rsvp-opt-in-' . md5( $attendee_ids );

					$post_data['opt_in_nonce'] = wp_create_nonce( $nonce_action );
				}
			}

			$_POST = $post_data;
		}

		$args = [
			'rsvp_id' => $ticket_id,
			'post_id' => $post_id,
			'step'    => $step,
		];

		$process_result = $sut->process_rsvp_step( $args );

		if ( isset( $expected_response['opt_in_args'], $process_result['opt_in_args'] ) ) {
			$process_result['opt_in_args'] = array_merge( $process_result['opt_in_args'], $expected_response['opt_in_args'] );
		}

		self::assertEquals( $expected_response, $process_result );
	}

	/**
	 * It should render the RSVP error.
	 *
	 * @test
	 */
	public function it_should_render_rsvp_error() {
		$sut = $this->make_instance();

		$html = $sut->render_rsvp_error( 'There was an error here' );

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * It should render the RSVP error with an array of messages.
	 *
	 * @test
	 */
	public function it_should_render_rsvp_error_with_an_array() {
		$sut = $this->make_instance();

		$html = $sut->render_rsvp_error( [
			'There was an error here',
			'There was an error there too',
			'There was an error over on the other side too',
		] );

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @return mixed
	 */
	protected function setup_POST( $status, $sales ) {
		$_POST['tickets_process']       = true;
		$_POST['attendee']              = [
			'email'        => 'me@tri.be',
			'full_name'    => 'Me',
			'order_status' => $status,
		];
		$post_id                        = $this->factory()->post->create();
		$ticket_id                      = $this->make_sales_ticket( $sales, $post_id );
		$_POST["quantity_{$ticket_id}"] = 1;

		return $ticket_id;
	}

	protected function unset_POST() {
		unset( $_POST['tickets_process'] );
		unset( $_POST['attendee'] );
		unset( $_POST['product_id'] );
		// quantity_ID not relevant
	}

	protected function make_data( $previous_status, $status, $sales = 0, $stock = 0 ) {
		$base_data = $this->make_base_data( $sales, $stock );
		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		// mock the already placed order
		$order_id = $this->factory()->post->create( [
				'meta_input' => [
					RSVP::ATTENDEE_RSVP_KEY => $previous_status,
				],
			] );

		// mock the current user
		wp_set_current_user( $user_id );

		// mock already present attendees
		$rsvp_options = $this->tickets_view->get_rsvp_options( null, false );

		/** @var Tickets_View $tickets_view */
		$tickets_view = $this->prophesize( Tickets_View::class );
		$tickets_view->get_rsvp_options( null, false )->willReturn( $rsvp_options );
		$tickets_view->get_event_rsvp_attendees( $post_id, $user_id )->willReturn( [
			[
				'product_id' => $ticket_id,
				'order_id'   => $order_id,
			],
		] );

		// not restricted
		$tickets_view->is_rsvp_restricted( Argument::type( 'int' ), Argument::type( 'int' ) )->willReturn( false );
		$tickets_view->is_valid_rsvp_option( Argument::type( 'string' ) )->willReturn( true );
		$this->tickets_view = $tickets_view->reveal();

		// mock the submission data
		$data = [
			'email'        => 'me@tri.be',
			'full_name'    => 'Me',
			'order_status' => $status,
		];

		return [ $data, $ticket_id, $order_id, $post_id ];
	}

	protected function make_base_data( $sales = 0, $stock = 10 ) {
		$post_id = $this->factory()->post->create();

		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => $sales,
				'_stock'      => $stock,
				'_capacity'   => $stock + $sales,
			],
		] );

		$user_id = $this->factory()->user->create();

		return [
			'post_id'   => $post_id,
			'ticket_id' => $ticket_id,
			'user_id'   => $user_id,
		];
	}

	protected function make_sales_ticket( $sales, $post_id ) {
		$stock = 10;

		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => $sales,
				'_capacity'   => $stock + $sales,
				'_stock'      => $stock,
			],
		] );

		$_POST['product_id'] = $ticket_id;

		return $ticket_id;
	}

	protected function make_stock_ticket( $stock, $post_id ) {
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => $stock,
				'_stock'      => $stock,
				'total_sales' => 0,
			],
		] );

		$_POST['product_id'] = $ticket_id;

		return $ticket_id;
	}

	protected function fake_attendee_details( array $overrides = [] ) {
		return array_merge( [
			'full_name'    => 'Jane Doe',
			'email'        => 'jane@doe.com',
			'order_status' => 'yes',
			'optout'       => 'no',
			'order_id'     => RSVP::generate_order_id(),
		], $overrides );
	}
}
