<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Common\StellarWP\Assets\Assets;
use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Screen;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Ticket;

class Order_Modifier_Fee_Metabox_Test extends Controller_Test_Case {
	use With_Uopz;
	use Fee_Creator;
	use Ticket_Maker;
	use SnapshotAssertions;

	protected string $controller_class = Order_Modifier_Fee_Metabox::class;

	public function asset_data_provider() {
		$assets = [
			'order-modifiers-fees-js' => '/src/resources/js/admin/order-modifiers/fees.js',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
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


	public function should_enqueue_assets_data_provider(): Generator {
		yield 'no ticket-able post types' => [
			function (): bool {
				tribe_update_option( 'ticket-able-post-types', [] );

				return false;
			}
		];

		yield 'get_post does not return post' => [
			function()	:bool{
				tribe_update_option( 'ticket-enabled-post-types', ['post', 'page'] );
				$this->set_fn_return( 'get_post', null );

				return false;
			}
		];

		yield 'get_post returns non ticket-able post' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'page' ] );
				$this->set_fn_return( 'get_post', self::factory()->post->create_and_get() );

				return false;
			}
		];

		yield 'ticket-able post, not admin context' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );
				$this->set_fn_return( 'get_post', self::factory()->post->create_and_get() );
				$this->set_fn_return( 'is_admin', false );

				return false;
			}
		];

		yield 'ticket-able post, admin context - block editor' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );
				$this->set_fn_return( 'get_post', self::factory()->post->create_and_get() );
				$this->set_fn_return( 'is_admin', true );
				$this->set_fn_return( 'get_current_screen', WP_Screen::get( 'post' ) );
				$this->set_class_fn_return( WP_Screen::class, 'is_block_editor', true );

				return false;
			}
		];

		yield 'ticket-able post, admin context - not block editor' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );
				$this->set_fn_return( 'get_post', self::factory()->post->create_and_get() );
				$this->set_fn_return( 'is_admin', true );
				$this->set_fn_return( 'get_current_screen', WP_Screen::get( 'post' ) );
				$this->set_class_fn_return( WP_Screen::class, 'is_block_editor', false );

				return true;
			}
		];
	}

	/**
	 * @dataProvider should_enqueue_assets_data_provider
	 */
	public function test_should_enqueue_assets( Closure $fixture ): void {
		$should_enqueue_assets = $fixture();

		$controller = $this->make_controller();

		$this->assertEquals( $should_enqueue_assets, $controller->should_enqueue_assets() );
	}

	/**
	 * @test
	 */
	public function it_should_add_fee_section() {
		$post_id = self::factory()->post->create();

		$ticket = $this->create_tc_ticket( $post_id, 10.0 );

		$fee_id = $this->create_fee_for_ticket( $ticket, [ 'raw_amount' => 5.27 ] );

		$this->create_fee_for_all( [ 'raw_amount' => 3.26 ] );

		$this->make_controller()->register();

		ob_start();
		do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, $ticket, 'default' );
		$this->assertMatchesHtmlSnapshot( str_replace( $fee_id, '{FEE_ID}', ob_get_clean() ) );
	}

	/**
	 * @test
	 */
	public function it_should_add_fee_section_for_fresh_ticket() {
		$post_id = self::factory()->post->create();

		$this->create_fee_for_all( [ 'raw_amount' => 3.26 ] );

		$this->make_controller()->register();

		ob_start();
		do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, null, 'ticket' );
		$this->assertMatchesHtmlSnapshot( ob_get_clean() );
	}

	/**
	 * @test
	 */
	public function it_should_add_fee_section_for_series() {
		$post_id = self::factory()->post->create();

		$this->create_fee_for_all( [ 'raw_amount' => 3.26 ] );

		$this->make_controller()->register();

		ob_start();
		do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, null, 'series_pass' );
		$this->assertMatchesHtmlSnapshot( ob_get_clean() );
	}

	/**
	 * @test
	 */
	public function it_should_not_add_fee_section_for_anything_other_than_ticket() {
		$post_id = self::factory()->post->create();

		$this->create_fee_for_all( [ 'raw_amount' => 3.26 ] );

		$this->make_controller()->register();

		ob_start();
		do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, null, 'rsvp' );
		$this->assertMatchesHtmlSnapshot( ob_get_clean() );
	}

	/**
	 * @test
	 */
	public function it_should_save_ticket_fee() {
		$post = self::factory()->post->create();
		$ticket = $this->create_tc_ticket( $post, 10.0 );
		$fee_1 = $this->create_fee( [ 'raw_amount' => 5.24 ] );
		$fee_2 = $this->create_fee( [ 'raw_amount' => 3.26 ] );

		$controller = $this->make_controller();

		// IMPORTANT! We do NOT save the values if the dataset is invalid.
		$raw_data = [
			'ticket_order_modifier_fees' => [
				$fee_1->id,
				$fee_2,
			],
		];

		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket ), $raw_data );

		$this->assertMatchesJsonSnapshot(
			str_replace(
				[
					$fee_1->id,
					$fee_2->id,
					$ticket,
				],
				[
					'{FEE_ID_1}',
					'{FEE_ID_2}',
					'{TICKET_ID}',
				],
				// WE DO NOT RETURN THEM though by this method.
				wp_json_encode( $this->make_controller( Fees::class )->get_selected_fees_for_post_by_ticket( $post ), JSON_SNAPSHOT_OPTIONS )
			)
		);

		$results = DB::get_results(
			DB::prepare(
				'SELECT modifier_id FROM %i WHERE post_id = %d',
				DB::prefix( 'tec_order_modifier_relationships' ),
				$ticket
			)
		);

		$this->assertMatchesStringSnapshot(
			str_replace(
				[
					$fee_1->id,
					$fee_2->id,
				],
				[
					'{FEE_ID_1}',
					'{FEE_ID_2}',
				],
				wp_json_encode( $results, JSON_SNAPSHOT_OPTIONS )
			)
		);
	}

	/**
	 * @test
	 */
	public function it_should_save_same_fee_to_multiple_tickets() {
		$post = self::factory()->post->create();

		$ticket_1 = $this->create_tc_ticket( $post, 10.0 );
		$ticket_2 = $this->create_tc_ticket( $post, 20.0 );

		$fee_1 = $this->create_fee_for_ticket( $ticket_1, [ 'raw_amount' => 5.24 ] );
		$fee_2 = $this->create_fee_for_ticket( $ticket_2, [ 'raw_amount' => 3.26 ] );
		$fee_3 = $this->create_fee_for_all( [ 'raw_amount' => 7.24 ] );

		$controller = $this->make_controller();

		$raw_data = [
			'ticket_order_modifier_fees' => [
				$fee_1,
				$fee_2,
			],
		];

		// Save the fees to both tickets.
		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket_1 ), $raw_data );
		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket_2 ), $raw_data );

		$ticket_1_fees = $this->make_controller( Fees::class )->get_fees_for_ticket( $ticket_1 );
		$ticket_2_fees = $this->make_controller( Fees::class )->get_fees_for_ticket( $ticket_2 );

		$this->assertCount( 2, $ticket_1_fees['selected_fees'] );
		$this->assertCount( 1, $ticket_1_fees['automatic_fees'] );
		$this->assertCount( 2, $ticket_1_fees['available_fees'] );
		$this->assertEquals( $ticket_1_fees, $ticket_2_fees );

		$results_1 = DB::get_results(
			DB::prepare(
				'SELECT modifier_id FROM %i WHERE post_id = %d',
				DB::prefix( 'tec_order_modifier_relationships' ),
				$ticket_1
			)
		);

		$results_2 = DB::get_results(
			DB::prepare(
				'SELECT modifier_id FROM %i WHERE post_id = %d',
				DB::prefix( 'tec_order_modifier_relationships' ),
				$ticket_2
			)
		);

		$this->assertEquals( $results_1, $results_2 );
	}

	/**
	 * @test
	 */
	public function it_should_save_same_fee_to_multiple_tickets_one_by_one() {
		$post = self::factory()->post->create();

		$ticket_1 = $this->create_tc_ticket( $post, 10.0 );
		$ticket_2 = $this->create_tc_ticket( $post, 20.0 );

		$fee_1 = $this->create_fee_for_ticket( $ticket_1, [ 'raw_amount' => 5.24 ] );
		$fee_2 = $this->create_fee_for_ticket( $ticket_2, [ 'raw_amount' => 3.26 ] );
		$fee_3 = $this->create_fee_for_all( [ 'raw_amount' => 7.24 ] );

		$controller = $this->make_controller();

		$raw_data = [
			'ticket_order_modifier_fees' => [
				$fee_1,
			],
		];

		// Save the fees to both tickets.
		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket_1 ), $raw_data );
		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket_2 ), $raw_data );

		$raw_data = [
			'ticket_order_modifier_fees' => [
				$fee_2,
			],
		];

		// Save the fees to both tickets.
		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket_1 ), $raw_data );
		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket_2 ), $raw_data );

		$ticket_1_fees = $this->make_controller( Fees::class )->get_fees_for_ticket( $ticket_1 );
		$ticket_2_fees = $this->make_controller( Fees::class )->get_fees_for_ticket( $ticket_2 );

		$this->assertCount( 1, $ticket_1_fees['selected_fees'] );
		$this->assertCount( 1, $ticket_1_fees['automatic_fees'] );
		$this->assertCount( 2, $ticket_1_fees['available_fees'] );
		$this->assertEquals( $ticket_1_fees, $ticket_2_fees );

		$results_1 = DB::get_results(
			DB::prepare(
				'SELECT modifier_id FROM %i WHERE post_id = %d',
				DB::prefix( 'tec_order_modifier_relationships' ),
				$ticket_1
			)
		);

		$results_2 = DB::get_results(
			DB::prepare(
				'SELECT modifier_id FROM %i WHERE post_id = %d',
				DB::prefix( 'tec_order_modifier_relationships' ),
				$ticket_2
			)
		);

		$this->assertEquals( $results_1, $results_2 );
	}

	/**
	 * @test
	 */
	public function it_should_not_save_ticket_fee_when_invalid_dataset() {
		$post = self::factory()->post->create();
		$ticket = $this->create_tc_ticket( $post, 10.0 );
		$fee_1 = $this->create_fee( [ 'raw_amount' => 5.24 ] );
		$fee_2 = $this->create_fee( [ 'raw_amount' => 3.26 ] );

		$controller = $this->make_controller();

		// IMPORTANT! We do NOT save the values if the dataset is invalid.
		// We only accept numeric or objects with the property id set and being numeric.
		// They should only be existing fees in order to save them and selectable! Not automatic for example.
		$raw_data = [
			'ticket_order_modifier_fees' => [
				$fee_1->id,
				$fee_2,
				12,
				9567654,
				'string',
				false,
				null,
				true,
				-1,
				-127485,
				314135.27,
				'sdasdsa123456831sdasdsa',
				[ 90 ],
				[ '67', 'asdsa' ],
				[ 'asffa', 78 ],
				[ true ],
			],
		];

		$controller->save_ticket_fee( $post, Tickets::load_ticket_object( $ticket ), $raw_data );

		$this->assertMatchesJsonSnapshot(
			str_replace(
				[
					$fee_1->id,
					$fee_2->id,
					$ticket,
				],
				[
					'{FEE_ID_1}',
					'{FEE_ID_2}',
					'{TICKET_ID}',
				],
				// WE DO NOT RETURN THEM though by this method.
				wp_json_encode( $this->make_controller( Fees::class )->get_selected_fees_for_post_by_ticket( $post ), JSON_SNAPSHOT_OPTIONS )
			)
		);

		$results = DB::get_results(
			DB::prepare(
				'SELECT modifier_id FROM %i WHERE post_id = %d',
				DB::prefix( 'tec_order_modifier_relationships' ),
				$ticket
			)
		);

		$this->assertMatchesStringSnapshot(
			str_replace(
				[
					$fee_1->id,
					$fee_2->id,
				],
				[
					'{FEE_ID_1}',
					'{FEE_ID_2}',
				],
				wp_json_encode( $results, JSON_SNAPSHOT_OPTIONS )
			)
		);

		$this->assertEquals( 1, did_action( 'tribe_log' ) );
	}

	/**
	 * @test
	 */
	public function it_should_delete_fees_replationship_along_with_tickets() {
		$post = self::factory()->post->create();
		$ticket = $this->create_tc_ticket( $post, 10.0 );
		$fee_1 = $this->create_fee_for_ticket( $ticket, [ 'raw_amount' => 5.24 ] );
		$fee_2 = $this->create_fee_for_ticket( $ticket, [ 'raw_amount' => 3.26 ] );

		$this->make_controller()->register();

		$current = $this->make_controller( Fees::class )->get_selected_fees_for_post_by_ticket( $post );

		$this->assertEquals(
			[
				$ticket => [
					$fee_1,
					$fee_2,
				]
			],
			$current
		);

		$this->assertEquals(
			2,
			DB::get_var(
				DB::prepare(
					'SELECT count(*) FROM %i WHERE id IN ( %d, %d )',
					DB::prefix( 'tec_order_modifiers' ),
					$fee_1,
					$fee_2
				)
			)
		);
		$this->assertEquals(
			2,
			DB::get_var(
				DB::prepare(
					'SELECT count(*) FROM %i WHERE post_id = %d',
					DB::prefix( 'tec_order_modifier_relationships' ),
					$ticket
				)
			)
		);

		tribe( Ticket::class )->delete( $post, $ticket );

		$current = $this->make_controller( Fees::class )->get_selected_fees_for_post_by_ticket( $post );

		$this->assertEmpty( $current );

		// Modifiers are not deleted!
		$this->assertEquals(
			2,
			DB::get_var(
				DB::prepare(
					'SELECT count(*) FROM %i WHERE id IN ( %d, %d )',
					DB::prefix( 'tec_order_modifiers' ),
					$fee_1,
					$fee_2
				)
			)
		);
		// Relationships are deleted though!
		$this->assertEquals(
			0,
			DB::get_var(
				DB::prepare(
					'SELECT count(*) FROM %i WHERE post_id = %d',
					DB::prefix( 'tec_order_modifier_relationships' ),
					$ticket
				)
			)
		);
	}
}
