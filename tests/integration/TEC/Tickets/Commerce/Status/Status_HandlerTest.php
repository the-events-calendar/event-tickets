<?php

namespace TEC\Tickets\Commerce\Status;

class Status_HandlerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @test
	 *
	 * @dataProvider status_provider
	 */
	public function it_should_return_group_of_statuses_by_slug( $status, $expected ) {
		$status_handler = tribe( Status_Handler::class );

		$expected = array_map( function ( $v ) {
			return $v->get_wp_slug();
		}, array_map( 'tribe', $expected ) );

		$st_object = tribe( $status );

		$this->assertEquals( $expected, $status_handler->get_group_of_statuses_by_slug( $st_object->get_slug() ) );
		$this->assertEquals( $expected, $status_handler->get_group_of_statuses_by_slug( '', $st_object->get_wp_slug() ) );
	}

	/**
	 * @test
	 *
	 * @dataProvider status_provider
	 */
	public function it_should_get_status_by_slug( $expected, $tests ) {
		$status_handler = tribe( Status_Handler::class );

		$tests = array_map( 'tribe', $tests );

		foreach ( $tests as $t ) {
			$this->assertInstanceOf( get_class( $t ), $status_handler->get_by_slug( $t->get_slug() ) );
			$this->assertInstanceOf( get_class( $t ), $status_handler->get_by_wp_slug( $t->get_wp_slug() ) );
			$this->assertInstanceOf( $expected, $status_handler->get_by_slug( $t->get_slug(), false ) );
			$this->assertInstanceOf( $expected, $status_handler->get_by_wp_slug( $t->get_wp_slug(), false ) );
		}

		$this->assertInstanceOf( Unsupported::class, $status_handler->get_by_slug( 'unknown' ) );
		$this->assertInstanceOf( Unsupported::class, $status_handler->get_by_wp_slug( 'unknown' ) );
		$this->assertInstanceOf( Unsupported::class, $status_handler->get_by_slug( 'unknown', false ) );
		$this->assertInstanceOf( Unsupported::class, $status_handler->get_by_wp_slug( 'unknown', false ) );

		$this->assertInstanceOf( Trashed::class, $status_handler->get_by_slug( 'trash' ) );
		$this->assertInstanceOf( Trashed::class, $status_handler->get_by_wp_slug( 'trash' ) );
		$this->assertInstanceOf( Trashed::class, $status_handler->get_by_slug( 'trash', false ) );
		$this->assertInstanceOf( Trashed::class, $status_handler->get_by_wp_slug( 'trash', false ) );
	}

	/**
	 * Data provider for status test.
	 *
	 * @return array
	 */
	public function status_provider() {
		$status = [
			[ Pending::class, [ Pending::class, Action_Required::class, Approved::class, Created::class, Not_Completed::class ] ],
			[ Completed::class, [ Completed::class ] ],
			[ Denied::class, [ Denied::class, Undefined::class ] ],
			[ Refunded::class, [ Refunded::class, Reversed::class ] ],
			[ Voided::class, [ Voided::class ] ],
		];

		foreach ( $status as $st ) {
			yield $st;
		}
	}
}
