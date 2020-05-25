<?php

namespace Tribe\Tickets\RSVP\Early_Access;

class Early_Access_Update_Notice_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp() {
		// Before...
		parent::setUp();

		// Your set up methods here.
		$this->as_user();
	}

	public function tearDown() {
		// Your tear down methods here.

		// Then...
		parent::tearDown();
	}

	private function make_instance(): Update_Notice {
		return clone tribe( Update_Notice::class );
	}

	private function as_user( bool $with_cap = true ) {
		$user = new \WP_User( wp_create_user( wp_generate_password(), wp_generate_password() ) );

		if ( $with_cap ) {
			$user->add_cap( \Tribe__Settings::instance()->requiredCap );
		} else {
			$user->remove_cap( \Tribe__Settings::instance()->requiredCap );
		}

		set_current_user( $user->ID );
	}

	private function set_minimum_version( string $minimum_version ) {
		add_filter(
			'tribe_tickets_min_version_to_show_rsvp_early_access_update_notice',
			function () use ( $minimum_version ) {
				return $minimum_version;
			}
		);
	}

	/** @test */
	public function should_enqueue_notice_if_early_access() {
		add_filter( 'tribe_tickets_is_rsvp_early_access', function () {
			return true;
		} );

		$this->set_minimum_version('100');

		$should_display = $this->make_instance()->maybe_display_update_notice();

		$this->assertTrue( $should_display );
	}

	/** @test */
	public function should_not_enqueue_notice_if_not_early_access() {
		add_filter( 'tribe_tickets_is_rsvp_early_access', function () {
			return false;
		} );

		$this->set_minimum_version('100');

		$should_display = $this->make_instance()->maybe_display_update_notice();

		$this->assertFalse( $should_display );
	}

	/** @test */
	public function should_not_enqueue_notice_if_bellow_minimum_version() {
		$this->set_minimum_version('100');

		$should_display = $this->make_instance()->maybe_display_update_notice();

		$this->assertFalse( $should_display );
	}

	/** @test */
	public function should_enqueue_notice_if_has_minimum_version() {
		$this->set_minimum_version( \Tribe__Tickets__Main::VERSION );

		$should_display = $this->make_instance()->maybe_display_update_notice();

		$this->assertTrue( $should_display );
	}

	/** @test */
	public function should_enqueue_notice_if_above_minimum_version() {
		$this->set_minimum_version( '1.0' );

		$should_display = $this->make_instance()->maybe_display_update_notice();

		$this->assertTrue( $should_display );
	}

	/** @test */
	public function should_enqueue_notice_if_user_does_not_have_caps() {
		$this->as_user( false );
		$this->set_minimum_version( '1.0' );

		$should_display = $this->make_instance()->maybe_display_update_notice();

		$this->assertFalse( $should_display );
	}

}
