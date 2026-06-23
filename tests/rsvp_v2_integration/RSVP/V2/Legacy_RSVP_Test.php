<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as Classic_RSVP_Ticket_Maker;
use Tribe__Tickets__RSVP as Legacy_RSVP;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Tests for legacy RSVP module behavior with TC-RSVP tickets.
 */
class Legacy_RSVP_Test extends WPTestCase {
	use Ticket_Maker;
	use Classic_RSVP_Ticket_Maker;

	/**
	 * @var Legacy_RSVP
	 */
	private Legacy_RSVP $rsvp;

	public function setUp(): void {
		parent::setUp();
		$this->rsvp = tribe( Legacy_RSVP::class );
	}

	public function test_get_ticket_applies_commerce_legacy_filter_for_tc_rsvp(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$filter_called = false;
		$filter        = function ( $ticket, $event_id, $filtered_ticket_id ) use ( &$filter_called, $post_id, $ticket_id ) {
			$filter_called = true;
			$this->assertSame( $post_id, $event_id );
			$this->assertSame( $ticket_id, $filtered_ticket_id );
			$this->assertInstanceOf( Ticket_Object::class, $ticket );
			$ticket->iac = 'required';

			return $ticket;
		};

		add_filter( 'tec_tickets_commerce_get_ticket_legacy', $filter, 10, 3 );

		wp_cache_flush();

		$ticket = $this->rsvp->get_ticket( $post_id, $ticket_id );

		remove_filter( 'tec_tickets_commerce_get_ticket_legacy', $filter );

		$this->assertInstanceOf( Ticket_Object::class, $ticket );
		$this->assertSame( Constants::TC_RSVP_TYPE, $ticket->type() );
		$this->assertTrue( $filter_called, 'Legacy commerce filter should run for TC-RSVP tickets.' );
		$this->assertSame( 'required', $ticket->iac );
	}

	public function test_get_ticket_does_not_apply_commerce_legacy_filter_for_classic_rsvp(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2026-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket_id = $this->create_rsvp_ticket( $event_id, [ 'post_title' => 'Classic RSVP' ] );

		$filter_called = false;
		$filter        = function () use ( &$filter_called ) {
			$filter_called = true;

			return func_get_arg( 0 );
		};

		add_filter( 'tec_tickets_commerce_get_ticket_legacy', $filter, 10, 3 );

		$this->rsvp->get_ticket( $event_id, $ticket_id );

		remove_filter( 'tec_tickets_commerce_get_ticket_legacy', $filter );

		$this->assertFalse( $filter_called, 'Legacy commerce filter should not run for classic RSVP tickets.' );
	}

	public function test_render_rsvp_step_resolves_event_from_commerce_meta(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// TC-RSVP tickets relate to events via commerce meta, not legacy RSVP meta.
		delete_post_meta( $ticket_id, $this->rsvp->get_event_key() );

		$html = $this->rsvp->render_rsvp_step( $ticket_id, 'rsvp' );

		$this->assertNotEmpty( $html, 'Should render when event is resolved via commerce meta fallback.' );
		$this->assertStringContainsString( 'tribe-tickets__rsvp-actions-rsvp', $html );
	}

	public function test_render_rsvp_step_uses_commerce_content_template_for_tc_rsvp(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$html = $this->rsvp->render_rsvp_step( $ticket_id, 'rsvp' );

		$this->assertStringContainsString( 'tribe-tickets__rsvp-actions-rsvp', $html );
		$this->assertStringNotContainsString( 'RSVP Here', $html, 'Commerce template should not render the legacy heading.' );
	}

	public function test_frontend_filter_applies_commerce_legacy_filter_before_rendering(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$rsvp      = tribe( Module::class )->get_ticket( $post_id, $ticket_id );

		$filter_called = false;
		$filter        = function ( $ticket, $event_id, $filtered_ticket_id ) use ( &$filter_called, $post_id, $ticket_id ) {
			$filter_called = true;
			$this->assertSame( $post_id, $event_id );
			$this->assertSame( $ticket_id, $filtered_ticket_id );
			$ticket->iac = 'allowed';

			return $ticket;
		};

		add_filter( 'tec_tickets_commerce_get_ticket_legacy', $filter, 10, 3 );

		$result = apply_filters(
			'tec_tickets_front_end_rsvp_form_template_content',
			'',
			[
				'active_rsvps' => [ $rsvp ],
			],
			tribe( 'tickets.editor.template' ),
			get_post( $post_id ),
			false
		);

		remove_filter( 'tec_tickets_commerce_get_ticket_legacy', $filter );

		$this->assertTrue( $filter_called, 'Frontend should apply the legacy commerce filter for TC-RSVP tickets.' );
		$this->assertStringContainsString( 'data-iac="allowed"', $result );
	}
}
