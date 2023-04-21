<?php

namespace TEC\Tickets\Admin;

use Spatie\Snapshots\MatchesSnapshots;

class Editor_DataTest extends \Codeception\TestCase\WPTestCase {
	use MatchesSnapshots;

	/**
	 * It should allow getting the HTML-escaped data.
	 *
	 * @test
	 */
	public function should_allow_getting_the_html_escaped_data(): void {
		$editor_data = new Editor_Data();

		$this->assertMatchesJsonSnapshot( json_encode( $editor_data->get_html_escaped_data() ) );
	}

	/**
	 * It should allow filtering the HTML-escaped data
	 *
	 * @test
	 */
	public function should_allow_filtering_the_html_escaped_data(): void {
		add_filter( 'tec_tickets_localized_editor_data', function ( $data ) {
			$data['test'] = 'Some new data';

			return $data;
		} );

		$editor_data = new Editor_Data();

		$this->assertMatchesJsonSnapshot( json_encode( $editor_data->get_html_escaped_data() ) );
	}

	/**
	 * It should allow getting a raw data entry
	 *
	 * @test
	 */
	public function should_allow_getting_a_raw_data_entry(): void {
		$editor_data = new Editor_Data();

		$this->assertSame(
			'Type:',
			$editor_data->get_raw_data_entry( 'ticket_name_label_default' )
		);
	}
}
