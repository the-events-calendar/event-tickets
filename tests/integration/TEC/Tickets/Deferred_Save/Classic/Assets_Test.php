<?php

namespace TEC\Tickets\Deferred_Save\Classic;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\Assets\Assets as Stellar_Assets;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Tickets as Tickets;

class Assets_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use With_Tickets_Commerce;

	/**
	 * @test
	 */
	public function it_should_register_the_script_and_the_style(): void {
		$this->assertTrue( wp_script_is( Assets::SCRIPT, 'registered' ) );
		$this->assertTrue( wp_style_is( Assets::STYLE, 'registered' ) );
		$this->assertContains( 'event-tickets-admin-js', wp_scripts()->registered[ Assets::SCRIPT ]->deps );
	}

	/**
	 * @test
	 */
	public function it_should_enqueue_only_for_a_ticketable_post(): void {
		global $post;
		$assets = tribe( Assets::class );

		$post = get_post( static::factory()->post->create( [ 'post_type' => 'page' ] ) );
		$this->assertTrue( $assets->should_enqueue() );

		$post = get_post( static::factory()->attachment->create_object( 'image.jpg', 0, [ 'post_mime_type' => 'image/jpeg' ] ) );
		$this->assertFalse( $assets->should_enqueue() );

		$post = null;
		$this->assertFalse( $assets->should_enqueue() );
	}

	/**
	 * @test
	 */
	public function it_should_tell_the_module_how_many_fields_the_server_reads_and_why_a_change_waits(): void {
		$data = tribe( Assets::class )->localized_data();

		$this->assertSame( (int) ini_get( 'max_input_vars' ), $data['maxInputVars'] );
		$this->assertSame( (int) \Tribe__Date_Utils::get_datepicker_format_index(), $data['dateFormat'] );
		foreach ( [ 'inputLimit', 'moveBlocked', 'editBlocked', 'duplicateFailed', 'deleteConfirm', 'copySuffix', 'invalidTicket', 'listSeparator' ] as $key ) {
			$this->assertIsString( $data[ $key ] );
			$this->assertNotSame( '', $data[ $key ], $key );
		}
		$this->assertStringContainsString( '%s', $data['markerSentences']['move'], 'The destination goes inside the sentence.' );
	}

	/**
	 * @test
	 */
	public function it_should_tell_the_module_how_many_of_each_ticket_on_the_post_are_sold(): void {
		global $post;
		$post_id = static::factory()->post->create();
		$sold_id = $this->create_tc_ticket( $post_id, 10 );
		$none_id = $this->create_tc_ticket( $post_id, 20 );
		$this->create_order( [ $sold_id => 3 ] );
		$post = get_post( $post_id );

		$sold = tribe( Assets::class )->localized_data()['sold'];

		$this->assertSame( 3, $sold[ $sold_id ] );
		$this->assertSame( 0, $sold[ $none_id ] );
		$this->assertSame( Tickets::load_ticket_object( $sold_id )->qty_sold(), $sold[ $sold_id ] );
	}

	/**
	 * The assets library registers and enqueues what it holds on `init`, so unregistering must take them out of it.
	 *
	 * @test
	 */
	public function it_should_take_the_assets_out_of_the_assets_library_on_unregister(): void {
		$assets = tribe( Assets::class );
		$assets->register();

		$assets->unregister();

		$this->assertFalse( Stellar_Assets::instance()->exists( Assets::SCRIPT ) );
		$this->assertFalse( Stellar_Assets::instance()->exists( Assets::STYLE ) );
		$this->assertFalse( wp_script_is( Assets::SCRIPT, 'registered' ) );

		$assets->register();
		$this->assertTrue( Stellar_Assets::instance()->exists( Assets::SCRIPT ) );
	}

}
