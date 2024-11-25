<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Event_Automator\Tests\Traits\Create_Events;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;
use WP_Error;
use Generator;
use Closure;
use Tribe\Tests\Traits\With_Clock_Mock;
use WP_REST_Response;
use Tribe__Date_Utils as Dates;

class Fees_Test extends Controller_Test_Case {
	use With_Uopz;
	use SnapshotAssertions;
	use Fee_Creator;
	use Ticket_Maker;
	use Create_Events;
	use With_Clock_Mock;

	protected string $controller_class = Fees::class;

	public function rest_endpoints_data_provider(): Generator {
		yield 'fees archive- authorized' => [
			function () {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				return [ '/fees', false, 'GET', $post_ids, $ticket_ids, $fee_ids, [] ];
			},
		];

		yield 'fees archive - unauthorized' => [
			function () {
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				return [ '/fees', true, 'GET', $post_ids, $ticket_ids, $fee_ids, [] ];
			},
		];

		yield 'ticket fees list - authorized' => [
			function () {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				return [ '/tickets/{TICKET_ID}/fees', false, 'GET', $post_ids, $ticket_ids, $fee_ids, [] ];
			},
		];

		yield 'ticket fees list - unauthorized' => [
			function () {
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				return [ '/tickets/{TICKET_ID}/fees', true, 'GET', $post_ids, $ticket_ids, $fee_ids, [] ];
			},
		];

		yield 'ticket fees add - authorized - invalid data' => [
			function () {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				$selected_fees = array_slice( $fee_ids, 0, 2 );
				$selected_fees = array_merge( $selected_fees, [ 'string', false, null, true, 'as78dasf', '89', 98, [ 87 ] ] );
				return [ '/tickets/{TICKET_ID}/fees', true, 'POST', $post_ids, $ticket_ids, $fee_ids, $selected_fees ];
			},
			400
		];

		yield 'ticket fees add - unauthorized - invalid data' => [
			function () {
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				$selected_fees = array_slice( $fee_ids, 0, 2 );
				$selected_fees = array_merge( $selected_fees, [ 'string', false, null, true, 'as78dasf', '89', 98, [ 87 ] ] );
				return [ '/tickets/{TICKET_ID}/fees', true, 'POST', $post_ids, $ticket_ids, $fee_ids, $selected_fees ];
			},
			400
		];

		yield 'ticket fees add - authorized - valid data' => [
			function () {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				$selected_fees = array_slice( $fee_ids, 0, 2 );
				return [ '/tickets/{TICKET_ID}/fees', false, 'POST', $post_ids, $ticket_ids, $fee_ids, $selected_fees ];
			},
		];

		yield 'ticket fees add - unauthorized - valid data' => [
			function () {
				[ $post_ids, $ticket_ids, $fee_ids ] = $this->create_data();
				$selected_fees = array_slice( $fee_ids, 0, 2 );
				return [ '/tickets/{TICKET_ID}/fees', true, 'POST', $post_ids, $ticket_ids, $fee_ids, $selected_fees ];
			},
		];
	}

	/**
	 * @dataProvider rest_endpoints_data_provider
	 * @test
	 */
	public function it_should_provide_expected_responses( Closure $fixture, $error_code = 401 ) {
		$this->freeze_time( Dates::immutable( '2022-06-13 17:25:32' ) );
		[ $path, $should_fail, $method, $post_ids, $ticket_ids, $fee_ids, $selected_fees ] = $fixture();
		$controller = $this->make_controller();
		$controller->register();
		if ( strstr( $path, '{TICKET_ID}' ) ) {
			$data = [];
			foreach ( $ticket_ids as $ticket_id ) {
				$data[] = $this->assert_endpoint( str_replace( '{TICKET_ID}', $ticket_id, $path ), $method, $should_fail, $selected_fees, $error_code );
			}

			if ( ! empty( $selected_fees ) ) {
				foreach ( $ticket_ids as $ticket_id ) {
					$ticket_fees = $controller->get_fees_for_ticket( $ticket_id );
					$this->assertCount( 2, $ticket_fees['selected_fees'] );
					$this->assertCount( 1, $ticket_fees['automatic_fees'] );
					$this->assertCount( count( $fee_ids ) - 1, $ticket_fees['available_fees'] );
				}
			}
		} else {
			$data = $this->assert_endpoint( $path, $method, $should_fail, $selected_fees, $error_code );
		}

		$json = wp_json_encode( $data, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace(
			$post_ids,
			'{POST_ID}',
			$json
		);

		$json = str_replace(
			$ticket_ids,
			'{TICKET_ID}',
			$json
		);

		$json = str_replace(
			$fee_ids,
			'{FEE_ID}',
			$json
		);
		$this->assertMatchesJsonSnapshot( $json );
	}

	protected function create_data() {
		$post_ids = self::factory()->post->create_many( 2 );
		$post_ids = array_merge( $post_ids, array_map( static fn( $e ) => $e->ID, $this->generate_multiple_events( '2021-01-01', 2 ) ) );

		$ticket_ids = [];
		foreach ( $post_ids as $k => $post_id ) {
			foreach ( range( 1, 3 ) as $i ) {
				$ticket_ids[] = $this->create_tc_ticket( $post_id, ( $k + 1 ) * ( $i * 10 ) );
			}
		}

		$fee_ids = [];
		foreach ( $ticket_ids as $k => $ticket_id ) {
			foreach ( [ 1, 3 ] as $i ) {
				$fee_ids[] = $this->create_fee_for_ticket( $ticket_id, [ 'raw_amount' => ( $k + 1 ) * ( $i * 10 ) ] );
			}
		}

		$fee_id_for_all = $this->create_fee( [ 'raw_amount' => 100 ] );
		$this->set_fee_application( $fee_id_for_all, 'all' );
		$fee_ids[] = $fee_id_for_all->id;

		return [ $post_ids, $ticket_ids, $fee_ids ];
	}

	protected function assert_endpoint( string $path, string $method = 'GET', bool $should_fail = false, array $selected_fees = [], int $error_code = 401 ) {
		$response = $this->do_rest_api_request( $path, $method, $selected_fees );

		if ( $should_fail ) {
			$this->assertTrue( $response->is_error() );
			$this->assertInstanceof( WP_Error::class, $response->as_error() );
			$this->assertEquals( $error_code, $response->get_status() );
			return $response->get_data();
		}

		$this->assertFalse( $response->is_error() );
		$this->assertEquals( 200, $response->get_status() );
		return $response->get_data();
	}

	protected function do_rest_api_request( string $path, string $method, array $selected_fees = [] ): WP_REST_Response {
		do_action( 'rest_api_init' );
		$server = rest_get_server();

		$request = new WP_REST_Request( $method, '/tribe/tickets/v1' . $path );

		if ( ! empty( $selected_fees ) ) {
			$request->set_body_params( [ 'selected_fees' => $selected_fees ] );
		}

		return $server->dispatch( $request );
	}
}
