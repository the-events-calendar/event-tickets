<?php

namespace Tribe\Tickets\RSVP\Early_Access;

class Early_Access_Template_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/** @var Early_Access */
	private $early_access;

	/** @var Template */
	private $template;

	public function setUp() {
		// Before...
		parent::setUp();

		$this->early_access = clone tribe( Early_Access::class );
		$this->template     = clone tribe( Template::class );
	}

	public function tearDown() {
		// Your tear down methods here.

		// Then...
		parent::tearDown();
	}

	/** @test */
	public function should_change_rsvp_template_if_early_access() {
		$this->early_access->set_rsvp_early_access( true );

		$template = $this->template->override_template( 'foo/rsvp/bar/rsvp.php' );

		$this->assertEquals( 'foo/rsvp/bar/rsvp-early-access.php', $template );
	}
}
