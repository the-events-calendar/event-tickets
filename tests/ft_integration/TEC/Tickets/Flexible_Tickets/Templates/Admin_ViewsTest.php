<?php

namespace TEC\Tickets\Flexible_Tickets\Templates;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Admin_ViewsTest extends WPTestCase {
	use SnapshotAssertions;

	public function disabled_provider() {
		return [
			'disabled' => [ true ],
			'enabled'  => [ false ],
		];
	}

	/**
	 * It should render series pass form toggle correctly
	 *
	 * @test
	 *
	 * @dataProvider disabled_provider
	 */
	public function should_render_series_pass_form_toggle_correctly( bool $disabled ): void {
		$view = new Admin_Views();

		$html = $view->template( 'series-pass-form-toggle', [ 'disabled' => $disabled ], false );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should render series passs form toggle correctly when filtering series pass name
	 *
	 * @test
	 *
	 * @dataProvider disabled_provider
	 */
	public function should_render_series_passs_form_toggle_correctly_when_filtering_series_pass_name( bool $disabled ): void {
		add_filter( 'tec_tickets_series_pass_singular_lowercase', static fn() => 'group ticket' );
		add_filter( 'tec_tickets_series_pass_singular_uppercase', static fn() => 'Group Ticket' );

		$view = new Admin_Views();

		$html = $view->template( 'series-pass-form-toggle', [ 'disabled' => $disabled ], false );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should render ticket type options correctly
	 *
	 * @test
	 */
	public function should_render_ticket_type_options_correctly(): void {
		$view = new Admin_Views();

		$html = $view->template( 'ticket-type-options', [], false );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should render ticket type options correctly when filtering series pass name
	 *
	 * @test
	 */
	public function should_render_ticket_type_options_correctly_when_filtering_series_pass_name(): void {
		add_filter( 'tec_tickets_series_pass_singular_lowercase', static fn() => 'group ticket' );
		add_filter( 'tec_tickets_series_pass_singular_uppercase', static fn() => 'Group Ticket' );

		$view = new Admin_Views();

		$html = $view->template( 'ticket-type-options', [], false );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
