<?php

namespace Tribe\Tests\Tickets\Traits;

use Generator;
use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Tickets as Tickets;

/**
 * The fixtures and attack payloads both entry point suites share.
 *
 * Every attack is expressed as a payload against post A by a user, with a way to assert nothing
 * about the world changed. The suites differ only in how the payload reaches the server.
 */
trait Deferred_Save_Attacks {
	private int $post_a;
	private int $post_b;
	private int $ticket_a;
	private int $ticket_b;
	private int $ticket_b2;
	private int $ticket_b3;
	private int $attendee_b;
	private int $owner_id;

	/**
	 * Two posts, owned by an editor, each with Tickets Commerce tickets; post B also has an attendee.
	 *
	 * Posts rather than pages, so that an author can own one: authors cannot edit pages at all.
	 */
	protected function given_two_posts_with_tickets(): void {
		$this->owner_id   = static::factory()->user->create( [ 'role' => 'editor' ] );
		$this->post_a     = static::factory()->post->create( [ 'post_author' => $this->owner_id ] );
		$this->post_b     = static::factory()->post->create( [ 'post_author' => $this->owner_id ] );
		$this->ticket_a   = $this->create_tc_ticket( $this->post_a, 10, [ 'ticket_name' => 'Ticket on A' ] );
		$this->ticket_b   = $this->create_tc_ticket( $this->post_b, 20, [ 'ticket_name' => 'Ticket on B' ] );
		$this->ticket_b2  = $this->create_tc_ticket( $this->post_b, 30, [ 'ticket_name' => 'Second ticket on B' ] );
		$this->ticket_b3  = $this->create_tc_ticket( $this->post_b, 40, [ 'ticket_name' => 'Third ticket on B' ] );
		$this->attendee_b = $this->create_attendee_for_ticket( $this->ticket_b, $this->post_b );
	}

