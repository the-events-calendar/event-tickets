<?php

namespace Tribe\Tickets\Admin\Views;

use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Admin__Views__Ticketed as Ticketed;
use Closure;
use Generator;

class TicketedTest extends \Codeception\TestCase\WPTestCase {
	use MatchesSnapshots;
	use Ticket_Maker;

	public function ticketed_and_unticketed_counts_provider(): Generator {
		yield 'no posts' => [
			static function (): void {
			}
		];

		yield '3 unticketed posts' => [
			function (): void {
				$posts = $this->factory()->post->create_many( 3 );
			}
		];

		yield '3 ticketed posts' => [
			function (): void {
				foreach ( $this->factory()->post->create_many( 3 ) as $post ) {
					$this->create_tc_ticket( $post );
				}
			}
		];

		yield '3 ticketed and 4 unticketed posts' => [
			function (): void {
				foreach ( $this->factory()->post->create_many( 3 ) as $post ) {
					$this->create_tc_ticket( $post );
				}

				$this->factory()->post->create_many( 4 );
			}
		];
	}

	/**
	 * It should correctly report ticketed and unticketed counts
	 *
	 * @test
	 * @dataProvider ticketed_and_unticketed_counts_provider
	 */
	public function should_correctly_report_ticketed_and_unticketed_counts( Closure $fixture ): void {
		$fixture();

		$ticketed = new Ticketed( 'post' );
		$filtered = $ticketed->filter_edit_link( [] );

		$this->assertMatchesSnapshot( $filtered );
	}
}
