<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use Closure;
use Generator;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__Main as TEC;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Editor_Test extends Controller_Test_Case {
	use With_Uopz;
	use Fee_Creator;
	use Ticket_Maker;
	use SnapshotAssertions;

	protected string $controller_class = Editor::class;

	public function get_store_data_provider(): Generator {
		yield 'new post' => [
			function (): array {
				global $pagenow;
				$pagenow = 'post-new.php';

				return [ [], [] ];
			},
		];

		yield 'existing post with tickets' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow  = 'edit.php';
				$post     = get_post( $id );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				return [ [ $ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5 ], [] ];
			},
		];

		yield 'existing post with tickets with fees' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow  = 'edit.php';
				$post     = get_post( $id );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				$fee_id_2 = $this->create_fee_for_ticket( $ticket_2, [ 'raw_amount' => 3.25 ] );
				$fee_id_4 = $this->create_fee_for_ticket( $ticket_4, [ 'raw_amount' => 5.50 ] );
				$fee_id_5 = $this->create_fee_for_ticket( $ticket_5, [ 'raw_amount' => 7.77 ] );

				return [
					[ $ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5 ],
					[ $fee_id_2, $fee_id_4, $fee_id_5 ],
				];
			},
		];

		yield 'new event' => [
			function (): array {
				$post_type = TEC::POSTTYPE;
				global $pagenow;
				$pagenow               = 'post-new.php';
				$_REQUEST['post_type'] = $post_type;

				return [ [], [] ];
			},
		];

		yield 'existing event with tickets' => [
			function (): array {
				$id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'start_date' => '+1 week',
						'duration'   => 3 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );

				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				return [ [ $ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5 ], [] ];
			},
		];

		yield 'existing event with tickets with fees' => [
			function (): array {
				$id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'start_date' => '+1 week',
						'duration'   => 3 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );

				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				$fee_id_2 = $this->create_fee_for_ticket( $ticket_2, [ 'raw_amount' => 3.25 ] );
				$fee_id_4 = $this->create_fee_for_ticket( $ticket_4, [ 'raw_amount' => 5.50 ] );
				$fee_id_5 = $this->create_fee_for_ticket( $ticket_5, [ 'raw_amount' => 7.77 ] );

				return [
					[ $ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5 ],
					[ $fee_id_2, $fee_id_4, $fee_id_5 ],
				];
			},
		];
	}

	/**
	 * @dataProvider get_store_data_provider
	 */
	public function test_get_store_data( Closure $fixture ): void {
		[ $ticket_ids, $fee_ids ] = $fixture();

		/** @var Editor $controller */
		$controller     = $this->make_controller();
		$get_store_data = Closure::bind(
			function () {
				return $this->get_store_data();
			},
			$controller,
			$controller
		);

		$json = wp_json_encode( $get_store_data(), JSON_SNAPSHOT_OPTIONS );

		$json = str_replace(
			$ticket_ids,
			'{{ticket_id}}',
			$json
		);

		$json = str_replace(
			$fee_ids,
			'{{fee_id}}',
			$json
		);
		$this->assertMatchesJsonSnapshot( $json );
	}

	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ) {
		$this->make_controller()->register();

		$this->assertTrue( Assets::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = Assets::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-order-modifiers-block-editor'      => 'build/OrderModifiers/blockEditor.js',
			'tec-tickets-order-modifiers-rest-localization' => 'build/OrderModifiers/rest.js',
			'tec-tickets-order-modifiers-block-editor-css'  => 'build/OrderModifiers/style-blockEditor.css',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	public function should_enqueue_assets_data_provider(): Generator {
		yield 'no ticket-able post types' => [
			function (): bool {
				tribe_update_option( 'ticket-able-post-types', [] );

				return false;
			},
		];

		yield 'get_post does not return post' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );
				$this->set_fn_return( 'get_post', null );

				return false;
			},
		];

		yield 'get_post returns non ticket-able post' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'page' ] );
				$this->set_fn_return( 'get_post', self::factory()->post->create_and_get() );

				return false;
			},
		];

		yield 'ticket-able post, not admin context' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );
				$this->set_fn_return( 'get_post', self::factory()->post->create_and_get() );
				$this->set_fn_return( 'is_admin', false );

				return false;
			},
		];

		yield 'ticket-able post, admin context' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );
				$this->set_fn_return( 'get_post', self::factory()->post->create_and_get() );
				$this->set_fn_return( 'is_admin', true );

				return true;
			},
		];
	}

	/**
	 * @dataProvider should_enqueue_assets_data_provider
	 */
	public function test_should_enqueue_assets( Closure $fixture ): void {
		/** @var Editor $controller */
		$controller            = $this->make_controller();
		$should_enqueue_assets = Closure::bind(
			function () {
				return $this->should_enqueue_assets();
			},
			$controller,
			$controller
		);

		$this->assertEquals( $fixture(), $should_enqueue_assets() );
	}
}
