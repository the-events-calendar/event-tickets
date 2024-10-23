<?php

use Tribe__Tickets__Attendee_Registration__Template as Template;

class Template_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @test
	 *
	 * @dataProvider ar_page_data
	 */
	public function it_should_return_true_if_on_custom_ar_page_with_shortcode( $wp_query_update, $post_update ) {
		global $wp_query, $post;

		$template = new Template();

		$wp_query = $wp_query_update;
		$post = $post_update;

		$this->assertTrue( $template->is_on_custom_ar_page() );
	}

	/**
	 * Data provider for on custom ar page test.
	 *
	 * @return array
	 */
	public function ar_page_data() {
		$wp_query_no_queried_object = (object) [
			'query_vars' => [
				'pagename' => 'attendee-registration'
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
			[ $wp_query_no_queried_object, $post_with_shortcode ],
			[ $wp_query_queried_object, $post_no_shortcode ],
		];
	}
}
