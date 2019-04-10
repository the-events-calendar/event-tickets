<?php
namespace Tribe\Tickets;

use Prophecy\Argument;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Tickets_View as Tickets_View;

class RSVPTest extends \Codeception\TestCase\WPTestCase {

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
		add_filter (
			'tribe_exit', function () {
				return [ $this, 'dont_die' ];
			}
		);

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
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( ['order_status' => 'yes'] ) );

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
		$sut->generate_tickets_for( $ticket_id, 1, $this->fake_attendee_details( ['order_status' => 'yes'] ) );

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
		add_filter(
			'event_tickets_rsvp_options', function ( $options ) {
			return array_merge(
				$options, [
					        'yes-plus-one' => [ 'label' => 'Yes plus one', 'decrease_stock_by' => 2 ],
				        ]
			);
		}
		);

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
	 * @since 4.7.4
	 */
	public function it_should_update_the_sales_according_to_old_and_new_status_stock_sizes( $a_status_stock_size, $b_status_stock_size, $old_status, $new_status, $diff ) {
		$stati = [
			'a' => [ 'label' => 'a', 'decrease_stock_by' => $a_status_stock_size ],
			'b' => [ 'label' => 'b', 'decrease_stock_by' => $b_status_stock_size ],
		];

		add_filter(
			'event_tickets_rsvp_options', function ( $options ) use ( $stati ) {
			return array_merge( $options, $stati );
		}
		);

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

		add_filter(
			'event_tickets_rsvp_options', function ( $options ) use ( $stati ) {
			return array_merge( $options, $stati );
		}
		);

		list( $data, $ticket_id, $order_id, $post_id ) = $this->make_data( $old_status, $new_status, 0, 10 );

		$sut = $this->make_instance();
		$sut->update_attendee_data( $data, $order_id, $post_id );

		// stock cannot exceed capacity
		$expected_stock = min( 10, 10 - $diff );

		$this->assertEquals( $expected_stock, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @return mixed
	 */
	protected function setup_POST( $status, $sales ) {
		$_POST['tickets_process']       = true;
		$_POST['attendee']              = [
			'email'        => 'me@tri.be',
			'full_name'    => 'Me',
			'order_status' => $status
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
		$post_id = $this->factory()->post->create();

		// mock the already placed order
		$order_id = $this->factory()->post->create(
			[
				'meta_input' => [
					RSVP::ATTENDEE_RSVP_KEY => $previous_status,
				]
			]
		);

		$ticket_id = $this->factory()->post->create(
			[
				'post_type'   => 'tribe_rsvp_tickets',
				'post_status' => 'publish',
				'meta_input'  => [
					'total_sales'            => $sales,
					'_tribe_rsvp_for_event'  => $post_id,
					'_stock'                 => $stock,
					'_tribe_ticket_capacity' => $stock + $sales,
				]
			]
		);

		// mock the current user
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );

		// mock already present attendees
		$rsvp_options = $this->tickets_view->get_rsvp_options( null, false );
		$tickets_view = $this->prophesize( Tickets_View::class );
		$tickets_view->get_rsvp_options( null, false )->willReturn( $rsvp_options );
		$tickets_view->get_event_rsvp_attendees( $post_id, $user_id )->willReturn(
			[
				[ 'product_id' => $ticket_id, 'order_id' => $order_id ]
			]
		);

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

	protected function make_sales_ticket( $sales, $post_id ) {
		$ticket_id = $_POST['product_id'] = $this->factory()->post->create(
			[
				'post_type'   => 'tribe_rsvp_tickets',
				'post_status' => 'publish',
				'meta_input'  => [
					'total_sales'           => $sales,
					'_tribe_rsvp_for_event' => $post_id,
				]
			]
		);

		return $ticket_id;
	}

	protected function make_stock_ticket( $stock, $post_id ) {
		$ticket_id = $_POST['product_id'] = $this->factory()->post->create(
			[
				'post_type'   => 'tribe_rsvp_tickets',
				'post_status' => 'publish',
				'meta_input'  => [
					'_stock'                => $stock,
					'_tribe_rsvp_for_event' => $post_id,
				]
			]
		);

		return $ticket_id;
	}

	protected function fake_attendee_details(array $overrides = array()) {
		return array_merge( array(
			'full_name'    => 'Jane Doe',
			'email'        => 'jane@doe.com',
			'order_status' => 'yes',
			'optout'       => 'no',
			'order_id'     => RSVP::generate_order_id(),
		), $overrides );
	}
}
