<?php

namespace Tribe\Tickets\Attendee_Info;

use Tribe__Tickets__Attendee_Info__View as View;

class ViewTest extends \Codeception\TestCase\WPTestCase {

	protected $exited = false;

	public function setUp() {
		parent::setUp();

		add_filter( 'tribe_exit', function () {
			$this->exited = true;
			return [ $this, 'dont_die' ];
		} );
	}

	public function dont_die() {
		// no-op, go on
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( View::class, $sut );
	}

	/**
	 * @return View
	 */
	private function make_instance() {
		return new View();
	}

	/**
	 * @test
	 * it should load the correct template when query var is set
	 */
	public function it_should_load_the_correct_template_when_query_var_is_set() {
		global $wp_query;

		$wp_query->query_vars['attendee-info'] = true;

		$view = new View();

		$view->display_attendee_info_page();

		$this->assertTrue( $this->exited );
	}

	/**
	 * @test
	 * it should load the default template when query var is not set
	 */
	public function it_should_load_the_default_template_when_query_var_is_not_set() {
		global $wp_query;

		$wp_query->query_vars['attendeeInfo'] = false;

		$view = new View();

		$view->display_attendee_info_page();

		$this->assertFalse( $this->exited );
	}

}
