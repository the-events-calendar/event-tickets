<?php

namespace Tribe\Tests\Tickets\Traits;

use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Tickets as Tickets;

/**
 * The fixtures and attack payloads both entry point suites share.
 *
 * Every attack is expressed as a payload against post A by a user, with a way to assert nothing
 * about the world changed. The suites differ only in how the payload reaches the server.
 */
trait Deferred_Save_Attacks {
	private array $deferred_posts = [];
	private int $post_a;
	private int $post_b;
	private int $ticket_a;
	private int $ticket_b;
	private int $attendee_b;
	private int $owner_id;

	protected function defer( int $post_id ): void {
		$this->deferred_posts[] = $post_id;
	}

	protected function enable_switch_filter(): void {
		add_filter(
			'tec_tickets_deferred_save_enabled',
			function ( bool $enabled, int $post_id ): bool {
				return in_array( $post_id, $this->deferred_posts, true ) ? true : $enabled;
			},
			10,
			2
		);
	}

	/**
	 * Two posts, owned by an editor, each with a Tickets Commerce ticket; post B also has an attendee.
	 *
	 * Posts rather than pages, so that an author can own one: authors cannot edit pages at all.
	 */
	protected function given_two_posts_with_tickets(): void {
		$this->owner_id   = static::factory()->user->create( [ 'role' => 'editor' ] );
		$this->post_a     = static::factory()->post->create( [ 'post_author' => $this->owner_id ] );
		$this->post_b     = static::factory()->post->create( [ 'post_author' => $this->owner_id ] );
		$this->ticket_a   = $this->create_tc_ticket( $this->post_a, 10, [ 'ticket_name' => 'Ticket on A' ] );
		$this->ticket_b   = $this->create_tc_ticket( $this->post_b, 20, [ 'ticket_name' => 'Ticket on B' ] );
		$this->attendee_b = $this->create_attendee_for_ticket( $this->ticket_b, $this->post_b );
		$this->defer( $this->post_a );
		$this->defer( $this->post_b );
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
	 * A payload that tries every write against post A, naming post B's ticket, attendee and page.
	 */
	protected function hostile_payload(): array {
		return [
			'update' => [
				$this->ticket_b   => [ 'ticket_name' => 'Hijacked B' ],
				$this->attendee_b => [ 'ticket_name' => 'Attendee as ticket' ],
				$this->post_b     => [ 'ticket_name' => 'Page as ticket' ],
			],
			'delete' => [ $this->ticket_b ],
			'move'   => [ $this->ticket_a => $this->post_b ],
			'create' => [ $this->ticket_data( 'Created on A' ) ],
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
