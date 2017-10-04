<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe__Tickets__Commerce__PayPal__Transaction as Transaction;

class TransactionTest extends \Codeception\TestCase\WPTestCase {

	protected $id = 'foo';

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @return Transaction
	 */
	private function make_instance() {
		return new Transaction( $this->id );
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Transaction::class, $sut );
	}

	/**
	 * It should have a default status of undefined
	 *
	 * @test
	 */
	public function should_have_a_default_status_of_undefined() {
		$sut = $this->make_instance();
		$this->assertEquals( Transaction::$undefined_status, $sut->get_status() );
	}

	/**
	 * It should allow setting data on the transaction
	 *
	 * @test
	 */
	public function should_allow_setting_data_on_the_transaction() {
		$this->id = 'foobar';

		$sut = $this->make_instance();
		$sut->set_status( Transaction::$unregistered_status );
		$sut->set_data( 'one', 23 );
		$sut->set_data( 'bar', 'lorem dolor' );
		$sut->set_data( 'empty', '' );


		$expected = [
			'id'     => 'foobar',
			'status' => Transaction::$unregistered_status,
			'one'    => 23,
			'bar'    => 'lorem dolor',
			'empty'  => '',
		];

		$this->assertEqualSets( $expected, $sut->to_array() );

		$this->assertEquals( 23, $sut->get_data( 'one' ) );
		$this->assertEquals( 'lorem dolor', $sut->get_data( 'bar' ) );
		$this->assertEquals( '', $sut->get_data( 'empty' ) );
		$this->assertEquals( null, $sut->get_data( 'woot' ) );
		$this->assertEquals( 'bzor', $sut->get_data( 'woot', 'bzor' ) );
	}

	/**
	 * It should allow saving the transaction to database
	 *
	 * @test
	 */
	public function should_allow_saving_the_transaction_to_database() {
		$sut = $this->make_instance();
		$sut->set_data( 'foo', 'bar' );
		$sut->save();

		$expected = $sut->to_array();
		$this->assertEqualSets( $expected, Transaction::build_from_id( $this->id )->to_array() );
	}

	/**
	 * It should merge existing transaction data with new data
	 *
	 * @test
	 */
	public function should_merge_existing_transaction_data_with_new_data() {
		$sut = $this->make_instance();
		$sut->set_data( 'foo', 'bar' );
		$sut->save();

		$sut->set_data( 'bar', 'baz' );
		$sut->save();

		$expected = $sut->to_array();
		$this->assertEqualSets( $expected, Transaction::build_from_id( $this->id )->to_array() );
	}

	/**
	 * It should allow creating an instance from options using its id
	 *
	 * @test
	 */
	public function should_allow_creating_an_instance_from_options_using_its_id() {
		$sut = $this->make_instance();
		$sut->set_data( 'foo', 'bar' );
		$sut->set_data( 'bar', 23 );
		$expected = $sut->to_array();

		$sut->save();

		$from_db = Transaction::build_from_id( $this->id );

		$this->assertEqualSets( $expected, $from_db->to_array() );
	}

	/**
	 * It should return a new instance when trying to build from id and no data exists
	 *
	 * @test
	 */
	public function should_return_a_new_instance_when_trying_to_build_from_id_and_no_data_exists() {
		$from_db = Transaction::build_from_id( $this->id );

		$expected = $this->make_instance()->to_array();
		$this->assertEqualSets( $expected, $from_db->to_array() );
	}
}