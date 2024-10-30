<?php

namespace TEC\Tickets\Flexible_Tickets\Templates;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Admin_ViewsTest extends WPTestCase {
	use SnapshotAssertions;

	public function template_fixture_provider(): array {
		return [
			'series-pass-edit-link'             => [
				'series-pass-edit-link',
				[
					'series_edit_link' => 'https://wordpress.test/wp-admin/post.php?post=10&action=edit',
					'helper_link'      => 'https://evnt.is/-series-passes',
				],
			],
			'series-pass-event-notice'          => [
				'series-pass-event-notice',
				[
					'series_edit_link' => 'https://wordpress.test/wp-admin/post.php?post=10&action=edit',
					'helper_link'      => 'https://evnt.is/-series-passes',
				],
			],
			'series-pass-form-toggle--disabled' => [
				'series-pass-form-toggle',
				[
					'disabled' => true,
				],
			],
			'series-pass-form-toggle--enabled'  => [
				'series-pass-form-toggle',
				[
					'disabled' => false,
				],
			],
			'series-pass-icon'                  => [ 'series-pass-icon' ],
			'series-pass-type-header'           => [ 'series-pass-type-header' ],
		];
	}

	/**
	 * It should render template correctly
	 *
	 * @test
	 * @dataProvider template_fixture_provider
	 */
	public function should_render_template_correctly( string $template, array $context = [] ): void {
		$view = new Admin_Views();

		$html = $view->template( $template, $context, false );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should render template correctly when filtering Series Pass labels
	 *
	 * @test
	 * @dataProvider template_fixture_provider
	 */
	public function should_render_template_correctly_when_filtering_series_pass_labels( string $template, array $context = [] ): void {
		add_filter(
			'tec_tickets_series_pass_singular_lowercase',
			static function (): string {
				return 'badge';
			} 
		);

		add_filter(
			'tec_tickets_series_pass_singular_uppercase',
			static function (): string {
				return 'Badge';
			} 
		);

		add_filter(
			'tec_tickets_series_pass_plural_lowercase',
			static function (): string {
				return 'badges';
			} 
		);

		add_filter(
			'tec_tickets_series_pass_plural_uppercase',
			static function (): string {
				return 'Badges';
			} 
		);

		$view = new Admin_Views();

		$html = $view->template( $template, $context, false );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
