<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Date_Utils as Date_Utils;
use Tribe__Tickets__Main;

class Metabox_Test extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Order_Maker;
	use With_Uopz;

	/**
	 * Backup of the global $wp_meta_boxes, restored after each test.
	 *
	 * @var mixed
	 */
	private $wp_meta_boxes_backup;

	public function setUp(): void {
		parent::setUp();

		global $wp_meta_boxes;
		$this->wp_meta_boxes_backup = $wp_meta_boxes;
	}

	public function tearDown(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = $this->wp_meta_boxes_backup;

		parent::tearDown();
	}

	public function render_data_provider(): Generator {
		yield 'post without RSVP' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				return [ $post_id, null ];
			},
		];

		yield 'post with TC-RSVP' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$rsvp_id = $this->create_tc_rsvp_ticket( $post_id );

				return [ $post_id, $rsvp_id ];
			},
		];

		yield 'post with TC-RSVP and capacity' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$rsvp_id = $this->create_tc_rsvp_ticket(
					$post_id,
					[
						'tribe-ticket' => [
							'mode'     => 'own',
							'capacity' => 50,
						],
					]
				);

				return [ $post_id, $rsvp_id ];
			},
		];

		yield 'post with TC-RSVP and show not going enabled' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$rsvp_id = $this->create_tc_rsvp_ticket( $post_id );
				update_post_meta( $rsvp_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );

				return [ $post_id, $rsvp_id ];
			},
		];
	}

	/**
	 * Replaces post IDs with placeholders for stable snapshots.
	 *
	 * @param string $snapshot The snapshot HTML.
	 * @param array  $ids      The IDs to replace.
	 *
	 * @return string The snapshot with placeholders.
	 */
	private function placehold_post_ids( string $snapshot, array $ids ): string {
		$ids = array_filter( $ids, static fn( $id ) => $id !== null );

		return str_replace(
			array_map( 'strval', array_values( $ids ) ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	/**
	 * Replaces dynamic dates with placeholders for stable snapshots.
	 *
	 * @param string $snapshot The snapshot HTML.
	 *
	 * @return string The snapshot with date placeholders.
	 */
	private function placehold_dates( string $snapshot ): string {
		$today    = Date_Utils::build_date_object( 'today' )->format( 'n/j/Y' );
		$tomorrow = Date_Utils::build_date_object( 'tomorrow' )->format( 'n/j/Y' );

		return str_replace(
			[ $today, $tomorrow ],
			[ '{START_DATE}', '{END_DATE}' ],
			$snapshot
		);
	}

	/**
	 * @test
	 * @dataProvider render_data_provider
	 */
	public function it_should_render_metabox( Closure $fixture ): void {
		[ $post_id, $rsvp_id ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox = tribe( Metabox::class );
		$html = $metabox->render( $post_id );
		$html = $this->placehold_post_ids(
			$html,
			[
				'POST_ID' => $post_id,
				'RSVP_ID' => $rsvp_id,
			]
		);
		$html = $this->placehold_dates( $html );
		$html = str_replace( 'the-events-calendar/common', 'event-tickets/common', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_render_same_output_for_post_id_and_wp_post_object(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$post    = get_post( $post_id );
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox       = tribe( Metabox::class );
		$html_from_int = $metabox->render( $post_id );
		$html_from_obj = $metabox->render( $post );

		$this->assertSame( $html_from_int, $html_from_obj );
	}

	/**
	 * Returns the first ticket-enabled post type, used to seed the metabox global.
	 *
	 * @return string
	 */
	private function first_ticketable_post_type(): string {
		$post_types = Tribe__Tickets__Main::instance()->post_types();

		return (string) reset( $post_types );
	}

	/**
	 * Seeds the global $wp_meta_boxes `normal`/`high` group for a post type.
	 *
	 * @param string               $post_type The post type to seed.
	 * @param array<string,mixed>  $boxes     Map of box id => box definition, in registration order.
	 *
	 * @return void
	 */
	private function seed_meta_boxes( string $post_type, array $boxes ): void {
		global $wp_meta_boxes;

		$wp_meta_boxes = [
			$post_type => [
				'normal' => [
					'high' => $boxes,
				],
			],
		];
	}

	/**
	 * @test
	 *
	 * Tickets, RSVP, and (e.g.) Waitlist all register into normal/high. When another box
	 * lands between Tickets and RSVP, the RSVP box should be moved to sit right after Tickets.
	 */
	public function it_should_move_the_rsvp_box_directly_after_the_tickets_box(): void {
		$post_type = $this->first_ticketable_post_type();

		$this->seed_meta_boxes(
			$post_type,
			[
				'tribetickets'              => [ 'id' => 'tribetickets' ],
				'tribe-waitlist'            => [ 'id' => 'tribe-waitlist' ],
				'tec-tickets-commerce-rsvp' => [ 'id' => 'tec-tickets-commerce-rsvp' ],
			]
		);

		tribe( Metabox::class )->reorder_after_tickets_metabox( $post_type );

		global $wp_meta_boxes;
		$this->assertSame(
			[ 'tribetickets', 'tec-tickets-commerce-rsvp', 'tribe-waitlist' ],
			array_keys( $wp_meta_boxes[ $post_type ]['normal']['high'] )
		);
	}

	/**
	 * @test
	 *
	 * When the RSVP box already follows the Tickets box, the order must be left untouched.
	 */
	public function it_should_be_a_no_op_when_rsvp_already_follows_tickets(): void {
		$post_type = $this->first_ticketable_post_type();

		$this->seed_meta_boxes(
			$post_type,
			[
				'tribetickets'              => [ 'id' => 'tribetickets' ],
				'tec-tickets-commerce-rsvp' => [ 'id' => 'tec-tickets-commerce-rsvp' ],
				'tribe-waitlist'            => [ 'id' => 'tribe-waitlist' ],
			]
		);

		tribe( Metabox::class )->reorder_after_tickets_metabox( $post_type );

		global $wp_meta_boxes;
		$this->assertSame(
			[ 'tribetickets', 'tec-tickets-commerce-rsvp', 'tribe-waitlist' ],
			array_keys( $wp_meta_boxes[ $post_type ]['normal']['high'] )
		);
	}

	/**
	 * @test
	 *
	 * The reorder must bail on post types that cannot have tickets, leaving their boxes alone.
	 */
	public function it_should_bail_for_a_non_ticketable_post_type(): void {
		$post_type = 'tec_not_ticketable';

		$this->seed_meta_boxes(
			$post_type,
			[
				'tribetickets'              => [ 'id' => 'tribetickets' ],
				'tribe-waitlist'            => [ 'id' => 'tribe-waitlist' ],
				'tec-tickets-commerce-rsvp' => [ 'id' => 'tec-tickets-commerce-rsvp' ],
			]
		);

		tribe( Metabox::class )->reorder_after_tickets_metabox( $post_type );

		global $wp_meta_boxes;
		$this->assertSame(
			[ 'tribetickets', 'tribe-waitlist', 'tec-tickets-commerce-rsvp' ],
			array_keys( $wp_meta_boxes[ $post_type ]['normal']['high'] )
		);
	}

	/**
	 * @test
	 *
	 * With no RSVP box registered there is nothing to move, so the group is left untouched.
	 */
	public function it_should_bail_when_the_rsvp_box_is_missing(): void {
		$post_type = $this->first_ticketable_post_type();

		$this->seed_meta_boxes(
			$post_type,
			[
				'tribetickets'   => [ 'id' => 'tribetickets' ],
				'tribe-waitlist' => [ 'id' => 'tribe-waitlist' ],
			]
		);

		tribe( Metabox::class )->reorder_after_tickets_metabox( $post_type );

		global $wp_meta_boxes;
		$this->assertSame(
			[ 'tribetickets', 'tribe-waitlist' ],
			array_keys( $wp_meta_boxes[ $post_type ]['normal']['high'] )
		);
	}

	/**
	 * Captures the echoed output of Metabox::display_responses_info().
	 *
	 * @param int|\WP_Post $post_id     The event the RSVP is attached to.
	 * @param string       $ticket_type The ticket type the form is being rendered for.
	 * @param int|null     $rsvp_id     The RSVP ticket ID, or null when adding a new RSVP.
	 *
	 * @return string The trimmed output.
	 */
	private function capture_responses_info( $post_id, string $ticket_type, $rsvp_id ): string {
		ob_start();
		tribe( Metabox::class )->display_responses_info( $post_id, $ticket_type, $rsvp_id );

		return trim( (string) ob_get_clean() );
	}

	/**
	 * @test
	 */
	public function it_should_render_the_response_count_and_view_attendees_link(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_id = $this->create_tc_rsvp_ticket( $post_id );
		$this->create_order( [ $rsvp_id => 3 ] );

		$output = $this->capture_responses_info( $post_id, Constants::TC_RSVP_TYPE, $rsvp_id );

		$this->assertStringContainsString( 'tec-tickets-rsvp-responses-info__wrap', $output );
		$this->assertMatchesRegularExpression( '/tec-tickets-rsvp-total-count">\s*3\s*</', $output );
		$this->assertStringContainsString( 'View Attendees', $output );
		// "Not going" tooltip stays hidden unless show_not_going is enabled.
		$this->assertStringNotContainsString( 'dashicons-info', $output );
	}

	/**
	 * @test
	 */
	public function it_should_show_the_not_going_tooltip_when_show_not_going_is_enabled(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_id = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $rsvp_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );
		$this->create_order( [ $rsvp_id => 1 ] );

		$output = $this->capture_responses_info( $post_id, Constants::TC_RSVP_TYPE, $rsvp_id );

		$this->assertStringContainsString( 'dashicons-info', $output );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_responses_info_for_a_non_rsvp_ticket_type(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_id = $this->create_tc_rsvp_ticket( $post_id );
		$this->create_order( [ $rsvp_id => 1 ] );

		$output = $this->capture_responses_info( $post_id, 'default', $rsvp_id );

		$this->assertSame( '', $output );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_responses_info_without_an_rsvp_id(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$output = $this->capture_responses_info( $post_id, Constants::TC_RSVP_TYPE, null );

		$this->assertSame( '', $output );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_responses_info_when_there_are_no_responses(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_id = $this->create_tc_rsvp_ticket( $post_id );

		$output = $this->capture_responses_info( $post_id, Constants::TC_RSVP_TYPE, $rsvp_id );

		$this->assertSame( '', $output );
	}
}
