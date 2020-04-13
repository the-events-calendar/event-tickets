<?php

namespace Tribe\Tickets\Test\Testcases\REST\V1;

use Codeception\Example;
use Restv1Tester;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Traits\CapacityMatrix;

class BaseTicketEditorCest extends BaseRestCest {

	use Attendee_Maker;
	use CapacityMatrix;
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
	 * @var bool
	 */
	public $is_plus = false;

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
	 * Create a RSVP.
	 *
	 * @param Restv1Tester $I         API tester.
	 * @param array        $variation Variation data.
	 * @param null|array   $override  List of arguments to override with.
	 *
	 * @return array RSVP args.
	 */
	protected function create_rsvp_using_rest( Restv1Tester $I, array $variation, array $override = [] ) {
		$post_id = $I->havePostInDatabase();

		$args = [
			'post_id' => $post_id,
			'title'   => 'Test RSVP name',
			'excerpt' => 'Test description text',
			'meta'    => [
				'_price'                         => 0,
				'_tribe_ticket_capacity'         => '',
				'_ticket_start_date'             => '2020-01-02 08:00:00',
				'_ticket_end_date'               => '2050-03-02 20:00:00',
				'_tribe_ticket_show_not_going'   => 'false',
				'_tribe_rsvp_for_event'          => $post_id,
				'_tribe_ticket_show_description' => 'yes',
			],
			'status'  => 'publish',
		];

		$create_args = array_merge( $args, $override, $variation );

		$rsvp_create_rest_url = $this->rsvps_url;

		$I->sendPOST( $rsvp_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 201 );
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
			'available_from'                => 'tribe-commerce' === $provider ? $create_args['start_date'] : $create_args['start_date'] . ' ' . $create_args['start_time'],
			// @todo TC does not return full date+time, should it?
			'available_until'               => 'tribe-commerce' === $provider ? $create_args['end_date'] : $create_args['end_date'] . ' ' . $create_args['end_time'],
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
				'currency_symbol'   => 'woo' === $provider ? '€' : '$',
				'currency_position' => 'woo' === $provider ? 'postfix' : 'prefix',
				'values'            => [
					(string) $price,
				],
				'suffix'            => null,
			],
			'requires_attendee_information' => false,
			'attendee_information_fields'   => [],
			'supports_attendee_information' => false,
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			'price_suffix'                  => null,
		];

		$is_plus_test = $this->is_plus;

		if ( ! $is_plus_test ) {
			unset( $expected_json['requires_attendee_information'], $expected_json['attendee_information_fields'] );
		}

		$response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $response['id'], $response['global_id'], $response['global_id_lineage'], $response['date'], $response['date_utc'], $response['modified'], $response['modified_utc'], $response['available_from_details'], $response['available_until_details'], $response['rest_url'] );

		$I->assertEquals( $expected_json, $response );
	}

	/**
	 * It should allow creating a ticket and updating the post.
	 *
	 * @dataProvider _get_shared_ticket_matrix
	 */
	public function should_allow_creating_a_ticket_and_updating_post( Restv1Tester $I, Example $variation ) {
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
		$sku      = null;

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

		if ( 'rsvp' !== $provider ) {
			$sku = $create_args['sku'];
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
			'available_from'                => 'tribe-commerce' === $provider ? $create_args['start_date'] : $create_args['start_date'] . ' ' . $create_args['start_time'],
			// @todo TC does not return full date+time, should it?
			'available_until'               => 'tribe-commerce' === $provider ? $create_args['end_date'] : $create_args['end_date'] . ' ' . $create_args['end_time'],
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
				'currency_symbol'   => 'woo' === $provider ? '€' : '$',
				'currency_position' => 'woo' === $provider ? 'postfix' : 'prefix',
				'values'            => [
					(string) $price,
				],
				'suffix'            => null,
			],
			'requires_attendee_information' => false,
			'attendee_information_fields'   => [],
			'supports_attendee_information' => false,
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			'capacity_type'                 => $mode,
			'sku'                           => $sku,
			'available_from_start_time'     => $create_args['start_time'],
			'available_from_end_time'       => $create_args['end_time'],
			'totals'                        => [
				// @todo Zero may not be what it should return if unlimited.
				'stock'   => - 1 === $capacity ? 0 : $capacity,
				'sold'    => 0,
				'pending' => 0,
			],
			'price_suffix'                  => null,
		];

		$is_plus_test = $this->is_plus;

		if ( ! $is_plus_test ) {
			unset( $expected_json['requires_attendee_information'], $expected_json['attendee_information_fields'] );
		}

		$create_response = json_decode( $I->grabResponse(), true );

		$ticket_id = $create_response['id'];

		$post_args = [
			'id'      => $post_id,
			'content' => "<!-- wp:tribe/event-datetime /-->\n\n<!-- wp:paragraph {\"placeholder\":\"Add Description...\"} -->\n<p></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:tribe/event-price /-->\n\n<!-- wp:tribe/event-organizer /-->\n\n<!-- wp:tribe/event-venue /-->\n\n<!-- wp:tribe/event-website /-->\n\n<!-- wp:tribe/event-links /-->\n\n<!-- wp:tribe/tickets -->\n<div class=\"wp-block-tribe-tickets\"><!-- wp:tribe/tickets-item {\"hasBeenCreated\":true,\"ticketId\":{$ticket_id} -->\n<div class=\"wp-block-tribe-tickets-item\"></div>\n<!-- /wp:tribe/tickets-item --></div>\n<!-- /wp:tribe/tickets -->\n\n<!-- wp:tribe/rsvp /-->",
			'meta'    => [
				'_price'                                 => '',
				'_stock'                                 => '',
				'_tribe_ticket_header'                   => '',
				'_tribe_default_ticket_provider'         => $create_args['provider'],
				'_tribe_ticket_capacity'                 => $capacity,
				'_ticket_start_date'                     => '',
				'_ticket_end_date'                       => '',
				'_tribe_ticket_show_description'         => '',
				'_tribe_ticket_show_not_going'           => false,
				'_tribe_ticket_use_global_stock'         => '',
				'_tribe_ticket_global_stock_level'       => '',
				'_global_stock_mode'                     => '',
				'_global_stock_cap'                      => '',
				'_tribe_rsvp_for_event'                  => '',
				'_tribe_ticket_going_count'              => '',
				'_tribe_ticket_not_going_count'          => '',
				'_tribe_tickets_list'                    => [],
				'_tribe_ticket_has_attendee_info_fields' => false,
			],
		];

		// Handle post update.
		$post_update_rest_url = $this->wp_rest_url . 'posts/' . $post_id;

		$I->sendPOST( $post_update_rest_url, $post_args );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		// Get Ticket data.
		$ticket_get_rest_url = $this->tickets_url . '/' . $ticket_id;

		$I->sendGET( $ticket_get_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$get_response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $get_response['id'], $get_response['global_id'], $get_response['global_id_lineage'], $get_response['date'], $get_response['date_utc'], $get_response['modified'], $get_response['modified_utc'], $get_response['available_from_details'], $get_response['available_until_details'], $get_response['rest_url'] );

		$I->assertEquals( $expected_json, $get_response );
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
			'available_from'                => 'tribe-commerce' === $provider ? $update_args['start_date'] : $update_args['start_date'] . ' ' . $update_args['start_time'],
			'available_from_details'        => $response['available_from_details'],
			// @todo TC does not return full date+time, should it?
			'available_until'               => 'tribe-commerce' === $provider ? $update_args['end_date'] : $update_args['end_date'] . ' ' . $update_args['end_time'],
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
				'currency_symbol'   => 'woo' === $provider ? '€' : '$',
				'currency_position' => 'woo' === $provider ? 'postfix' : 'prefix',
				'values'            => [
					(string) $price,
				],
				'suffix'            => null,
			],
			'requires_attendee_information' => false,
			'attendee_information_fields'   => [],
			'supports_attendee_information' => false,
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			'rest_url'                      => $ticket_update_rest_url,
			'price_suffix'                  => null,
		];

		$is_plus_test = $this->is_plus;

		if ( ! $is_plus_test ) {
			unset( $expected_json['requires_attendee_information'], $expected_json['attendee_information_fields'] );
		}

		$response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $response['modified'], $response['modified_utc'] );

		$I->assertEquals( $expected_json, $response );
	}
}
