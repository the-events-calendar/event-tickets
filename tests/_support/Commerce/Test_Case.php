<?php

namespace Tribe\Tickets\Test\Commerce;

use Codeception\TestCase\WPTestCase;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

/**
 * Class Test_Case
 *
 * @package Tribe\Tickets\Test\Commerce
 */
class Test_Case extends WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * The array of generated data.
	 *
	 * @see setup_test_data()
	 *
	 * @var array
	 */
	public $test_data = [];

	/**
	 * Overrides the base setUp method to make sure we're starting from a database clean of any posts.
	 */
	public function setUp() {
		parent::setUp();

		$this->remove_all_posts();

		$this->factory()->event = new Event();

		// Enable post as ticket type.
		add_filter(
			'tribe_tickets_post_types', function () {
			return [ 'post' ];
		}
		);

		// Let's avoid confirmation emails.
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter(
			'tribe_tickets_get_modules', function ( $modules ) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
			$paypal = tribe( 'tickets.commerce.paypal' );

			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = $paypal->plugin_name;

			return $modules;
		}
		);

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		// Setup test data here.
		$this->setup_test_data();
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

	/**
	 * Helpers
	 */

	/**
	 * Given an array index key, get its value from the array of Events.
	 *
	 * @param int $index
	 *
	 * @return int
	 */
	public function get_event_id( $index ) {
		if ( isset( $this->test_data['events'][ $index ] ) ) {
			return $this->test_data['events'][ $index ];
		}

		return 0;
	}

	/**
	 * Given an array index key, get its value from the array of Attendees.
	 *
	 * @param int $index
	 *
	 * @return int
	 */
	public function get_attendee_id( $index ) {
		if ( isset( $this->test_data['attendees_all'][ $index ] ) ) {
			return $this->test_data['attendees_all'][ $index ];
		}

		return 0;
	}

	/**
	 * Given an array index key, get its value from the array of Users.
	 *
	 * @param int $index
	 *
	 * @return int
	 */
	public function get_user_id( $index ) {
		if ( isset( $this->test_data['users'][ $index ] ) ) {
			return $this->test_data['users'][ $index ];
		}

		return 0;
	}

	/**
	 * Given an array index key, get its value from the array of RSVP Tickets.
	 *
	 * @param int $index
	 *
	 * @return int
	 */
	public function get_rsvp_ticket_id( $index ) {
		if ( isset( $this->test_data['rsvp_tickets'][ $index ] ) ) {
			return $this->test_data['rsvp_tickets'][ $index ];
		}

		return 0;
	}

	/**
	 * Given an array index key, get its value from the array of PayPal Tickets.
	 *
	 * @param int $index
	 *
	 * @return int
	 */
	public function get_paypal_tickets_id( $index ) {
		if ( isset( $this->test_data['paypal_tickets'][ $index ] ) ) {
			return $this->test_data['paypal_tickets'][ $index ];
		}

		return 0;
	}

	/**
	 * Setup list of test data.
	 *
	 * We create 4 events, 1st and 3rd having same author, and various types of tickets that have attendees,
	 * and 2nd and 4th having neither an author nor tickets (and therefore no attendees).
	 * Some ticket purchases are by valid users and others are by non-users (site guests as attendees).
	 * Event 1 has:
	 * - User1 is author
	 * - User2 is RSVP attendee
	 * - User3 is RSVP attendee and PayPal attendee
	 * - User4 is PayPal attendee
	 * - So 1 RSVP ticket having 4 attendees (2 guests) and 1 PayPal ticket having 4 attendees (2 guests)
	 *   for a total of 8 attendees
	 * - And 3 RSVP tickets and 3 PayPal tickets, each having zero attendees
	 * Event 2 has: no author, no tickets (therefore no attendees)
	 * Event 3 has:
	 * - User1 is author
	 * - User2 is RSVP attendee
	 * Event 4 has: User5 as author, no tickets (therefore no attendees)
	 * Note that guest purchasers will still have User ID# zero saved to `_tribe_tickets_attendee_user_id` meta field.
	 */
	public function setup_test_data() {
		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		$test_data = [
			// 5 total: 1&5 = Event author, not Attendee; 2 = only RSVP attendee; 3 = RSVP & PayPal attendee; 4 = only PayPal attendee
			'events'                         => [],
			// '_tribe_rsvp_product' values
			'tickets_products_rsvp'          => [],
			// '_tribe_tpp_product' values
			'tickets_products_paypal'        => [],
			// '_tribe_rsvp_order' values
			'tickets_orders_rsvp'            => [],
			// '_tribe_tpp_order' values
			'tickets_orders_paypal'          => [],
			// '_tribe_rsvp_full_name' values
			'tickets_purchaser_names_rsvp'   => [],
			// '_tribe_tpp_full_name' values
			'tickets_purchaser_names_paypal' => [],
			// 4 total: 1&3 = Event author, 2&4 = Attendees
			'users'                          => [],
			'user_2_details'                 => [
				'first_name' => 'Female',
				'last_name'  => 'Blue',
				'email'      => 'user2@tri.be',
			],
			'user_4_details'                 => [
				'first_name' => 'Male',
				'last_name'  => 'Brown',
				'email'      => 'user4@tri.be',
			],
			// 4 total: 1&3 = has Author, Tickets, and Attendees; 2&4 = Author ID of zero and no Tickets (so no Attendees)
			'rsvp_tickets'                   => [],
			// 4 total: 1 = 4 Attendees (users 2 & 3 + 2 guests); 2, 3, & 4 = no Attendees
			'paypal_tickets'                 => [],
			// 4 total: 1 = 4 Attendees (users 3 & 4 + 2 guests); 2, 3, & 4 = no Attendees
			'attendees_all'                  => [],
			// 9 total (5 by logged in): 1 & 2 = RSVP by logged in; 3 & 4 = RSVP by logged out; 5 & 6 = PayPal by logged in; 7 & 8: PayPal by logged out; 9 by User2 on Event3
			'attendees_event_1'              => [], // Event1's
			'attendees_event_3'              => [], // Event3's
			'attendees_rsvp'                 => [], // All RSVP Ticket attendees
			'attendees_rsvp_1'               => [], // All RSVP Ticket ID 1's attendees: 1,2,3,4
			'attendees_rsvp_5'               => [], // All RSVP Ticket ID 5's attendees: 10
			'attendees_paypal'               => [], // All PayPal Ticket attendees
			'attendees_paypal_1'             => [], // All PayPal Ticket ID 1's attendees: 5,6,7,8
			'attendees_paypal_5'             => [], // All PayPal Ticket ID 5's attendees: 9
		];

		// Create User1, author of Event1.
		$test_data['users'][] = $user_id_one = $this->factory()->user->create( [ 'role' => 'author' ] );

		// Create test users 2, 3, and 4 as Attendees
		$test_data['users'][] = $user_id_two = $this->factory()->user->create( $test_data['user_2_details'] );
		$test_data['users'][] = $user_id_three = $this->factory()->user->create();
		$test_data['users'][] = $user_id_four = $this->factory()->user->create( $test_data['user_4_details'] );

		//
		// Event1: RSVP and PayPal by User2, User3, and guests
		//
		$test_data['events'][] =
		$event_id_one = $this->factory()->event->create(
			[
				'post_title'  => 'Test event 1',
				'post_author' => $user_id_one,
			]
		);

		// Create RSVP1 ticket on Event1
		$rsvp_id_one = $this->create_rsvp_ticket( $event_id_one );

		// Add User2 (Attendee1) and User3 (Attendee2) as RSVP1 attendees on Event1
		$test_data['attendees_event_1'][] =
		$test_data['attendees_rsvp'][] =
		$test_data['attendees_rsvp_1'][] =
		$attendee_id_1 =
			$this->create_attendee_for_ticket( $rsvp_id_one, $event_id_one, [ 'user_id' => $user_id_two ] );

		$test_data['attendees_event_1'][] =
		$test_data['attendees_rsvp'][] =
		$test_data['attendees_rsvp_1'][] =
		$attendee_id_2 =
			$this->create_attendee_for_ticket( $rsvp_id_one, $event_id_one, [ 'user_id' => $user_id_three ] );

		// Add 2 guest purchasers (Attendees 3 & 4) to RSVP1 Ticket already having other Attendees
		$test_data['attendees_event_1'][] =
		$test_data['attendees_rsvp'][] =
		$test_data['attendees_rsvp_1'][] =
		$attendee_id_3 =
			$this->create_attendee_for_ticket( $rsvp_id_one, $event_id_one );

		$test_data['attendees_event_1'][] =
		$test_data['attendees_rsvp'][] =
		$test_data['attendees_rsvp_1'][] =
		$attendee_id_4 =
			$this->create_attendee_for_ticket( $rsvp_id_one, $event_id_one );

		// Create 3 more RSVP tickets that will never have any attendees
		$test_data['rsvp_tickets'] = array_merge( [ $rsvp_id_one ], $this->create_many_rsvp_tickets( 3, $event_id_one ) );

		// Create test PayPal1 ticket
		$paypal_id_one = $this->create_paypal_ticket_basic( $event_id_one, 5 );

		// Add User3 (Attendee5) and User4 (Attendee6) as Tribe Commerce PayPal Ticket attendees

		$test_data['attendees_event_1'][] =
		$test_data['attendees_paypal'][] =
		$test_data['attendees_paypal_1'][] =
		$attendee_id_5 =
			$this->create_attendee_for_ticket( $paypal_id_one, $event_id_one, [ 'user_id' => $user_id_three ] );

		$test_data['attendees_event_1'][] =
		$test_data['attendees_paypal'][] =
		$test_data['attendees_paypal_1'][] =
		$attendee_id_6 =
			$this->create_attendee_for_ticket( $paypal_id_one, $event_id_one, [ 'user_id' => $user_id_four ] );

		// Add 2 guest purchasers (Attendees 7 & 8) to the PayPal Ticket already having other Attendees

		$test_data['attendees_event_1'][] =
		$test_data['attendees_paypal'][] =
		$test_data['attendees_paypal_1'][] =
		$attendee_id_7 =
			$this->create_attendee_for_ticket( $paypal_id_one, $event_id_one );

		$test_data['attendees_event_1'][] =
		$test_data['attendees_paypal'][] =
		$test_data['attendees_paypal_1'][] =
		$attendee_id_8 =
			$this->create_attendee_for_ticket( $paypal_id_one, $event_id_one );

		// Create 3 more PayPal tickets that will never have any attendees
		$test_data['paypal_tickets'] = array_merge( [ $paypal_id_one ], $this->create_many_paypal_tickets_basic( 3, $event_id_one ) );

		// Create test PayPal5 ticket
		$test_data['paypal_tickets'][] =
		$paypal_id_five =
			$this->create_paypal_ticket_basic( $event_id_one, 12 );

		// Add User2 (Attendee9) as Tribe Commerce PayPal Ticket attendee
		$test_data['attendees_event_1'][] =
		$test_data['attendees_paypal'][] =
		$test_data['attendees_paypal_5'][] =
		$attendee_id_9 =
			$this->create_attendee_for_ticket( $paypal_id_five, $event_id_one, [ 'user_id' => $user_id_two ] );

		//
		// Event2: No author nor tickets (and therefore no attendees)
		//
		$test_data['events'][] =
		$event_id_two = $this->factory()->event->create(
			[
				'post_title'  => 'Test event 2',
				'post_author' => 0,
			]
		);

		//
		// Event3: User2 as Attendee10 (RSVP5) for User2
		//

		// Create Event3, having tickets
		$test_data['events'][] =
		$event_id_three = $this->factory()->event->create(
			[
				'post_title'  => 'Test event 3',
				'post_author' => $user_id_one,
			]
		);

		$test_data['rsvp_tickets'][] =
		$rsvp_id_five =
			$this->create_rsvp_ticket( $event_id_three );

		// Add User2 (Attendee10) on RSVP5 ticket on Event3
		$test_data['attendees_event_3'][] =
		$test_data['attendees_rsvp'][] =
		$test_data['attendees_rsvp_5'][] =
		$attendee_id_10 =
			$this->create_attendee_for_ticket( $rsvp_id_five, $event_id_three, [ 'user_id' => $user_id_two ] );

		// Create User5, author of Event3.
		$test_data['users'][] = $user_id_five = $this->factory()->user->create( [ 'role' => 'author' ] );

		//
		// Event4: No author nor tickets (and therefore no attendees)
		//
		$test_data['events'][] = $this->factory()->event->create(
			[
				'post_title'  => 'Test event 4',
				'post_author' => $user_id_five,
			]
		);

		// Merge all attendees
		$test_data['attendees_all'] = array_unique(
			array_merge(
				$test_data['attendees_event_1'], // Event1
				$test_data['attendees_event_3'] // Event3
			)
		);

		// Get the post_meta so we can filter by it
		foreach ( $test_data['attendees_all'] as $attendee_id ) {
			$meta = get_post_meta( $attendee_id );

			foreach ( $meta as $k => $v ) {
				// Tickets
				if (
					$rsvp::ATTENDEE_PRODUCT_KEY === $k
					&& ! empty( $meta[ $k ][0] )
				) {
					$test_data['tickets_products_rsvp'][] = $meta[ $k ][0];
				} elseif (
					$paypal::ATTENDEE_PRODUCT_KEY === $k
					&& ! empty( $meta[ $k ][0] )
				) {
					$test_data['tickets_products_paypal'][] = $meta[ $k ][0];
				} // Orders
				elseif (
					$rsvp->order_key === $k
					&& ! empty( $meta[ $k ][0] )
				) {
					$test_data['tickets_orders_rsvp'][] = $meta[ $k ][0];
				} elseif (
					$paypal::ATTENDEE_ORDER_KEY === $k
					&& ! empty( $meta[ $k ][0] )
				) {
					$test_data['tickets_orders_paypal'][] = $meta[ $k ][0];
				} // Purchaser Names
				elseif (
					$rsvp->full_name === $k
					&& ! empty( $meta[ $k ][0] )
				) {
					$test_data['tickets_purchaser_names_rsvp'][] = $meta[ $k ][0];
				} elseif (
					$paypal->full_name === $k
					&& ! empty( $meta[ $k ][0] )
				) {
					$test_data['tickets_purchaser_names_paypal'][] = $meta[ $k ][0];
				}
			}
		}

		// Save test data to class property after running each through array_unique()
		foreach ( $test_data as $key => $value ) {
			$this->test_data[ $key ] = array_unique( (array) $value );
		}

		// Debugging (only works for failing tests)
		$debug = $this->get_attendee_data( 807 );

		global $wpdb;
		$all_metas = $wpdb->get_col(
			$wpdb->prepare(
				"
			SELECT pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND p.post_type = %s
			",
				'_tribe_rsvp_full_name',
				'tribe_rsvp_attendees'
			)
		);

		if ( ! empty( $debug ) ) {
			codecept_debug( $debug );
		}
	}

	/**
	 * Given an Attendee ID, such as from Codeception saying one was missing, get the info about that Attendee to help
	 * point you in the right direction to get the test running correctly.
	 *
	 * @param int $id
	 *
	 * @return array|false
	 */
	public function get_attendee_data( int $id = 0 ) {
		$result = [];

		$post = get_post( $id, ARRAY_A );

		if (
			empty( $id )
			|| empty( $post )
		) {
			return false;
		}

		$result['post'] = $post;
		$result['meta'] = get_post_meta( $id );

		return $result;
	}

	/**
	 * Get an array of IDs that would never match for any Attendees.
	 *
	 * @param int $key Optionally get just 1 value from the array (still returns an array).
	 *
	 * @return array
	 */
	public function get_fake_ids( int $key = - 1 ) {
		$array = [
			- 1,
			888888,
			999999,
			PHP_INT_MAX,
		];

		shuffle( $array );

		if ( array_key_exists( $key, $array ) ) {
			return (array) $array[ $key ];
		}

		return $array;
	}

	/**
	 * Get an array of names that would never match for any Attendees.
	 *
	 * @param int $key Optionally get just 1 value from the array (still returns an array).
	 *
	 * @return array
	 */
	public function get_fake_names( int $key = - 1 ) {
		$array = [
			'aaaaaaaaa',
			'bbbbbbbbb',
			'CCCCCCCCC',
			'DDDDDDDDD',
		];

		shuffle( $array );

		if ( array_key_exists( $key, $array ) ) {
			return (array) $array[ $key ];
		}

		return $array;
	}

}