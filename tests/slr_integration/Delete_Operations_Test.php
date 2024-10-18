<?php

namespace TEC\Tickets\Seating;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Post;

class Delete_Operations_Test extends Controller_Test_Case {
	use With_Tickets_Commerce;
	use Ticket_Maker;

	protected $controller_class = Delete_Operations::class;

	public function test_delete_non_ticket_post(): void {
		$post_id    = self::factory()->post->create();
		$post_array = (array) get_post( $post_id );

		$this->make_controller()->register();

		$trashed = wp_trash_post( $post_id );

		$this->assertInstanceOf( WP_Post::class, $trashed );

		$deleted = wp_delete_post( $post_id );

		$this->assertInstanceOf( WP_Post::class, $deleted );
	}

	public function test_trash_ticket_not_asc(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$this->make_controller()->register();

		$trashed = wp_trash_post( $ticket_id );

		$this->assertInstanceOf( WP_Post::class, $trashed );
		$this->assertEquals( '1', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'some-layout-id', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );
	}

	public function test_delete_ticket_not_asc(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$this->make_controller()->register();

		$deleted = wp_delete_post( $ticket_id );

		$this->assertInstanceOf( WP_Post::class, $deleted );
		$this->assertEquals( '1', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'some-layout-id', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );
	}

	public function test_trash_asc_ticket(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
		$ticket_1 = $this->create_tc_ticket( $post_id, 10 );
		update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$ticket_2 = $this->create_tc_ticket( $post_id, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$ticket_3 = $this->create_tc_ticket( $post_id, 30 );
		update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );

		$this->make_controller()->register();

		$trashed_ticket_1 = wp_trash_post( $ticket_1 );

		$this->assertInstanceOf( WP_Post::class, $trashed_ticket_1 );
		$this->assertEquals( '1', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'some-layout-id', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );

		$trashed_ticket_2 = wp_trash_post( $ticket_2 );

		$this->assertInstanceOf( WP_Post::class, $trashed_ticket_2 );
		$this->assertEquals( '1', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'some-layout-id', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );

		$trashed_ticket_3 = wp_trash_post( $ticket_3 );

		$this->assertInstanceOf( WP_Post::class, $trashed_ticket_3 );
		$this->assertEquals( '', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( '', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );
	}

	public function test_delete_asc_ticket(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
		$ticket_1 = $this->create_tc_ticket( $post_id, 10 );
		update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$ticket_2 = $this->create_tc_ticket( $post_id, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$ticket_3 = $this->create_tc_ticket( $post_id, 30 );
		update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );

		$this->make_controller()->register();

		$deleted_ticket_1 = wp_delete_post( $ticket_1 );

		$this->assertInstanceOf( WP_Post::class, $deleted_ticket_1 );
		$this->assertEquals( '1', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'some-layout-id', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );

		$deleted_ticket_2 = wp_delete_post( $ticket_2 );

		$this->assertInstanceOf( WP_Post::class, $deleted_ticket_2 );
		$this->assertEquals( '1', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'some-layout-id', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );

		$deleted_ticket_3 = wp_delete_post( $ticket_3 );

		$this->assertInstanceOf( WP_Post::class, $deleted_ticket_3 );
		$this->assertEquals( '', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( '', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );
	}
}
