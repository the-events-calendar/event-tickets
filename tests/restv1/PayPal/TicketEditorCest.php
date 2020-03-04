<?php

namespace Tribe\Tickets\Test\REST\V1\PayPal;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as Ticket_Maker;
use Tribe\Tickets\Test\REST\V1\BaseRestCest;

class TicketEditorCest extends BaseRestCest {

	use Ticket_Maker;
	use Attendee_Maker;

	/*
	 * Nonces
'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
	 */

	/**
	 * Get ticket matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_matrix() {
		$providers = array_keys( $this->get_providers() );

		$mode_matrix = [
			[
				// Shared capacity (with limit for this ticket).
				'ticket[mode]'           => 'capped',
				'ticket[capacity]'       => 10,
				'ticket[event_capacity]' => 15,
			],
			[
				// Shared capacity (no set optional limit for this ticket).
				'ticket[mode]'           => 'capped',
				'ticket[capacity]'       => '',
				'ticket[event_capacity]' => 15,
			],
			[
				// Limited capacity for this ticket only.
				'ticket[mode]'     => 'own',
				'ticket[capacity]' => 12,
			],
			[
				// Unlimited capacity.
				'ticket[mode]'     => '',
				'ticket[capacity]' => '',
			],
		];

		$matrix = [];

		foreach ( $providers as $provider ) {
			foreach ( $mode_matrix as $mode ) {
				$matrix[] = array_merge( [
					'provider' => $provider,
				], $mode );
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

		$mode_matrix = [
			[
				// Shared capacity (with limit for this ticket).
				'ticket[mode]'           => 'capped',
				'ticket[capacity]'       => 10,
				'ticket[event_capacity]' => 15,
			],
			[
				// Shared capacity (no set optional limit for this ticket).
				'ticket[mode]'           => 'capped',
				'ticket[capacity]'       => '',
				'ticket[event_capacity]' => 15,
			],
			[
				// Limited capacity for this ticket only.
				'ticket[mode]'     => 'own',
				'ticket[capacity]' => 12,
			],
			[
				// Unlimited capacity.
				'ticket[mode]'     => '',
				'ticket[capacity]' => '',
			],
		];

		$matrix = [];

		foreach ( $ticket_matrix as $ticket ) {
			foreach ( $mode_matrix as $mode ) {
				$new_ticket = $ticket;

				unset( $new_ticket['ticket[mode]'], $new_ticket['ticket[capacity]'], $new_ticket['ticket[event_capacity]'] );

				$new_ticket = array_merge( $new_ticket, $mode );

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
	 * Get list of providers.
	 *
	 * @return array List of providers.
	 */
	private function get_providers() {
		return [
			'Tribe__Tickets__Commerce__PayPal__Main' => 'tribe-commerce',
		];
	}

	/**
	 * Get matching provider ID or class.
	 *
	 * @param string $provider Provider class or ID.
	 *
	 * @return string The matching provider ID or class.
	 */
	private function get_provider( $provider ) {
		$providers = $this->get_providers();

		if ( isset( $providers[ $provider ] ) ) {
			return $providers[ $provider ];
		}

		return array_search( $provider, $providers, true );
	}

	/**
	 * Get capacity amount from arguments.
	 *
	 * @param array $args List of arguments.
	 *
	 * @return int Capacity amount.
	 */
	private function get_capacity( array $args ) {
		if ( '' === $args['ticket[capacity]'] ) {
			if ( isset( $args['ticket[event_capacity]'] ) ) {
				return $args['ticket[event_capacity]'];
			}

			return - 1;
		}

		return $args['ticket[capacity]'];
	}

	/**
	 * It should allow creating a ticket.
	 *
	 * @test
	 * @dataProvider _get_ticket_matrix
	 */
	public function should_allow_creating_a_ticket( Restv1Tester $I, \Codeception\Example $variation ) {
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

		$ticket_create_rest_url = $this->tickets_url . '/';

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 202 );
		$I->seeResponseIsJson();

		$capacity = $this->get_capacity( $create_args );

