<?php

namespace Tribe\Tickets\Attendee_Info;

use Codeception\Util\Debug;
use Tribe__Tickets__Attendee_Info__Rewrite as Rewrite;

class RewriteTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {
		parent::setUp();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Rewrite::class, $sut );
	}

	/**
	 * @return Rewrite
	 */
	private function make_instance() {
		return new Rewrite();
	}

	/**
	 * @test
	 * it should add rules to the wp_rewrite rules
	 */
	public function it_should_add_rules_to_the_wp_rewrite_rules() {
		/**
		 * @var \WP_Rewrite
		 */
		global $wp_rewrite;

		$wp_rewrite->permalink_structure = '/%postname%/';
		$wp_rewrite->rewrite_rules();

		$original_rules = $wp_rewrite->rules;

		$rewrite = $this->make_instance();
		$rewrite->filter_generate( $wp_rewrite );

		$new_rules = $original_rules + $rewrite->rules;

		$this->assertEquals( $wp_rewrite->rules, $new_rules );
	}

	/**
	 * @test
	 * it should add the defined rewrite rules
	 */
	public function it_should_add_the_defined_rewrite_rules() {
		$rewrite = $this->make_instance();

		$rewrite->generate_core_rules( $rewrite );

		$this->assertArrayHasKey( '{{ attendee-info }}/?$', $rewrite->rules );
	}

	/**
	 * @test
	 * it should parse the defined rewrite rules
	 */
	public function it_should_parse_the_defined_rewrite_rules() {
		$rewrite = $this->make_instance();

		$rewrite->setup();
		$rewrite->generate_core_rules( $rewrite );

		$this->assertArrayHasKey( '(?:attendee\-info)/?$', $rewrite->rules );
	}

	/**
	 * @test
	 * it should use the user provided slug
	 */
	public function it_should_use_the_user_provided_slug() {
		$rewrite = $this->make_instance();

		\Tribe__Settings_Manager::set_option( 'ticket-attendee-info-slug', 'foo-bar' );

		$rewrite->setup();
		$rewrite->generate_core_rules( $rewrite );

		$this->assertArrayHasKey( '(?:foo\-bar)/?$', $rewrite->rules );
	}

}
