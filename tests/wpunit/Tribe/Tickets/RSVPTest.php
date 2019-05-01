<?php

namespace Tribe\Tickets;

use Prophecy\Argument;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Tickets_View as Tickets_View;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class RSVPTest extends \Codeception\TestCase\WPTestCase {

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
	public function it_should_decrease_stock_by_1_when_an_attendee_is_going() {
		$post_id   = $this->factory->post->create();
		$ticket_id = $this->make_stock_ticket( 10, $post_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

		$this->assertEquals( 9, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @test
	 * it should increase sales by 1 when an attendee is going
	 */
	public function it_should_increase_sales_by_1_when_an_attendee_is_going() {
		$post_id   = $this->factory->post->create();
		$ticket_id = $this->make_sales_ticket( 10, $post_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

		$this->assertEquals( 11, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should not increase sales when an attendee is not going
	 */
	public function it_should_not_increase_sales_when_an_attendee_is_not_going() {
		$post_id   = $this->factory->post->create();
		$ticket_id = $this->make_sales_ticket( 10, $post_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'no' ] ) );

		$this->assertEquals( 10, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should not increase sales when an attendee is not going
	 *
	 * @since 4.7.4
	 */
	public function it_should_not_decrease_stock_when_an_attendee_is_not_going() {
		$post_id   = $this->factory->post->create();
		$ticket_id = $this->make_stock_ticket( 10, $post_id );

		$sut = $this->make_instance();
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( [ 'order_status' => 'no' ] ) );

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
		$sut->generate_tickets();

		$this->assertEquals( 12, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should decrease sales by 1 when attendee status changes from going to not going
	 */
	public function it_should_decrease_sales_by_1_when_attendee_status_changes_from_going_to_not_going() {
		list( $data, $ticket_id, $order_id, $post_id ) = $this->make_data( 'yes', 'no', 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $post_id );

		$this->assertEquals( 9, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should decrease sales by 1 when attendee status changes from going to not going
	 *
	 * @since 4.7.4
	 */
	public function it_should_increase_stock_by_1_when_attendee_status_changes_from_going_to_not_going() {
		list( $data, $ticket_id, $order_id, $post_id ) = $this->make_data( 'yes', 'no', 1, 9 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $post_id );

		$this->assertEquals( 10, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @test
	 * it should increase sales by 1 when attendee status changes from not going to going
	 */
	public function it_should_increase_sales_by_1_when_attendee_status_changes_from_not_going_to_going() {
		list( $data, $ticket_id, $order_id, $post_id ) = $this->make_data( 'no', 'yes', 0, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $post_id );

		$this->assertEquals( 1, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * @test
	 * it should increase sales by 1 when attendee status changes from not going to going
	 *
	 * @since 4.7.4
	 */
	public function it_should_decrease_stock_by_1_when_attendee_status_changes_from_not_going_to_going() {
		list( $data, $ticket_id, $order_id, $post_id ) = $this->make_data( 'no', 'yes', 0, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $post_id );

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

		list( $data, $ticket_id, $order_id, $post_id ) = $this->make_data( $old_status, $new_status, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $post_id );

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

		list( $data, $ticket_id, $order_id, $post_id ) = $this->make_data( $old_status, $new_status, 0, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $post_id );

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

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

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

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

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

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

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

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

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

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

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

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

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

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

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

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

		$this->assertEquals( 10, $sut->get_attendees_count( $post_id ) );
	}

	/**
	 * @test
	 *
	 * It should return count from get_attendees_count_by_user().
	 */
	public function it_should_return_count_from_get_attendees_count_by_user() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

		// Generate some tickets while logged in.
		wp_set_current_user( $user_id );

		$sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

		$this->assertEquals( 5, $sut->get_attendees_count_by_user( $post_id, $user_id ) );
	}

	/**
	 * @test
	 *
	 * It should return count from get_attendees_count_going().
	 */
	public function it_should_return_count_from_get_attendees_count_going() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );
		$sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'no' ] ) );

		$this->assertEquals( 10, $sut->get_attendees_count_going( $post_id ) );
	}

	/**
	 * @test
	 *
	 * It should return count from get_attendees_count_not_going().
	 */
	public function it_should_return_count_from_get_attendees_count_not_going() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut->generate_tickets_for( $ticket_id, 10, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );
		$sut->generate_tickets_for( $ticket_id, 5, $this->fake_attendee_details( [ 'order_status' => 'no' ] ) );

		$this->assertEquals( 5, $sut->get_attendees_count_not_going( $post_id ) );
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

	protected function make_data( $previous_status, $status, $sales, $stock = 0 ) {
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
				'_capacity' => $stock,
				'_stock'    => $stock,
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