		$expected_json = [
			'description'                   => $create_args['description'],
			'capacity'                      => - 1 === $capacity ? '' : $capacity,
			'post_id'                       => $post_id,
			'provider'                      => $this->get_provider( $create_args['provider'] ),
			'author'                        => (string) $author_id,
			'status'                        => 'publish',
			'title'                         => $create_args['name'],
			'image'                         => false,
			'available_from'                => $create_args['start_date'],
			'available_until'               => $create_args['end_date'],
			'capacity_details'              => [
				'available_percentage' => 100,
				'max'                  => - 1 === $capacity ? 0 : $capacity,
				'available'            => $capacity,
				'sold'                 => 0,
				'pending'              => 0,
			],
			'is_available'                  => true,
			'cost'                          => '$' . $create_args['price'] . '.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [
					(string) $create_args['price'],
				],
			],
			'supports_attendee_information' => false, // ET+ not installed.
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			//'capacity_type'                 => $create_args['ticket[mode]'],
			//'sku'                           => $create_args['sku'],
			/*'totals'                        => [
				'stock'   => $capacity,
				'sold'    => 0,
				'pending' => 0,
			],*/
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
	public function should_allow_updating_a_ticket( Restv1Tester $I, \Codeception\Example $variation ) {
		$I->generate_nonce_for_role( 'administrator' );

		$variation = $variation->getIterator()->getArrayCopy();

		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$author_id = get_current_user_id();
		$post_id   = $I->havePostInDatabase();

		$args = [
			'post_id'     => $post_id,
			'name'        => 'Test ticket name',
			'description' => 'Test description text',
			'price'       => 12,
			'start_date'  => date_i18n( 'Y-m-d' ),
			'start_time'  => '08:00:00',
			'end_date'    => date_i18n( 'Y-m-d', strtotime( '+2 months' ) ),
			'end_time'    => '20:00:00',
			'sku'         => 'TKT-555',
			'menu_order'  => 1,
		];

		$create_args = array_merge( $args, $variation['from'] );
		$update_args = array_merge( $args, $variation['to'] );

		codecept_debug( var_export( $variation['from'], true ) );
		codecept_debug( var_export( $variation['to'], true ) );

		$create_args['add_ticket_nonce']  = wp_create_nonce( 'add_ticket_nonce' );
		$update_args['edit_ticket_nonce'] = wp_create_nonce( 'edit_ticket_nonce' );

		$ticket_create_rest_url = $this->tickets_url . '/';

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 202 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$ticket_id = $response['id'];

		$ticket_update_rest_url = $ticket_create_rest_url . $ticket_id;

		$I->sendPOST( $ticket_update_rest_url, $update_args );

		$I->seeResponseCodeIs( 202 );
		$I->seeResponseIsJson();

		$capacity = $this->get_capacity( $update_args );

		$expected_json = [
			'description'                   => $update_args['description'],
			'capacity'                      => - 1 === $capacity ? '' : $capacity,
			'post_id'                       => $post_id,
			'provider'                      => $this->get_provider( $update_args['provider'] ),
			'id'                            => $ticket_id,
			'global_id'                     => $repository->get_ticket_global_id( $ticket_id ),
			'global_id_lineage'             => $repository->get_ticket_global_id_lineage( $ticket_id ),
			'author'                        => (string) $author_id,
			'status'                        => 'publish',
			'date'                          => $response['date'],
			'date_utc'                      => $response['date_utc'],
			'title'                         => $update_args['name'],
			'image'                         => false,
			'available_from'                => $update_args['start_date'],
			'available_from_details'        => $response['available_from_details'],
			'available_until'               => $update_args['end_date'],
			'available_until_details'       => $response['available_until_details'],
			'capacity_details'              => [
				'available_percentage' => 100,
				'max'                  => - 1 === $capacity ? 0 : $capacity,
				'available'            => $capacity,
				'sold'                 => 0,
				'pending'              => 0,
			],
			'is_available'                  => true,
			'cost'                          => '$' . $update_args['price'] . '.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [
					(string) $update_args['price'],
				],
			],
			'supports_attendee_information' => false, // ET+ not installed.
			'attendees'                     => [],
			'checkin'                       => [
				'checked_in'              => 0,
				'unchecked_in'            => 0,
				'checked_in_percentage'   => 100,
				'unchecked_in_percentage' => 0,
			],
			'rest_url'                      => $ticket_update_rest_url,
			//'capacity_type'                 => $update_args['ticket[mode]'],
			//'sku'                           => $update_args['sku'],
			/*'totals'                        => [
				'stock'   => $capacity,
				'sold'    => 0,
				'pending' => 0,
			],*/
		];

		$response = json_decode( $I->grabResponse(), true );

		// Remove args from comparison.
		unset( $response['modified'], $response['modified_utc'] );

		$I->assertEquals( $expected_json, $response );
	}
}
