<?php

// Tests for SCreen_Options class.

namespace TEC\Tickets\Admin\Tickets;

use TEC\Tickets\Admin\Tickets\Screen_Options;

/**
 * Tests for the Screen_Options class.
 */
class Screen_OptionsTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Screen_Options
	 */
	protected $screen_options;

	public function setUp(): void {
		// before
		parent::setUp();

		$this->screen_options = new Screen_Options();
	}

	// test
	public function test_construct() {
		$this->assertInstanceOf( Screen_Options::class, $this->screen_options );
	}

	// test
	public function test_filter_manage_columns() {
		$columns = tribe( List_Table::class )->get_table_columns();
		$this->assertEquals( $columns, $this->screen_options->filter_manage_columns( [] ) );
	}

	// test
	public function test_filter_screen_options_show_screen() {
		$screen = (object) [
			'id' => Page::$hook_suffix,
		];
		$this->assertTrue( $this->screen_options->filter_screen_options_show_screen( true, $screen ) );

		$wrong_screen = (object) [
			'id' => 'not_the_screen_id',
		];
		$this->assertFalse( $this->screen_options->filter_screen_options_show_screen( true, $wrong_screen ) );
	}

	// test
	public function test_filter_set_screen_options() {
		$option = Screen_Options::$per_page_user_option;
		$value = 10;
		$status = false;
		$this->assertEquals( $value, $this->screen_options->filter_set_screen_options( $status, $option, $value ) );

		$different_option = 'different_option';
		$this->assertEquals( $status, $this->screen_options->filter_set_screen_options( $status, $different_option, $value ) );
	}

	// test
	public function test_filter_default_hidden_columns() {
		$wrong_screen = (object) [
			'id' => 'not_the_screen_id',
		];
		$this->assertEmpty( $this->screen_options->filter_default_hidden_columns( [], $wrong_screen ) );

		$right_screen = (object) [
			'id' => Page::$hook_suffix,
		];
		$this->assertNotEmpty( $this->screen_options->filter_default_hidden_columns( [], $right_screen ) );
		$this->assertEquals( List_Table::get_default_hidden_columns(), $this->screen_options->filter_default_hidden_columns( [], $right_screen ) );
	}
}