	protected function log_in_as( string $role, array $args = [] ): int {
		$user_id = static::factory()->user->create( array_merge( [ 'role' => $role ], $args ) );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function ticket_data( string $name, array $overrides = [] ): array {
		return array_merge(
			[
				'ticket_name'     => $name,
				'ticket_price'    => '10',
				'ticket_provider' => Module::class,
				'tribe-ticket'    => [ 'mode' => 'own', 'capacity' => '25' ],
			],
			$overrides
		);
	}

	/**
	 * A payload that tries every write against post A, naming post B's tickets, attendee and page.
	 *
	 * Each part names a different ticket of post B, so that the parser's rule against a ticket in two parts
	 * never refuses one before the ownership checks see it. The move of B's ticket goes to post A, which the
	 * user may edit, so only ownership refuses it; the move of A's ticket goes to B, which the user may not.
	 */
	protected function hostile_payload(): array {
		return [
			'update' => [
				$this->ticket_b   => [ 'ticket_name' => 'Hijacked B' ],
				$this->attendee_b => [ 'ticket_name' => 'Attendee as ticket' ],
				$this->post_b     => [ 'ticket_name' => 'Page as ticket' ],
			],
			'delete' => [ $this->ticket_b2 ],
			'move'   => [
				$this->ticket_a  => $this->post_b,
				$this->ticket_b3 => $this->post_a,
			],
			'create' => [ $this->ticket_data( 'Created on A' ) ],
		];
	}

	/**
	 * The entries of the hostile payload an editor of post A sees refused, as `[ part, key ]`.
	 */
	protected function hostile_payload_refusals(): array {
		return [
			[ 'update', $this->ticket_b ],
			[ 'update', $this->attendee_b ],
			[ 'update', $this->post_b ],
			[ 'delete', $this->ticket_b2 ],
			[ 'move', $this->ticket_a ],
			[ 'move', $this->ticket_b3 ],
		];
	}

	/**
	 * What the world looks like, for before/after comparisons.
	 */
	protected function snapshot(): array {
		$tickets = fn( int $post_id ) => array_map(
			fn( int $id ) => [ $id, get_the_title( $id ), (int) get_post_meta( $id, Tickets::get_ticket_provider_instance( Module::class )->get_event_key(), true ) ],
			tribe_tickets()->where( 'event', $post_id )->get_ids()
		);

		return [
			'a'          => $tickets( $this->post_a ),
			'b'          => $tickets( $this->post_b ),
			'attendee_b' => [ get_the_title( $this->attendee_b ), get_post_type( $this->attendee_b ), (int) get_post_meta( $this->attendee_b, Module::ATTENDEE_EVENT_KEY, true ) ],
			'b_title'    => get_the_title( $this->post_b ),
			'b_type'     => get_post_type( $this->post_b ),
		];
	}

	/**
	 * A role that holds `unfiltered_html` and one that does not: `ticket_add()` filters the name and description for both.
	 */
	public function roles_saving_markup(): Generator {
		yield 'administrator' => [ 'administrator' ];
		yield 'author' => [ 'author' ];
	}

	protected function markup_ticket_data(): array {
		return $this->ticket_data(
			'<b>Bold</b> <script>alert(1)</script> name',
			[
				'ticket_description' => '<p>Kept</p><script>alert(2)</script><img src=x onerror=alert(3)>',
				'ticket_sku'         => 'SKU <i>x</i>',
			]
		);
	}

	/**
	 * Saves a ticket through today's AJAX path, on a post of the current user's own so the panels it renders have
	 * nothing else to load.
	 */
	protected function save_through_ajax( array $data ): int {
		$post_id = static::factory()->post->create( [ 'post_author' => get_current_user_id() ] );
		$_POST   = [
			'post_id'     => $post_id,
			// tickets.js posts the edit form serialized: the request helper sanitizes that string, not the fields in it.
			'data'        => http_build_query( $data ),
			'ticket_type' => 'default',
			'nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
		];
		$this->assertIsArray( tribe( 'tickets.metabox' )->ajax_ticket_add( true ) );
		$_POST = [];
		$ids   = tribe_tickets()->where( 'event', $post_id )->get_ids();

		return absint( reset( $ids ) );
	}

	protected function stored_fields( int $ticket_id ): array {
		return [
			'title'   => get_post_field( 'post_title', $ticket_id, 'raw' ),
			'excerpt' => get_post_field( 'post_excerpt', $ticket_id, 'raw' ),
			'sku'     => get_post_meta( $ticket_id, '_sku', true ),
			'price'   => get_post_meta( $ticket_id, '_price', true ),
		];
	}

	protected function assert_stored_like_the_ajax_save( int $ajax_id, int $deferred_id ): void {
		$this->assertGreaterThan( 0, $ajax_id );
		$this->assertGreaterThan( 0, $deferred_id );
		$stored = $this->stored_fields( $deferred_id );
		$this->assertSame( $this->stored_fields( $ajax_id ), $stored );
		$this->assertStringContainsString( '<p>Kept</p>', $stored['excerpt'] );

		$saved_text = $stored['title'] . $stored['excerpt'];
		$this->assertStringNotContainsString( '<script>', $saved_text );
		$this->assertStringNotContainsString( 'onerror', $saved_text );
	}

	protected function assert_world_unchanged( array $before, string $message = '' ): void {
		$this->assertSame( $before, $this->snapshot(), $message );
	}

	protected function assert_b_untouched_and_a_created( array $before ): void {
		$after = $this->snapshot();
		$this->assertSame( $before['b'], $after['b'], 'Post B keeps its ticket.' );
		$this->assertSame( $before['attendee_b'], $after['attendee_b'], 'The attendee is untouched.' );
		$this->assertSame( $before['b_title'], $after['b_title'] );
		$this->assertCount( 2, $after['a'], 'Post A has its own ticket and the created one.' );
		$this->assertContains( 'Created on A', array_column( $after['a'], 1 ) );
		$this->assertSame( 'Ticket on A', get_the_title( $this->ticket_a ), 'The move to a foreign destination did not rename or move A.' );
		$this->assertContains( [ $this->ticket_a, 'Ticket on A', $this->post_a ], $after['a'] );
	}
}
