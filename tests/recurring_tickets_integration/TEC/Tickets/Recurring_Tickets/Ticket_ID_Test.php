<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use Generator;
use InvalidArgumentException;

/**
 * A table ticket's ID is the base plus its row ID, and nothing else may ever read as one.
 */
class Ticket_ID_Test extends WPTestCase {
	public function table_ticket_id_provider(): Generator {
		yield 'the base' => [ 1000000000, true ];
		yield 'one below the base' => [ 999999999, false ];
		yield 'a post ID' => [ 13488, false ];
		yield 'zero' => [ 0, false ];
		yield 'negative' => [ -1000000001, false ];
		yield 'digit string at the base' => [ '1000000007', true ];
		yield 'digit string below the base' => [ '999999999', false ];
		yield 'float above the base' => [ 1000000007.5, false ];
		yield 'non-numeric string' => [ 'abc', false ];
		yield 'numeric prefix' => [ '1000000007abc', false ];
		yield 'null' => [ null, false ];
		yield 'array' => [ [ 1000000007 ], false ];
	}

	/**
	 * @test
	 * @dataProvider table_ticket_id_provider
	 *
	 * @param mixed $id The value to check.
	 */
	public function it_should_tell_a_table_ticket_id_apart( $id, bool $expected ): void {
		$this->assertSame( $expected, Ticket_ID::is_table_ticket( $id ) );
	}

	/**
	 * @test
	 */
	public function it_should_convert_a_row_id_to_a_ticket_id_and_back(): void {
		$ticket_id = Ticket_ID::from_row_id( 42 );

		$this->assertSame( 1000000042, $ticket_id );
		$this->assertSame( 42, Ticket_ID::to_row_id( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_to_convert_a_post_id_to_a_row_id(): void {
		$this->expectException( InvalidArgumentException::class );

		Ticket_ID::to_row_id( 13488 );
	}

	/**
	 * @test
	 */
	public function it_should_convert_the_base_to_row_zero_which_names_no_row(): void {
		// The base is a table ticket ID, so loaders must not throw on it: it names row 0, and rows start at 1.
		$this->assertSame( 0, Ticket_ID::to_row_id( 1000000000 ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_to_convert_a_row_id_below_one(): void {
		$this->expectException( InvalidArgumentException::class );

		Ticket_ID::from_row_id( 0 );
	}

	/**
	 * @test
	 */
	public function it_should_use_a_filtered_base(): void {
		add_filter( 'tec_tickets_recurring_tickets_ticket_id_base', static fn() => 2000000000 );

		$this->assertSame( 2000000000, Ticket_ID::base() );
		$this->assertFalse( Ticket_ID::is_table_ticket( 1000000000 ) );
		$this->assertSame( 2000000001, Ticket_ID::from_row_id( 1 ) );
		$this->assertSame( 1, Ticket_ID::to_row_id( 2000000001 ) );
	}
}
