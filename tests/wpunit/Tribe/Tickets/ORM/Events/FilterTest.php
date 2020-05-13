<?php

namespace Tribe\Tickets\ORM\Events;

use Tribe\Tickets\Test\Commerce\ORM\EventsTestCase;

/**
 * Class FilterTest
 * @package Tribe\Tickets\ORM\Events
 *
 * @see \tribe_events() What all these tests are for.
 * @see \Tribe__Tickets__Event_Repository The custom filters we are testing.
 */
class FilterTest extends EventsTestCase {

	/**
	 * @dataProvider get_event_test_matrix
	 */
	public function test_events_orm_filters( $method ) {
		list( $repository, $filter_name, $filter_arguments, $assertions ) = $this->$method();

		// Setup events.
		$events = tribe_events();

		// Enable found() calculations.
		$events->set_found_rows( true );

		// Do the filtering.
		$args = array_merge( [ $filter_name ], $filter_arguments );

		$events->by( ...$args );

		if ( 'attendee_user__not_in' === $filter_name ) {
			codecept_debug( var_export( $args, true ) );
			codecept_debug( var_export( $assertions['get_ids'], true ) );
			codecept_debug( var_export( $events->get_ids(), true ) );
			codecept_debug( var_export( $this->test_data, true ) );
			codecept_debug( $events->get_last_built_query()->request );
		}

		// Assert that we get what we expected.

		/**
		 * Same as `all()` except only an array of Post IDs, not full Post objects.
		 * Is affected by pagination, but ORM defaults to unlimited.
		 *
		 * We use the 'canonicalize' argument to compare arrays as sorted so the order returned doesn't matter.
		 *
		 * @see \Tribe__Repository::get_ids()
		 */
		$this->assertEquals( $assertions['get_ids'], $events->get_ids(), $method, 0.0, 10, true );

		/**
		 * The total number of posts found matching the current query parameters.
		 * Is affected by pagination, but ORM defaults to unlimited.
		 *
		 * We use the 'canonicalize' argument to compare arrays as sorted so the order returned doesn't matter.
		 *
		 * @see \Tribe__Repository::all() Runs get_posts() then format_item().
		 * @see \WP_Query::get_posts()
		 * @see \Tribe__Repository::format_item()
		 */
		$this->assertEquals( $assertions['all'], $events->all(), $method, 0.0, 10, true );

		/**
		 * @see \Tribe__Repository::count() WP_Query's `post_count`:
		 *      The number of posts being displayed. Is affected by pagination but ORM defaults to unlimited.
		 */
		$this->assertEquals( $assertions['count'], $events->count(), $method );

		/**
		 * The total number of posts found matching the current query parameters.
		 * Is NOT affected by pagination.
		 *
		 * @see \Tribe__Repository::found() WP_Query's `found_posts`.
		 */
		$this->assertEquals( $assertions['found'], $events->found(), $method );
	}
}