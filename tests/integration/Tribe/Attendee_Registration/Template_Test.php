<?php

use Tribe__Tickets__Attendee_Registration__Template;
use Tribe__Tickets__Attendee_Registration__Main;
use Tribe\Tests\Traits\With_Uopz;

class Template_Test extends \Codeception\TestCase\WPTestCase {
	use With_Uopz;

	/**
	 * @before
	 */
	public function setup_singletons() {
		tribe()->singleton( 'tickets.attendee_registration.template', new Tribe__Tickets__Attendee_Registration__Template() );
		tribe()->singleton( 'tickets.attendee_registration', new Tribe__Tickets__Attendee_Registration__Main() );
	}

	/**
	 * @test
	 *
	 * @dataProvider ar_page_data
	 */
	public function it_should_return_true_if_on_custom_ar_page_with_shortcode( $wp_query_update, $post_update ) {
		global $wp_query, $post, $shortcode_tags;

		uopz_set_return( 'get_queried_object', $wp_query_update->queried_object );
		uopz_set_return( Tribe__Tickets__Attendee_Registration__Main::class, 'get_slug', 'attendee-registration' );

		$template = tribe( 'tickets.attendee_registration.template' );

		// Set globals.
		$wp_query       = $wp_query_update;
		$post           = $post_update;
		$shortcode_tags = [ 'tribe_attendee_registration' => 'tribe_attendee_registration' ];

		$this->assertTrue( $template->is_on_custom_ar_page() );
	}

	/**
	 * Data provider for on custom ar page test.
	 *
	 * @return array
	 */
	public function ar_page_data() {
		$wp_query_blank_queried_object = (object) [
			'query_vars' => [
				'pagename' => 'attendee-registration'
			],
			'queried_object' => (object) [
				'post_content' => ''
			]
		];
		$post_with_shortcode = (object) [
			'post_content' => '[tribe_attendee_registration]'
		];
		$wp_query_queried_object = (object) [
			'query_vars' => [
				'pagename' => 'attendee-registration'
			],
			'queried_object' => (object) [
				'post_content' => '[tribe_attendee_registration]'
			]
		];
		$post_no_shortcode = (object) [
			'post_content' => ''
		];

		return [
			[ $wp_query_blank_queried_object, $post_with_shortcode ],
			[ $wp_query_queried_object, $post_no_shortcode ],
		];
	}
}
