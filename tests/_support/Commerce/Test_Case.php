<?php

namespace Tribe\Tickets\Test\Commerce;

use Codeception\TestCase\WPTestCase;

/**
 * Class Test_Case
 *
 * @package Tribe\Tickets\Test\Commerce
 */
class Test_Case extends WPTestCase {

	/**
	 * Overrides the base setUp method to make sure we're starting from a database clean of any posts.
	 */
	function setUp() {
		parent::setUp();

		$this->remove_all_posts();
	}

	/**
	 * Removes all the posts any test method might have left in the database.
	 *
	 * In the context of normal test cases and methods this should be not needed as any query done
	 * via the global `$wpdb` object is rolled back in the `Codeception\TestCase\WPTestCase::tearDown` method.
	 * Some factories we're using, like the EDD and WOO ones are creating some posts in another PHP thread (due
	 * to plugin internals) and those posts would not be rolled back.
	 */
	protected function remove_all_posts() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE 1=1" );
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE 1=1" );
	}

	/**
	 * Overrides the base tearDown method to make sure we're leaving a database clean of any posts.
	 */
	function tearDown() {
		$this->remove_all_posts();

		parent::tearDown();
	}
}