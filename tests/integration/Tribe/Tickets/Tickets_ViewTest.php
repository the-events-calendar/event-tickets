<?php
namespace Tribe\Tickets;

use Tribe__Tickets__Tickets_View as Tickets_View;

class Tickets_ViewTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
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

		$this->assertInstanceOf( Tickets_View::class, $sut );
	}

	/**
	 * @return Tickets_View
	 */
	private function make_instance() {
		return new Tickets_View();
	}

	/**
	 * @test
	 * it should allow registering new RSVP states specifying label only
	 *
	 * The "old" way should still work.
	 */
	public function it_should_allow_registering_new_rsvp_states_specifying_label_only() {
		$rsvp_options = [
			'yes-plus-one'    => 'Going +1',
			'yes-plus-family' => 'Going +family',
			'yes-with-mt'     => 'Going (with MT)',
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		foreach ( $rsvp_options as $rsvp_option => $label ) {
			$this->assertArrayHasKey( $rsvp_option, $options );
			$this->assertEquals( $label, $options[ $rsvp_option ]['label'] );
		}
	}

	/**
	 * @test
	 * it should default the decrease_stock_by arg to 1 if not passed
	 */
	public function it_should_default_the_decrease_stock_by_arg_to_1_if_not_passed() {
		$rsvp_options = [
			'yes-plus-one'    => 'Going +1',
			'yes-plus-family' => 'Going +family',
			'yes-with-mt'     => 'Going (with MT)',
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		foreach ( $rsvp_options as $rsvp_option => $label ) {
			$this->assertArrayHasKey( $rsvp_option, $options );
			$this->assertEquals( 1, $options[ $rsvp_option ]['decrease_stock_by'] );
		}
	}

	/**
	 * @test
	 * it should prune RSVP options that do not have right format
	 */
	public function it_should_prune_rsvp_options_that_do_not_have_right_format() {
		$rsvp_options = [
			// good
			'yes-plus-one'    => [ 'label' => 'Going +1', 'decrease_stock_by' => 2 ],
			// good even without stock
			'maybe'           => [ 'label' => 'Maybe' ],
			// no label
			'yes-plus-family' => [ 'Going +family' ],
			// ok stock but no label
			'yes-with-mt'     => [ 'Going (with MT)', 'decrease_stock_by' => 0 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		foreach ( [ 'yes-plus-one', 'maybe' ] as $rsvp_option ) {
			$this->assertArrayHasKey( $rsvp_option, $options );
		}
		foreach ( [ 'yes-plus-family', 'yes-with-mt' ] as $bad_rsvp_option ) {
			$this->assertArrayNotHasKey( $bad_rsvp_option, $options );
		}
	}

	/**
	 * @test
	 * it should allow decrease_stock_by zero values
	 */
	public function it_should_allow_decrease_stock_by_zero_values() {
		$rsvp_options = [
			'maybe' => [ 'label' => 'Maybe', 'decrease_stock_by' => 0 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'maybe', $options );
		$this->assertEquals( 0, $options['maybe']['decrease_stock_by'] );
	}

	/**
	 * @test
	 * it should not allow options to have a negative decrease_stock_by value
	 */
	public function it_should_allow_options_to_have_a_negative_decrease_stock_by_value() {
		$rsvp_options = [
			'plus-one'           => [ 'label' => 'Plus one', 'decrease_stock_by' => 2 ],
			'not-going-plus-one' => [ 'label' => 'Not going plus one', 'decrease_stock_by' => - 2 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'plus-one', $options );
		$this->assertArrayNotHasKey( 'not-going-plus-one', $options );
		$this->assertEquals( 2, $options['plus-one']['decrease_stock_by'] );
	}

	/**
	 * @test
	 * it should not allow non int decrease_stock_by values
	 */
	public function it_should_not_allow_non_int_decrease_stock_by_values() {
		$rsvp_options = [
			'maybe' => [ 'label' => 'Maybe', 'decrease_stock_by' => .5 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayNotHasKey( 'maybe', $options );
	}

	/**
	 * @test
	 * it should mark the default Going option to decrease_stock_by 1
	 */
	public function it_should_mark_the_default_going_option_to_decrease_stock_by_1() {
		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'yes', $options );
		$this->assertEquals( 1, $options['yes']['decrease_stock_by'] );
	}

	/**
	 * @test
	 * it should mark default Not Going option to decrease stock by 0
	 */
	public function it_should_mark_default_not_going_option_to_decrease_stock_by_0() {
		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'no', $options );
		$this->assertEquals( 0, $options['no']['decrease_stock_by'] );
	}
}