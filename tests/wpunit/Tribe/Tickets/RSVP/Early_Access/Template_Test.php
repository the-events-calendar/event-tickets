<?php

namespace Tribe\Tickets\RSVP\Early_Access;

class Early_Access_Template_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/** @var Template */
	private $template;

	public function setUp() {
		// Before...
		parent::setUp();

		$this->template     = clone tribe( Template::class );
	}

	public function tearDown() {
		// Your tear down methods here.

		// Then...
		parent::tearDown();
	}

	/** @test */
	public function should_change_rsvp_template_if_early_access() {
		add_filter( 'tribe_tickets_is_rsvp_early_access', function() {
			return true;
		} );

		$template = $this->template->override_template( 'foo/rsvp/bar/rsvp.php' );

		$this->assertEquals( 'foo/rsvp/bar/rsvp-early-access.php', $template );
	}
}
