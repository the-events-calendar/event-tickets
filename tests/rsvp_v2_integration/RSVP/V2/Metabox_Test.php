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
use Tribe__Date_Utils as Date_Utils;

class Metabox_Test extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use With_Uopz;

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
}
