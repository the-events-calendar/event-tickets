<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;
use Tribe__Tickets__Assets as Assets;
use Tribe__Tickets__RSVP as RSVP;

/**
 * Tests for the front-end asset enqueue gating.
 *
 * Covers SMTNC-1284: the front-end ticket styles/scripts must only load where ticket UI is
 * actually rendered, not on every tickets-enabled post type (the `page` post type is enabled by
 * default, which made these assets load on plain pages such as the WooCommerce cart and checkout).
 *
 * @see \Tribe__Tickets__Assets::should_enqueue_frontend()
 * @see \Tribe__Tickets__RSVP::enqueue_resources()
 */
class AssetsTest extends Ticket_Object_TestCase {

	/**
	 * @return Assets
	 */
	private function assets(): Assets {
		return tribe( 'tickets.assets' );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_frontend_on_ticketed_post_type_without_tickets() {
		// `post` is a tickets-enabled post type in this context, but this post has no tickets.
		$post_id = $this->factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertFalse(
			$this->assets()->should_enqueue_frontend(),
			'A tickets-enabled post with no tickets should not enqueue the front-end assets.'
		);
	}

	/**
	 * @test
	 */
	public function should_enqueue_frontend_on_post_that_has_tickets() {
		$post_id = $this->factory()->post->create();
		$this->create_rsvp_ticket( $post_id );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertTrue(
			$this->assets()->should_enqueue_frontend(),
			'A post that actually has tickets should enqueue the front-end assets.'
		);
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_frontend_on_non_ticketed_post_type() {
		// `page` is not a tickets-enabled post type in this context (only post + tribe_events are).
		$page_id = $this->factory()->post->create( [ 'post_type' => 'page' ] );
		$this->go_to( get_permalink( $page_id ) );

		$this->assertFalse(
			$this->assets()->should_enqueue_frontend(),
			'A post type that is not tickets-enabled should never enqueue the front-end assets.'
		);
	}

	/**
	 * @test
	 */
	public function filter_can_force_enqueue_frontend() {
		// Default decision is false: tickets-enabled post type, but no tickets.
		$post_id = $this->factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertFalse( $this->assets()->should_enqueue_frontend() );

		add_filter( 'tribe_tickets_assets_should_enqueue_frontend', '__return_true' );

		$this->assertTrue(
			$this->assets()->should_enqueue_frontend(),
			'The tribe_tickets_assets_should_enqueue_frontend filter should be able to force-enqueue.'
		);
	}

	/**
	 * @test
	 */
	public function filter_can_prevent_enqueue_frontend() {
		// Default decision is true: the post has tickets.
		$post_id = $this->factory()->post->create();
		$this->create_rsvp_ticket( $post_id );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertTrue( $this->assets()->should_enqueue_frontend() );

		add_filter( 'tribe_tickets_assets_should_enqueue_frontend', '__return_false' );

		$this->assertFalse(
			$this->assets()->should_enqueue_frontend(),
			'The tribe_tickets_assets_should_enqueue_frontend filter should be able to prevent enqueuing.'
		);
	}

	/**
	 * @test
	 */
	public function rsvp_resources_are_not_enqueued_on_post_without_tickets() {
		$post_id = $this->factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		/** @var RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );
		$rsvp->register_resources();

		// Start from a clean slate so the assertion does not depend on test order.
		wp_dequeue_style( 'event-tickets-rsvp' );
		wp_dequeue_script( 'event-tickets-rsvp' );

		$rsvp->enqueue_resources();

		$this->assertFalse( wp_style_is( 'event-tickets-rsvp', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'event-tickets-rsvp', 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function rsvp_resources_are_enqueued_on_post_with_tickets() {
		$post_id = $this->factory()->post->create();
		$this->create_rsvp_ticket( $post_id );
		$this->go_to( get_permalink( $post_id ) );

		/** @var RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );
		$rsvp->register_resources();
		$rsvp->enqueue_resources();

		$this->assertTrue( wp_style_is( 'event-tickets-rsvp', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'event-tickets-rsvp', 'enqueued' ) );
	}
}
