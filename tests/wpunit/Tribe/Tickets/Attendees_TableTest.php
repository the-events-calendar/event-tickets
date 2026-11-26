<?php

namespace Tribe\Tickets;

use Generator;
use RuntimeException;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Attendees_Table as Attendees_Table;
use Tribe__Tickets__Data_API as Data_API;
use WP_Post;

class Attendees_TableTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		$GLOBALS['hook_suffix'] = 'tribe_events_page_tickets-attendees';
	}

	/**
	 * {@inheritdoc}
	 */
	public function tearDown() {
		unset(
			$_GET['event_id'],
			$_REQUEST['event_id'],
			$_POST['attendee'],
			$_POST['_wpnonce'],
			$_REQUEST['action']
		);

		parent::tearDown();
	}

	private function make_instance() {
		return new Attendees_Table();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Attendees_Table::class, $sut );
	}

	/**
	 * It should only forward an advertised sort key and an `ASC`/`DESC` direction from the request.
	 *
	 * @test
	 * @dataProvider request_sort_parameters_provider
	 */
	public function should_only_forward_advertised_sort_parameters( $request, $expected_orderby, $expected_order ) {
		unset(
			$_REQUEST['orderby'],
			$_REQUEST['order'],
			$_GET['orderby'],
			$_GET['order'],
			$_POST['orderby'],
			$_POST['order']
		);

		foreach ( $request as $key => $value ) {
			$_GET[ $key ]     = $value;
			$_REQUEST[ $key ] = $value;
		}

		$captured = null;
		$capture  = static function ( $args ) use ( &$captured ) {
			$captured = $args;

			return $args;
		};

		add_filter( 'tec_tickets_attendees_table_query_args', $capture, 10, 2 );
		$sut = $this->make_instance();
		$sut->prepare_items();
		remove_filter( 'tec_tickets_attendees_table_query_args', $capture, 10 );

		$this->assertSame( $expected_orderby, $captured['orderby'] ?? null );
		$this->assertSame( $expected_order, $captured['order'] ?? null );
	}

	/**
	 * Request sort parameters and the table arguments they should produce.
	 *
	 * @return \Generator
	 */
	public function request_sort_parameters_provider() {
		yield 'advertised sort key' => [
			[ 'orderby' => 'security_code' ],
			'security_code',
			'DESC',
		];
		yield 'array-form orderby' => [
			[ 'orderby' => [ 'security_code' => 'DESC, (SELECT 1)' ] ],
			null,
			'DESC',
		];
		yield 'unknown sort key' => [
			[ 'orderby' => 'post_password' ],
			null,
			'DESC',
		];
		yield 'injected direction' => [
			[ 'order' => 'DESC, (SELECT 1)' ],
			null,
			'DESC',
		];
		yield 'lowercase direction' => [
			[ 'order' => 'asc' ],
			null,
			'ASC',
		];
	}

	/**
	 * It should allow fetching ticket attendees by event.
	 *
	 * @todo @bordoni fix this particular test to be more consistent [TECENG-37]
	 * @link https://theeventscalendar.atlassian.net/browse/TECENG-37
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_event() {
		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 25, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 25, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$paypal_attendee_ids2 = $this->create_many_attendees_for_ticket( 25, $paypal_ticket_id2, $post_id2 );
		$rsvp_attendee_ids2   = $this->create_many_attendees_for_ticket( 25, $rsvp_ticket_id2, $post_id2 );

		/*
		 Attendees will be ordered by `post_date` and `post_title`. Since we're creating the Attendees with a factory,
		the `post_date` will be the same for pretty much all of them. The purpose of the test is to assert we get the
		correct Attendees so we make the order deterministic among posts that would have an undetermined order by
		setting the `post_title` to a string including a progressive number.
		 */
		foreach (
			[
				...$paypal_attendee_ids,
				...$rsvp_attendee_ids,
				...$paypal_attendee_ids2,
				...$rsvp_attendee_ids2
			] as $k => $id
		) {
			wp_update_post( [
				'ID'         => $id,
				'post_title' => 'Attendee #' . str_pad( $k, '3', '0', STR_PAD_LEFT ),
			] );
		}

		$sut = $this->make_instance();

		$_GET['event_id'] = $post_id;
		$_GET['paged']    = 1;

		$sut->prepare_items();
		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$expected_attendee_array = array_merge( $paypal_attendee_ids, $rsvp_attendee_ids );
		// Sort descending by ID, to match the order.
		rsort( $expected_attendee_array );

		$expected_attendee_ids = array_slice( $expected_attendee_array, 0, $sut->get_pagination_arg( 'per_page' ) );

		$this->assertEquals( $expected_attendee_ids, $attendee_ids );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ) ), $sut->get_pagination_arg( 'total_items' ) );

		$_GET['event_id'] = $post_id2;
		$_GET['paged']    = 1;
		$sut->prepare_items();
		$attendee_ids2 = wp_list_pluck( $sut->items, 'attendee_id' );

		$expected_attendee_array2 = array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 );
		// Sort descending by ID, to match the order.
		rsort( $expected_attendee_array2 );

		$expected_attendee_ids2 = array_slice( $expected_attendee_array2, 0, $sut->get_pagination_arg( 'per_page' ) );

		$this->assertEquals( $expected_attendee_ids2, $attendee_ids2 );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 ) ), $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by name search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_name_search() {
		$post_id = $this->factory->post->create();

		$rsvp_name_meta_key = tribe( 'tickets.rsvp' )->full_name;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_name_meta_key, 'Robbbbbbbbbbbbb Tester' );

		$sut = $this->make_instance();

		$_GET['search']                      = 'robbbbb';
		$_POST['tribe_attendee_search_type'] = 'purchaser_name';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by email search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_email_search() {
		$post_id = $this->factory->post->create();

		$rsvp_email_meta_key = tribe( 'tickets.rsvp' )->email;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_email_meta_key, 'rob@likestests.com' );

		$sut = $this->make_instance();

		$_GET['search']                      = 'likestests';
		$_POST['tribe_attendee_search_type'] = 'purchaser_email';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by ticket search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_ticket_search() {
		$post_id = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$sut = $this->make_instance();

		$_GET['search']                      = $rsvp_ticket_id;
		$_POST['tribe_attendee_search_type'] = 'product_id';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( $rsvp_attendee_ids, $attendee_ids );
		$this->assertEquals( count( $rsvp_attendee_ids ), $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by security code search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_security_code_search() {
		$post_id = $this->factory->post->create();

		$rsvp_security_code_meta_key = tribe( 'tickets.rsvp' )->security_code;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_security_code_meta_key, 'robba' );

		$sut = $this->make_instance();

		$_GET['search']                      = 'robba';
		$_POST['tribe_attendee_search_type'] = 'security_code';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by user search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_user_search() {
		$post_id = $this->factory->post->create();

		$rsvp_user_meta_key = tribe( 'tickets.rsvp' )->attendee_user_id;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$search_attendee_id = current( array_slice( $rsvp_attendee_ids, 0, 1 ) );

		update_post_meta( $search_attendee_id, $rsvp_user_meta_key, '1234567890' );

		$sut = $this->make_instance();

		$_GET['search']                      = '1234567890';
		$_POST['tribe_attendee_search_type'] = 'user';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( [ $search_attendee_id ], $attendee_ids );
		$this->assertEquals( 1, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * It should allow fetching ticket attendees by order status search.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_order_status_search() {
		$post_id = $this->factory->post->create();

		$rsvp_user_meta_key = tribe( 'tickets.rsvp' )->attendee_user_id;

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$sut = $this->make_instance();

		$_GET['search']                      = 'yes';
		$_POST['tribe_attendee_search_type'] = 'order_status';

		$_GET['event_id'] = $post_id;

		$sut->prepare_items();

		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( $rsvp_attendee_ids, $attendee_ids );
		$this->assertEquals( 5, $sut->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * @test
	 */
	public function should_escape_attendee_name_in_primary_info_column(): void {
		set_current_screen( 'tribe_events_page_tickets-attendees' );

		$post_id   = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$name      = 'Bob< b >';
		$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'full_name' => $name ] );

		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';

		$table = $this->make_instance();
		$table->prepare_items();

		$html = $table->column_primary_info( $table->items[0] );

		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringContainsString( esc_html( $name ), $html );
	}

	public function test_display(): void {
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		$post_id = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $post_id );

		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';

		$table = $this->make_instance();
		$table->prepare_items();

		$attendee_ids  = wp_list_pluck( $table->items, 'attendee_id' );
		$attendee_data = array_reduce( $attendee_ids, function ( array $carry, int $id ) {
			foreach (
				[
					'_tribe_tpp_security_code',
					'_tribe_tpp_full_name',
					'_tribe_tpp_email',
					'_tribe_rsvp_security_code',
					'_tribe_rsvp_full_name',
					'_tribe_rsvp_email',
				] as $meta_key
			) {
				$meta_value = get_post_meta( $id, $meta_key, true );

				if ( empty( $meta_value ) ) {
					continue;
				}

				$carry[] = esc_html( $meta_value );
				$carry[] = $meta_value;
			}

			return $carry;
		}, [] );

		ob_start();
		$table->display();
		$html = ob_get_clean();

		// Stabilize snapshots.
		$html = str_replace( $attendee_data, 'ATTENDEE_DATA', $html );
		$html = str_replace( [
			...$attendee_ids,
			$post_id,
			$paypal_ticket_id,
			$rsvp_ticket_id,
			...$rsvp_attendee_ids,
			...$paypal_attendee_ids
		], 'POST_ID', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function bulk_check_in_action_provider(): Generator {
		yield 'check in' => [ 'check_in', false ];
		yield 'undo check-in' => [ 'uncheck_in', true ];
	}

	/**
	 * @test
	 * @dataProvider bulk_check_in_action_provider
	 *
	 * @param string $action            The bulk action.
	 * @param bool   $checked_in_before Whether the attendees are checked in before the action.
	 */
	public function should_change_the_check_in_only_of_attendees_the_user_can_check_in( string $action, bool $checked_in_before ): void {
		$fixture = $this->given_a_contributor_and_attendees_of_an_own_and_another_event();
		$paypal  = tribe( 'tickets.commerce.paypal' );

		if ( $checked_in_before ) {
			update_post_meta( $fixture['own_attendee'], $paypal->checkin_key, 1 );
			update_post_meta( $fixture['other_attendee'], $paypal->checkin_key, 1 );
		}

		wp_set_current_user( $fixture['contributor'] );
		$this->given_a_bulk_action_request( $action, $fixture['own_event'], [ $fixture['own_attendee'], $fixture['other_attendee'] ] );

		$this->make_instance()->process_actions();

		$this->assertSame( ! $checked_in_before, $this->is_checked_in( $fixture['own_attendee'] ) );
		$this->assertSame( $checked_in_before, $this->is_checked_in( $fixture['other_attendee'] ) );
	}

	/**
	 * @test
	 */
	public function should_ignore_bulk_actions_from_the_page_of_an_event_the_user_cannot_manage(): void {
		$fixture = $this->given_a_contributor_and_attendees_of_an_own_and_another_event();

		wp_set_current_user( $fixture['contributor'] );
		$this->given_a_bulk_action_request( 'check_in', $fixture['other_event'], [ $fixture['own_attendee'] ] );

		$this->make_instance()->process_actions();

		$this->assertFalse( $this->is_checked_in( $fixture['own_attendee'] ) );
	}

	/**
	 * @test
	 */
	public function should_delete_only_attendees_the_user_can_manage(): void {
		$fixture = $this->given_a_contributor_and_attendees_of_an_own_and_another_event();

		wp_set_current_user( $fixture['contributor'] );
		$this->given_a_bulk_action_request( 'delete_attendee', $fixture['own_event'], [ $fixture['own_attendee'], $fixture['other_attendee'] ] );
		add_filter(
			'wp_redirect',
			static function () {
				// The action redirects back to the page and exits, which would end the test run.
				throw new RuntimeException( 'redirected' );
			}
		);

		try {
			$this->make_instance()->process_actions();
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'redirected', $e->getMessage() );
		}

		$this->assertNull( get_post( $fixture['own_attendee'] ) );
		$this->assertInstanceOf( WP_Post::class, get_post( $fixture['other_attendee'] ) );
	}

	public function listed_event_request_provider(): Generator {
		yield 'own event in the query string, another in the request' => [ 'own', 'other', '' ];
		yield 'own event as a decimal number' => [ 'own', 'own', '.0' ];
	}

	/**
	 * @test
	 * @dataProvider listed_event_request_provider
	 *
	 * @param string $query_event   The event in the query string: `own` or `other`.
	 * @param string $request_event The event in the merged request: `own` or `other`.
	 * @param string $suffix        Appended to each requested event ID.
	 */
	public function should_list_only_the_attendees_of_the_event_the_table_was_built_for( string $query_event, string $request_event, string $suffix ): void {
		$fixture = $this->given_a_contributor_and_attendees_of_an_own_and_another_event();

		wp_set_current_user( $fixture['contributor'] );
		$_GET['event_id']     = $fixture[ "{$query_event}_event" ] . $suffix;
		$_REQUEST['event_id'] = $fixture[ "{$request_event}_event" ] . $suffix;
		$table                = $this->make_instance();

		$table->prepare_items();

		$this->assertSame( [ $fixture['own_attendee'] ], array_map( 'absint', wp_list_pluck( $table->items, 'attendee_id' ) ) );
	}

	/**
	 * Creates a Contributor's draft and an administrator's post, each with one PayPal attendee.
	 *
	 * @return array{contributor: int, own_event: int, other_event: int, own_attendee: int, other_attendee: int}
	 */
	private function given_a_contributor_and_attendees_of_an_own_and_another_event(): array {
		$admin_id       = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$contributor_id = $this->factory->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $admin_id );

		$own_event   = $this->factory->post->create(
			[
				'post_author' => $contributor_id,
				'post_status' => 'draft',
			]
		);
		$other_event = $this->factory->post->create( [ 'post_author' => $admin_id ] );

		return [
			'contributor'    => $contributor_id,
			'own_event'      => $own_event,
			'other_event'    => $other_event,
			'own_attendee'   => $this->create_attendee_for_ticket( $this->create_paypal_ticket_basic( $own_event, 1 ), $own_event ),
			'other_attendee' => $this->create_attendee_for_ticket( $this->create_paypal_ticket_basic( $other_event, 1 ), $other_event ),
		];
	}

	/**
	 * Sets up a bulk action request on the Attendees page of an event, as the current user.
	 *
	 * @param string $action       The bulk action.
	 * @param int    $event_id     The event whose Attendees page sends the request.
	 * @param int[]  $attendee_ids The selected PayPal attendees.
	 */
	private function given_a_bulk_action_request( string $action, int $event_id, array $attendee_ids ): void {
		$_GET['event_id']     = $event_id;
		$_REQUEST['event_id'] = $event_id;
		$_REQUEST['action']   = $action;
		$_POST['_wpnonce']    = wp_create_nonce( 'bulk-attendees' );
		$_POST['attendee']    = array_map(
			static fn( int $attendee_id ) => $attendee_id . '|Tribe__Tickets__Commerce__PayPal__Main',
			$attendee_ids
		);
	}

	/**
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return bool
	 */
	private function is_checked_in( int $attendee_id ): bool {
		return tribe_is_truthy( get_post_meta( $attendee_id, tribe( 'tickets.commerce.paypal' )->checkin_key, true ) );
	}
}
