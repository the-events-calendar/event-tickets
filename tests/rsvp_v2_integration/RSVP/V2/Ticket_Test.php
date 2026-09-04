<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Tickets__RSVP as Legacy_RSVP;

class Ticket_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$ticket = tribe( Ticket::class );

		$this->assertInstanceOf( Ticket::class, $ticket );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_ticket_type(): void {
		$ticket = tribe( Ticket::class );

		$this->assertSame( Constants::TC_RSVP_TYPE, $ticket->get_type() );
	}

	/**
	 * Reproduces a V1 RSVP migrated to TC: the excerpt and the `yes` meta the migration
	 * writes both survive, so the description would render without the filter.
	 *
	 * @return array{0: int, 1: int} The post ID and the ticket ID.
	 */
	private function create_migrated_rsvp_ticket(): array {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		wp_update_post(
			[
				'ID'           => $ticket_id,
				'post_excerpt' => 'Stale V1 RSVP description.',
			]
		);
		update_post_meta( $ticket_id, '_tribe_ticket_show_description', 'yes' );

		return [ $post_id, $ticket_id ];
	}

	/**
	 * @test
	 */
	public function it_should_not_show_the_description_of_a_migrated_rsvp(): void {
		[ $post_id, $ticket_id ] = $this->create_migrated_rsvp_ticket();

		$rsvp = tribe( Module::class )->get_ticket( $post_id, $ticket_id );

		$this->assertSame( 'Stale V1 RSVP description.', $rsvp->description );
		$this->assertFalse( $rsvp->show_description() );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_the_description_of_a_migrated_rsvp(): void {
		[ , $ticket_id ] = $this->create_migrated_rsvp_ticket();

		$html = tribe( Legacy_RSVP::class )->render_rsvp_step( $ticket_id, 'rsvp' );

		$this->assertNotEmpty( $html, 'render_rsvp_step should render TC-RSVP commerce content.' );
		$this->assertStringNotContainsString( 'Stale V1 RSVP description.', $html );
		$this->assertStringNotContainsString( 'tribe-tickets__rsvp-description', $html );
	}

	/**
	 * @test
	 */
	public function it_should_leave_non_rsvp_ticket_descriptions_alone(): void {
		$show = apply_filters( 'tribe_tickets_show_description', true, 0 );

		$this->assertTrue( $show );
	}
}
