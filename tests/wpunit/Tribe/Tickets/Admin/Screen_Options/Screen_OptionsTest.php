<?php
namespace Tribe\Tickets\Admin;

use Tribe__Tickets__Admin__Screen_Options__Attendees as Attendees;

class Screen_OptionsTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WP_Screen
	 */
	protected $screen;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		// because the WP_Screen class is final we mock `WP_Screen` with a simple object
		$this->screen = new \stdClass;
		// and we set a property on it
		$this->screen->id = 'foo-page';
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

		$this->assertInstanceOf( Attendees::class, $sut );
	}

	/**
	 * @return Attendees
	 */
	private function make_instance() {
		return new Attendees( 'foo-page', $this->screen );
	}

	/**
	 * @test
	 * it should not add screen options if the screen is not an object
	 */
	public function it_should_not_add_screen_options_if_the_screen_is_not_an_object() {
		$this->screen = 'not-an-object';
		$sut          = $this->make_instance();

		$added = $sut->add_options();

		$this->assertFalse( $added );
	}

	/**
	 * @test
	 * it should not add screen options if the screen is not the expected one
	 */
	public function it_should_not_add_screen_options_if_the_screen_is_not_the_expected_one() {
		$this->screen->id = 'bar';
		$sut              = $this->make_instance();

		$added = $sut->add_options();

		$this->assertFalse( $added );
	}

	/**
	 * @test
	 * it should add the column options if on screen
	 */
	public function it_should_add_the_column_options_if_on_screen() {
		$sut = $this->make_instance();

		$added = $sut->add_options();

		$this->assertTrue( $added );
		$this->assertNotFalse( has_filter( "manage_foo-page_columns", [ $sut, 'filter_manage_columns' ] ) );
	}

}
