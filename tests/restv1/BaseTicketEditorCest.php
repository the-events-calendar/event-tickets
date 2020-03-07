<?php

namespace Tribe\Tickets\Test\REST\V1;

use Codeception\Example;
use Restv1Tester;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

class BaseTicketEditorCest extends BaseRestCest {

	use Attendee_Maker;
	use MatchesSnapshots {
		setUpSnapshotIncrementor as protected;
		markTestIncompleteIfSnapshotsHaveChanged as protected;
		assertMatchesXmlSnapshot as protected;
		assertMatchesJsonSnapshot as protected;
		assertMatchesFileHashSnapshot as protected;
		assertMatchesFileSnapshot as protected;
		assertMatchesSnapshot as protected;
	}

	/**
	 * Get ticket matrix mode variations.
	 *
	 * @return array List of mode variations.
	 */
	public function _get_ticket_mode_matrix() {
		return [
			[
				// Shared capacity (with limit for this ticket).
				'ticket' => [
					'mode'           => 'capped',
					'capacity'       => 10,
					'event_capacity' => 15,
				],
			],
			[
				// Shared capacity (with limit for this ticket).
				'ticket' => [
					'mode'           => 'capped',
					'capacity'       => 11,
					'event_capacity' => 15,
				],
			],
			[
				// Shared capacity (no set optional limit for this ticket).
				'ticket' => [
					'mode'           => 'capped',
					'capacity'       => '',
					'event_capacity' => 15,
				],
			],
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'mode'     => 'own',
					'capacity' => 12,
				],
			],
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'mode'     => 'own',
					'capacity' => 13,
				],
			],
			[
				// Unlimited capacity.
				'ticket' => [
					'mode'     => '',
					'capacity' => '',
				],
			],
		];
	}

	/**
	 * Get ticket matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_matrix() {
		$providers   = array_keys( $this->get_providers() );
		$mode_matrix = $this->_get_ticket_mode_matrix();

		$matrix = [];

		foreach ( $providers as $provider ) {
			foreach ( $mode_matrix as $mode ) {
				$matrix[] = array_merge( $mode, [
					'provider' => $provider,
				] );
			}
		}

		return $matrix;
	}

	/**
	 * Get ticket update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_update_matrix() {
		$ticket_matrix = $this->_get_ticket_matrix();
		$mode_matrix   = $this->_get_ticket_mode_matrix();

		$matrix = [];

		foreach ( $ticket_matrix as $ticket ) {
			foreach ( $mode_matrix as $mode ) {
				$new_ticket = $ticket;

				unset( $new_ticket['ticket'] );

				$new_ticket = array_merge( $mode, $new_ticket );

				if ( $ticket === $new_ticket ) {
					continue;
				}

				$matrix[] = [
					'from' => $ticket,
					'to'   => $new_ticket,
				];
			}
		}

		return $matrix;
	}

	/**
	 * Get RSVP matrix mode variations.
	 *
	 * @return array List of mode variations.
	 */
	public function _get_rsvp_mode_matrix() {
		return [
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'capacity' => 12,
				],
			],
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'capacity' => 13,
				],
			],
			[
				// Unlimited capacity.
				'ticket' => [
					'capacity' => '',
				],
			],
		];
	}

	/**
	 * Get RSVP matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_rsvp_matrix() {
		$providers   = array_keys( $this->get_rsvp_providers() );
		$mode_matrix = $this->_get_rsvp_mode_matrix();

		$matrix = [];

		foreach ( $providers as $provider ) {
			foreach ( $mode_matrix as $mode ) {
				$matrix[] = array_merge( $mode, [
					'provider' => $provider,
				] );
			}
		}

		return $matrix;
	}

	/**
	 * Get RSVP update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_rsvp_update_matrix() {
		$rsvp_matrix = $this->_get_rsvp_matrix();
		$mode_matrix = $this->_get_rsvp_mode_matrix();

		$matrix = [];

		foreach ( $rsvp_matrix as $rsvp ) {
			foreach ( $mode_matrix as $mode ) {
				$new_rsvp = $rsvp;

				unset( $new_rsvp['ticket'] );

				$new_rsvp = array_merge( $mode, $new_rsvp );

				if ( $rsvp === $new_rsvp ) {
					continue;
				}

				$matrix[] = [
					'from' => $rsvp,
					'to'   => $new_rsvp,
				];
			}
		}

		return $matrix;
	}

	/**
	 * Prepare HTML so it can be used in snapshot testing.
	 *
	 * @param string $html HTML to prepare.
	 *
	 * @return string Prepared HTML.
	 */
	protected function prepare_html( $html ) {
		$html = preg_replace( '/check=\w+/', 'check=nonceABC', $html );

		return $html;
	}

	/**
	 * Get list of providers for test.
	 *
	 * @return array List of providers.
	 */
	protected function get_providers() {
		return [];
	}

	/**
	 * Get list of RSVP providers for test.
	 *
	 * @return array List of RSVP providers.
	 */
	protected function get_rsvp_providers() {
		return [
			'Tribe__Tickets__RSVP' => 'rsvp',
		];
	}

	/**
	 * Get matching provider ID or class.
	 *
	 * @param string $provider Provider class or ID.
	 *
	 * @return string The matching provider ID or class.
	 */
	protected function get_provider( $provider ) {
		$providers      = $this->get_providers();
		$rsvp_providers = $this->get_rsvp_providers();

		if ( isset( $providers[ $provider ] ) ) {
			return $providers[ $provider ];
		} elseif ( isset( $rsvp_providers[ $provider ] ) ) {
			return $rsvp_providers[ $provider ];
		}

		$found = array_search( $provider, $providers, true );

		if ( ! $found ) {
			$found = array_search( $provider, $rsvp_providers, true );
		}

		return $found;
	}

	/**
	 * Get capacity amount from arguments.
	 *
	 * @param array $args List of arguments.
	 *
	 * @return int Capacity amount.
	 */
	protected function get_capacity( array $args ) {
		$capacity = isset( $args['tribe-ticket']['capacity'] ) ? $args['tribe-ticket']['capacity'] : $args['ticket']['capacity'];

		if ( '' === $capacity ) {
			if ( isset( $args['ticket']['event_capacity'] ) ) {
				return $args['ticket']['event_capacity'];
			} elseif ( isset( $args['tribe-ticket']['event_capacity'] ) ) {
				return $args['tribe-ticket']['event_capacity'];
			}

			return - 1;
		}

		return $capacity;
	}

	/**
	 * Create a ticket.
	 *
	 * @param Restv1Tester $I         API tester.
	 * @param array        $variation Variation data.
	 * @param null|array   $override  List of arguments to override with.
	 *
	 * @return array Ticket args.
	 */
	protected function create_ticket_using_rest( Restv1Tester $I, array $variation, array $override = [] ) {
		$post_id = $I->havePostInDatabase();

		$args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name',
			'description'      => 'Test description text',
			'price'            => 12,
			'start_date'       => '2020-01-02',
			'start_time'       => '08:00:00',
			'end_date'         => '2050-03-01',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-555',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
		];

		$create_args = array_merge( $args, $override, $variation );

		$ticket_create_rest_url = $this->tickets_url . '/';

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 202 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$create_args['response'] = $response;

		return $create_args;
	}

	/**
	 * Create a ticket via admin-ajax.php.
	 *
	 * @param Restv1Tester $I         API tester.
	 * @param array        $variation Variation data.
	 * @param null|array   $override  List of arguments to override with.
	 *
	 * @return array Ticket args.
	 */
	protected function create_ticket_using_ajax( Restv1Tester $I, array $variation, array $override = [] ) {
		if ( isset( $override['post_id'] ) ) {
			$post_id = $override['post_id'];

			unset( $override['post_id'] );
		} else {
			$post_id = $I->havePostInDatabase();
		}

		$ticket_id = '';

		if ( isset( $override['ticket_id'] ) ) {
			$ticket_id = $override['ticket_id'];

			unset( $override['ticket_id'] );
		}

		$menu_order = 0;

		if ( isset( $override['menu_order'] ) ) {
			$menu_order = $override['menu_order'];

			unset( $override['menu_order'] );
		}

		$args = [
			'name'        => 'Test ticket name',
			'description' => 'Test description text',
			'price'       => 12,
			'start_date'  => '2020-01-02',
			'start_time'  => '08:00:00',
			'end_date'    => '2050-03-01',
			'end_time'    => '20:00:00',
			'sku'         => 'TKT-555',
		];

		$args = array_merge( $args, $override, $variation );

		$create_args = [
			'action'     => 'tribe-ticket-add',
			'post_id'    => $post_id,
			'data'       => [
				'ticket_show_description'           => 1,
				'tribe-tickets-saved-fieldset-name' => '',
				'tribe-tickets-input[0]'            => '',
				'ticket_id'                         => $ticket_id,
			],
			'nonce'      => wp_create_nonce( 'add_ticket_nonce' ),
			'menu_order' => $menu_order,
			'is_admin'   => 'true',
		];

		foreach ( $args as $arg => $value ) {
			if ( 'ticket' === $arg ) {
				$arg = 'tribe-' . $arg;
			} else {
				$arg = 'ticket_' . $arg;
			}

			$create_args['data'][ $arg ] = $value;
		}

		$ticket_create_ajax_url = admin_url( 'admin-ajax.php' );

		$I->sendPOST( $ticket_create_ajax_url, $create_args );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$create_args['response'] = $response;

		return $create_args;
	}

	/**
	 * Create a RSVP via admin-ajax.php.
	 *
	 * @param Restv1Tester $I         API tester.
	 * @param array        $variation Variation data.
	 * @param null|array   $override  List of arguments to override with.
	 *
	 * @return array RSVP args.
	 */
	protected function create_rsvp_using_ajax( Restv1Tester $I, array $variation, array $override = [] ) {
		if ( isset( $override['post_id'] ) ) {
			$post_id = $override['post_id'];

			unset( $override['post_id'] );
		} else {
			$post_id = $I->havePostInDatabase();
		}

		$ticket_id = '';

		if ( isset( $override['ticket_id'] ) ) {
			$ticket_id = $override['ticket_id'];

			unset( $override['ticket_id'] );
		}

		$menu_order = 0;

		if ( isset( $override['menu_order'] ) ) {
			$menu_order = $override['menu_order'];

			unset( $override['menu_order'] );
		}

		$args = [
			'name'        => 'Test RSVP name',
			'description' => 'Test description text',
			'start_date'  => '2020-01-02',
			'start_time'  => '08:00:00',
			'end_date'    => '2050-03-01',
			'end_time'    => '20:00:00',
		];

		$args = array_merge( $args, $override, $variation );

		$create_args = [
			'action'     => 'tribe-ticket-add',
			'post_id'    => $post_id,
			'data'       => [
				'ticket_show_description'           => 1,
				'tribe-tickets-saved-fieldset-name' => '',
				'tribe-tickets-input[0]'            => '',
				'ticket_id'                         => $ticket_id,
			],
			'nonce'      => wp_create_nonce( 'add_ticket_nonce' ),
			'menu_order' => $menu_order,
			'is_admin'   => 'true',
		];

		foreach ( $args as $arg => $value ) {
			if ( 'ticket' === $arg ) {
				$arg = 'tribe-' . $arg;
			} else {
				$arg = 'ticket_' . $arg;
			}

			$create_args['data'][ $arg ] = $value;
		}

		$ticket_create_ajax_url = admin_url( 'admin-ajax.php' );

		$I->sendPOST( $ticket_create_ajax_url, $create_args );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$create_args['response'] = $response;

		return $create_args;
	}

	/**
	 * It should allow creating a ticket.
	 *
	 * @test
	 * @dataProvider _get_ticket_matrix
	 */
	public function should_allow_creating_a_ticket( Restv1Tester $I, Example $variation ) {
		$I->generate_nonce_for_role( 'administrator' );

		$variation = $variation->getIterator()->getArrayCopy();

		$author_id = get_current_user_id();
		$post_id   = $I->havePostInDatabase();

		$args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name',
			'description'      => 'Test description text',
			'price'            => 12,
			'start_date'       => date_i18n( 'Y-m-d' ),
			'start_time'       => '08:00:00',
			'end_date'         => date_i18n( 'Y-m-d', strtotime( '+2 months' ) ),
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-555',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
		];

		$create_args = array_merge( $args, $variation );

		if ( 'Tribe__Tickets__RSVP' === $create_args['provider'] ) {
			unset( $create_args['price'], $create_args['sku'] );
		}

		$ticket_create_rest_url = $this->tickets_url . '/';

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 202 );
		$I->seeResponseIsJson();

		$capacity = $this->get_capacity( $create_args );
		$provider = $this->get_provider( $create_args['provider'] );
		$mode     = 'own';
		$price    = '0';

		if ( isset( $create_args['ticket']['mode'] ) ) {
			$mode = 'unlimited';

			if ( '' !== $create_args['ticket']['mode'] ) {
				$mode = $create_args['ticket']['mode'];
			}
		} elseif ( - 1 === $capacity ) {
			$mode = 'unlimited';
		}

		if ( isset( $create_args['price'] ) ) {
			$price = $create_args['price'];
		}

		$expected_json = [
			'description'                   => $create_args['description'],
			// @todo Empty string may not be what it should return if unlimited.
			'capacity'                      => - 1 === $capacity ? '' : $capacity,
			'post_id'                       => $post_id,
			'provider'                      => $provider,
			'author'                        => (string) $author_id,
			'status'                        => 'publish',
			'title'                         => $create_args['name'],
			'image'                         => false,
			// @todo TC does not return full date+time, should it?
			'available_from'                => $create_args['start_date'],
			// @todo TC does not return full date+time, should it?
			'available_until'               => $create_args['end_date'],
			'capacity_details'              => [
				'available_percentage' => 100,
				// @todo Zero may not be what it should return if unlimited.
				'max'                  => - 1 === $capacity ? 0 : $capacity,
				'available'            => $capacity,
				'sold'                 => 0,
				'pending'              => 0,
			],
			'is_available'                  => true,
			'cost'                          => '$' . $price . '.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [
					(string) $price,
				],
			],
			'supports_attendee_information' => false,
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
		];

		$response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $response['id'], $response['global_id'], $response['global_id_lineage'], $response['date'], $response['date_utc'], $response['modified'], $response['modified_utc'], $response['available_from_details'], $response['available_until_details'], $response['rest_url'] );

		$I->assertEquals( $expected_json, $response );
	}

	/**
	 * It should allow updating a ticket.
	 *
	 * @test
	 * @dataProvider _get_ticket_update_matrix
	 */
	public function should_allow_updating_a_ticket( Restv1Tester $I, Example $variation ) {
		$I->generate_nonce_for_role( 'administrator' );

		$variation = $variation->getIterator()->getArrayCopy();

		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$create_args = $this->create_ticket_using_rest( $I, $variation['from'] );

		$response = $create_args['response'];

		$post_id   = $create_args['post_id'];
		$author_id = get_current_user_id();
		$ticket_id = $response['id'];

		$update_args = $create_args;

		unset( $update_args['response'], $update_args['ticket'], $update_args['add_ticket_nonce'] );

		$update_args = array_merge( $update_args, $variation['to'] );

		$update_args['edit_ticket_nonce'] = wp_create_nonce( 'edit_ticket_nonce' );

		$ticket_update_rest_url = $this->tickets_url . '/' . $ticket_id;

		$I->sendPOST( $ticket_update_rest_url, $update_args );

		$I->seeResponseCodeIs( 202 );
		$I->seeResponseIsJson();

		$capacity = $this->get_capacity( $update_args );
		$provider = $this->get_provider( $update_args['provider'] );
		$mode     = 'own';
		$price    = '0';

		if ( isset( $update_args['ticket']['mode'] ) ) {
			$mode = 'unlimited';

			if ( '' !== $update_args['ticket']['mode'] ) {
				$mode = $update_args['ticket']['mode'];
			}
		} elseif ( - 1 === $capacity ) {
			$mode = 'unlimited';
		}

		if ( isset( $update_args['price'] ) ) {
			$price = $update_args['price'];
		}

		$expected_json = [
			'description'                   => $update_args['description'],
			// @todo Empty string may not be what it should return if unlimited.
			'capacity'                      => - 1 === $capacity ? '' : $capacity,
			'post_id'                       => $post_id,
			'provider'                      => $provider,
			'id'                            => $ticket_id,
			'global_id'                     => $repository->get_ticket_global_id( $ticket_id ),
			'global_id_lineage'             => $repository->get_ticket_global_id_lineage( $ticket_id ),
			'author'                        => (string) $author_id,
			'status'                        => 'publish',
			'date'                          => $response['date'],
			'date_utc'                      => $response['date_utc'],
			'title'                         => $update_args['name'],
			'image'                         => false,
			// @todo TC does not return full date+time, should it?
			'available_from'                => $update_args['start_date'],
			'available_from_details'        => $response['available_from_details'],
			// @todo TC does not return full date+time, should it?
			'available_until'               => $update_args['end_date'],
			'available_until_details'       => $response['available_until_details'],
			'capacity_details'              => [
				'available_percentage' => 100,
				// @todo Zero may not be what it should return if unlimited.
				'max'                  => - 1 === $capacity ? 0 : $capacity,
				'available'            => $capacity,
				'sold'                 => 0,
				'pending'              => 0,
			],
			'is_available'                  => true,
			'cost'                          => '$' . $price . '.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [
					(string) $price,
				],
			],
			'supports_attendee_information' => false,
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			'rest_url'                      => $ticket_update_rest_url,
		];

		$response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $response['modified'], $response['modified_utc'] );

		$I->assertEquals( $expected_json, $response );
	}

	/**
	 * It should allow getting a Classic ticket.
	 *
	 * @test
	 * @dataProvider _get_ticket_matrix
	 */
	public function should_allow_getting_a_classic_ticket( Restv1Tester $I, Example $variation ) {
		$I->generate_nonce_for_role( 'administrator' );

		$variation = $variation->getIterator()->getArrayCopy();

		$variation_data = json_encode( $variation );

		// For snapshots.
		$this->setName( __FUNCTION__ . '_' . md5( $variation_data ) );

		$create_args = $this->create_ticket_using_ajax( $I, $variation );

		$response = $create_args['response'];

		$I->assertTrue( $response['success'] );

		$ticket_create_ajax_url = admin_url( 'admin-ajax.php' );

		// Assertion test the admin-ajax.php response.
		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), $ticket_create_ajax_url );

		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['list'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['settings'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['ticket'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['notice'] ) );

		preg_match( '/ticket-id=["\'](\d+)["\']/', $response['data']['list'], $matches );

		$post_id   = $create_args['post_id'];
		$ticket_id = $matches[1];

		$get_args = [
			'action'    => 'tribe-ticket-edit',
			'post_id'   => $post_id,
			'ticket_id' => $ticket_id,
			'nonce'     => wp_create_nonce( 'edit_ticket_nonce' ),
			'is_admin'  => 'true',
		];

		$ticket_get_ajax_url = admin_url( 'admin-ajax.php' );

		// This is a POST not a GET.
		$I->sendPOST( $ticket_get_ajax_url, $get_args );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$I->assertTrue( $response['success'] );

		$ticket_create_ajax_url = admin_url( 'admin-ajax.php' );

		// Assertion test the admin-ajax.php response.
		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), $ticket_create_ajax_url );

		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['list'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['settings'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['ticket'] ) );
	}

	/**
	 * It should allow creating a Classic ticket.
	 *
	 * @test
	 * @dataProvider _get_ticket_matrix
	 */
	public function should_allow_creating_a_classic_ticket( Restv1Tester $I, Example $variation ) {
		$I->generate_nonce_for_role( 'administrator' );

		$variation = $variation->getIterator()->getArrayCopy();

		$variation_data = json_encode( $variation );

		// For snapshots.
		$this->setName( __FUNCTION__ . '_' . md5( $variation_data ) );

		$create_args = $this->create_ticket_using_ajax( $I, $variation );

		$response = $create_args['response'];

		$I->assertTrue( $response['success'] );

		$ticket_create_ajax_url = admin_url( 'admin-ajax.php' );

		// Assertion test the admin-ajax.php response.
		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), $ticket_create_ajax_url );

		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['list'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['settings'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['ticket'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $response['data']['notice'] ) );

		preg_match( '/ticket-id=["\'](\d+)["\']/', $response['data']['list'], $matches );

		$post_id   = $create_args['post_id'];
		$author_id = get_current_user_id();
		$ticket_id = $matches[1];

		// Get ticket data so we can assert ticket saved as expected.
		$ticket_get_rest_url = $this->tickets_url . '/' . $ticket_id;

		$I->sendGET( $ticket_get_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$create_data = $create_args['data'];

		$capacity = $this->get_capacity( $create_data );
		$provider = $this->get_provider( $create_data['ticket_provider'] );
		$mode     = 'own';
		$price    = '0';

		if ( isset( $create_data['tribe-ticket']['mode'] ) ) {
			$mode = 'unlimited';

			if ( '' !== $create_data['tribe-ticket']['mode'] ) {
				$mode = $create_data['tribe-ticket']['mode'];
			}
		} elseif ( - 1 === $capacity ) {
			$mode = 'unlimited';
		}

		if ( isset( $create_data['ticket_price'] ) ) {
			$price = $create_data['ticket_price'];
		}

		$expected_json = [
			'description'                   => $create_data['ticket_description'],
			// @todo Empty string may not be what it should return if unlimited.
			'capacity'                      => - 1 === $capacity ? '' : $capacity,
			'post_id'                       => $post_id,
			'provider'                      => $provider,
			'author'                        => (string) $author_id,
			'status'                        => 'publish',
			'title'                         => $create_data['ticket_name'],
			'image'                         => false,
			// @todo TC does not return full date+time, should it?
			'available_from'                => 'tribe-commerce' === $provider ? $create_data['ticket_start_date'] : $create_data['ticket_start_date'] . ' ' . $create_data['ticket_start_time'],
			// @todo TC does not return full date+time, should it?
			'available_until'               => 'tribe-commerce' === $provider ? $create_data['ticket_end_date'] : $create_data['ticket_end_date'] . ' ' . $create_data['ticket_end_time'],
			'capacity_details'              => [
				'available_percentage' => 100,
				// @todo Zero may not be what it should return if unlimited.
				'max'                  => - 1 === $capacity ? 0 : $capacity,
				'available'            => $capacity,
				'sold'                 => 0,
				'pending'              => 0,
			],
			'is_available'                  => true,
			'cost'                          => '$' . $price . '.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [
					(string) $price,
				],
			],
			'supports_attendee_information' => false,
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			'capacity_type'                 => $mode,
			// @todo The below does not match AJAX versus API.
			'sku'                           => 'rsvp' === $provider ? null : $create_data['ticket_sku'],
			'available_from_start_time'     => $create_data['ticket_start_time'],
			'available_from_end_time'       => $create_data['ticket_end_time'],
			'totals'                        => [
				// @todo Zero may not be what it should return if unlimited.
				'stock'   => - 1 === $capacity ? 0 : $capacity,
				'sold'    => 0,
				'pending' => 0,
			],
		];

		if ( 'rsvp' === $provider ) {
			$expected_json['rsvp'] = [
				'rsvp_going'     => 0,
				'rsvp_not_going' => 0,
			];
		}

		$response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $response['id'], $response['global_id'], $response['global_id_lineage'], $response['date'], $response['date_utc'], $response['modified'], $response['modified_utc'], $response['available_from_details'], $response['available_until_details'], $response['rest_url'] );

		$I->assertEquals( $expected_json, $response );
	}

	/**
	 * It should allow updating a Classic ticket.
	 *
	 * @test
	 * @dataProvider _get_ticket_update_matrix
	 */
	public function should_allow_updating_a_classic_ticket( Restv1Tester $I, Example $variation ) {
		$I->generate_nonce_for_role( 'administrator' );

		$variation = $variation->getIterator()->getArrayCopy();

		$variation_data = json_encode( $variation );

		// For snapshots.
		$this->setName( __FUNCTION__ . '_' . md5( $variation_data ) );

		$create_args = $this->create_ticket_using_ajax( $I, $variation['from'] );

		$create_response = $create_args['response'];

		$I->assertTrue( $create_response['success'] );

		$ticket_create_ajax_url = admin_url( 'admin-ajax.php' );

		// Assertion test the admin-ajax.php response.
		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), $ticket_create_ajax_url );

		$this->assertMatchesSnapshot( $this->prepare_html( $create_response['data']['list'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $create_response['data']['settings'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $create_response['data']['ticket'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $create_response['data']['notice'] ) );

		preg_match( '/ticket-id=["\'](\d+)["\']/', $create_response['data']['list'], $matches );

		$post_id   = $create_args['post_id'];
		$author_id = get_current_user_id();
		$ticket_id = $matches[1];

		// Update the ticket.
		$update_args = $this->create_ticket_using_ajax( $I, $variation['to'], [
			// Update is the same as create, it just sets the post_id.
			'post_id'    => $post_id,
			// Update is the same as create, it just sets the ticket_id.
			'ticket_id'  => $ticket_id,
			// After first save, menu_order is saved as 1 on next save.
			'menu_order' => 1,
		] );

		$update_response = $update_args['response'];

		$I->assertEquals( $this->prepare_html( $create_response['data']['notice'] ), $this->prepare_html( $update_response['data']['notice'] ) );

		$I->assertTrue( $update_response['success'] );

		// Assertion test the admin-ajax.php response.
		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), $ticket_create_ajax_url );

		$this->assertMatchesSnapshot( $this->prepare_html( $update_response['data']['list'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $update_response['data']['settings'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $update_response['data']['ticket'] ) );
		$this->assertMatchesSnapshot( $this->prepare_html( $update_response['data']['notice'] ) );

		// Get ticket data so we can assert ticket saved as expected.
		$ticket_get_rest_url = $this->tickets_url . '/' . $ticket_id;

		$I->sendGET( $ticket_get_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$update_data = $update_args['data'];

		$capacity = $this->get_capacity( $update_data );
		$provider = $this->get_provider( $update_data['ticket_provider'] );
		$mode     = 'own';
		$price    = '0';

		if ( isset( $update_data['tribe-ticket']['mode'] ) ) {
			$mode = 'unlimited';

			if ( '' !== $update_data['tribe-ticket']['mode'] ) {
				$mode = $update_data['tribe-ticket']['mode'];
			}
		} elseif ( - 1 === $capacity ) {
			$mode = 'unlimited';
		}

		if ( isset( $update_data['ticket_price'] ) ) {
			$price = $update_data['ticket_price'];
		}

		$expected_json = [
			'description'                   => $update_data['ticket_description'],
			// @todo Empty string may not be what it should return if unlimited.
			'capacity'                      => - 1 === $capacity ? '' : $capacity,
			'post_id'                       => $post_id,
			'provider'                      => $provider,
			'author'                        => (string) $author_id,
			'status'                        => 'publish',
			'title'                         => $update_data['ticket_name'],
			'image'                         => false,
			// @todo TC does not return full date+time, should it?
			'available_from'                => 'tribe-commerce' === $provider ? $update_data['ticket_start_date'] : $update_data['ticket_start_date'] . ' ' . $update_data['ticket_start_time'],
			// @todo TC does not return full date+time, should it?
			'available_until'               => 'tribe-commerce' === $provider ? $update_data['ticket_end_date'] : $update_data['ticket_end_date'] . ' ' . $update_data['ticket_end_time'],
			'capacity_details'              => [
				'available_percentage' => 100,
				// @todo Zero may not be what it should return if unlimited.
				'max'                  => - 1 === $capacity ? 0 : $capacity,
				'available'            => $capacity,
				'sold'                 => 0,
				'pending'              => 0,
			],
			'is_available'                  => true,
			'cost'                          => '$' . $price . '.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [
					(string) $price,
				],
			],
			'supports_attendee_information' => false,
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			'capacity_type'                 => $mode,
			// @todo The below does not match AJAX versus API.
			'sku'                           => 'rsvp' === $provider ? null : $update_data['ticket_sku'],
			'available_from_start_time'     => $update_data['ticket_start_time'],
			'available_from_end_time'       => $update_data['ticket_end_time'],
			'totals'                        => [
				// @todo Zero may not be what it should return if unlimited.
				'stock'   => - 1 === $capacity ? 0 : $capacity,
				'sold'    => 0,
				'pending' => 0,
			],
		];

		if ( 'rsvp' === $provider ) {
			$expected_json['rsvp'] = [
				'rsvp_going'     => 0,
				'rsvp_not_going' => 0,
			];
		}

		$response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $response['id'], $response['global_id'], $response['global_id_lineage'], $response['date'], $response['date_utc'], $response['modified'], $response['modified_utc'], $response['available_from_details'], $response['available_until_details'], $response['rest_url'] );

		$I->assertEquals( $expected_json, $response );
	}
}
