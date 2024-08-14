<?php

namespace TEC\Tickets\Admin\All_Tickets;

use TEC\Tickets\Admin\All_Tickets\List_Table;

/**
 * Tests for the List_Table class.
 */
class List_TableTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var List_Table
	 */
	protected $list_table;

	public function setUp(): void {
		// before
		parent::setUp();

		$this->list_table = new List_Table();
	}

	// test
	public function test_construct() {
		$this->assertInstanceOf( List_Table::class, $this->list_table );
	}

	// test
	public function test_prepare_items() {
		$this->list_table->prepare_items();
		$this->assertEmpty( $this->list_table->items );
	}

	// test
	public function test_get_columns() {
		$columns = $this->list_table->get_columns();
		$this->assertArrayHasKey( 'name', $columns );
		$this->assertArrayHasKey( 'id', $columns );
		$this->assertArrayHasKey( 'event', $columns );
		$this->assertArrayHasKey( 'start', $columns );
		$this->assertArrayHasKey( 'end', $columns );
		$this->assertArrayHasKey( 'days_left', $columns );
		$this->assertArrayHasKey( 'price', $columns );
		$this->assertArrayHasKey( 'sold', $columns );
		$this->assertArrayHasKey( 'remaining', $columns );
		$this->assertArrayHasKey( 'sales', $columns );
	}

	// test
	public function test_get_sortable_columns() {
		$sortable_columns = $this->list_table->get_sortable_columns();
		$this->assertArrayHasKey( 'name', $sortable_columns );
		$this->assertArrayHasKey( 'id', $sortable_columns );
		$this->assertArrayNotHasKey( 'event', $sortable_columns );
		$this->assertArrayHasKey( 'start', $sortable_columns );
		$this->assertArrayHasKey( 'end', $sortable_columns );
		$this->assertArrayHasKey( 'days_left', $sortable_columns );
		$this->assertArrayHasKey( 'price', $sortable_columns );
		$this->assertArrayHasKey( 'sold', $sortable_columns );
		$this->assertArrayHasKey( 'remaining', $sortable_columns );
		$this->assertArrayHasKey( 'sales', $sortable_columns );
	}

	// test
	public function test_get_default_hidden_columns() {
		$default_hidden_columns = $this->list_table->get_default_hidden_columns();
		$this->assertNotContains( 'name', $default_hidden_columns );
		$this->assertContains( 'id', $default_hidden_columns );
		$this->assertNotContains( 'event', $default_hidden_columns );
		$this->assertContains( 'start', $default_hidden_columns );
		$this->assertNotContains( 'end', $default_hidden_columns );
		$this->assertContains( 'days_left', $default_hidden_columns );
		$this->assertNotContains( 'price', $default_hidden_columns );
		$this->assertNotContains( 'sold', $default_hidden_columns );
		$this->assertNotContains( 'remaining', $default_hidden_columns );
		$this->assertContains( 'sales', $default_hidden_columns );
	}

	// test
	public function test_column_default() {
		$item = [
			'title'    => 'Test Ticket',
			'event'    => 'Test Event',
			'status'   => 'draft',
			'price'    => 10.00,
			'quantity' => 100,
		];
		$column = 'title';
		$this->assertEquals( 'Test Ticket', $this->list_table->column_default( $item, $column ) );
		$column = 'event';
		$this->assertEquals( 'Test Event', $this->list_table->column_default( $item, $column ) );
		$column = 'status';
		$this->assertEquals( 'draft', $this->list_table->column_default( $item, $column ) );
		$column = 'price';
		$this->assertEquals( 10.00, $this->list_table->column_default( $item, $column ) );
		$column = 'quantity';
		$this->assertEquals( 100, $this->list_table->column_default( $item, $column ) );
	}
}


