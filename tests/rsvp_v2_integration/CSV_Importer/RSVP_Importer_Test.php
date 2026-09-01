<?php

namespace TEC\Tickets\CSV_Importer;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\RSVP\Controller as RSVP_Controller;
use TEC\Tickets\RSVP\V2\Constants;

/**
 * TDD: RSVP CSV Import on V2 must create a TC-RSVP ticket.
 * This test is expected to FAIL until the importer is ported.
 */
class RSVP_Importer_Test extends WPTestCase {

	protected $rsvp_module;

	public function setUp(): void {
		parent::setUp();
		// Hook-verifiable version: tests control RSVP version via `test_rsvp_version` option.
		add_filter( 'tec_tickets_rsvp_version', function(): string {
			return get_option( 'test_rsvp_version', RSVP_Controller::VERSION_1 );
		}, 20 );
		update_option( 'test_rsvp_version', RSVP_Controller::VERSION_2 );

		\Tribe__Tickets__CSV_Importer__RSVP_Importer::reset_cache();
		$this->file_reader    = $this->prophesize( 'Tribe__Events__Importer__File_Reader' );
		$this->image_uploader = $this->prophesize( 'Tribe__Events__Importer__Featured_Image_Uploader' );
		$this->rsvp_module    = tribe( Module::class );
	}

	public function tearDown(): void {
		delete_option( 'test_rsvp_version' );
		remove_filter( 'tec_tickets_rsvp_version', '__return_true' ); // noop – ensures next test starts clean
		// Remove the test filter added in setUp (priority 20).
		remove_all_filters( 'tec_tickets_rsvp_version' );
		// Re-add suite bootstrap filter (v2) so remaining tests in suite keep passing.
		add_filter( 'tec_tickets_rsvp_version', static fn(): string => RSVP_Controller::VERSION_2 );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_should_import_an_rsvp_ticket_as_tc_rsvp_on_v2(): void {
		$overrides = [
			'event_name'             => 'V2 Event 1',
			'ticket_name'            => 'Ignored Name',
			'ticket_description'     => 'Ignored desc',
			'ticket_start_sale_date' => '2026-05-11',
			'ticket_start_sale_time' => '9:00 AM',
			'ticket_end_sale_date'   => '2026-05-19',
			'ticket_end_sale_time'   => '8:00 PM',
			'ticket_stock'           => '100',
		];
		$record   = $this->make_record( $overrides );
		$event_id = \Tribe__Events__API::createEvent( [ 'post_title' => 'V2 Event 1' ] );

		$sut       = $this->make_instance();
		$ticket_id = $sut->create_post( $record );

		$this->assertNotFalse( $ticket_id, 'Importer should create a ticket' );
		$this->assertIsInt( $ticket_id );

		// Must be a TC ticket, not a legacy tribe_rsvp_tickets post.
		$ticket_post = get_post( $ticket_id );
		$this->assertEquals( 'tec_tc_ticket', $ticket_post->post_type, 'V2 RSVP must be a Commerce ticket' );
		$this->assertEquals( Constants::TC_RSVP_TYPE, get_post_meta( $ticket_id, '_type', true ), 'ticket type must be tc-rsvp' );

		$ticket = $this->rsvp_module->get_ticket( $event_id, $ticket_id );
		$this->assertNotEmpty( $ticket );
		$this->assertEquals( $event_id, (int) $ticket->get_event()->ID );
		// Name is hardcoded to RSVP in V2, legacy name is moot.
		$this->assertEquals( 'RSVP', $ticket->name );
		$this->assertEquals( 100, $ticket->capacity() );
	}

	/**
	 * @test
	 */
	public function it_should_map_stock_to_limit_and_support_unlimited_v2(): void {
		$event_id = \Tribe__Events__API::createEvent( [ 'post_title' => 'V2 Event Unlimited' ] );
		$record   = $this->make_record( [
			'event_name'   => 'V2 Event Unlimited',
			'ticket_stock' => '',
		] );

		$sut       = $this->make_instance();
		$ticket_id = $sut->create_post( $record );

		$this->assertNotFalse( $ticket_id );
		$ticket = $this->rsvp_module->get_ticket( $event_id, $ticket_id );
		$this->assertNotEmpty( $ticket );
		// Unlimited: mode should be '' (no own stock). Capacity is handled as -1/'' for unlimited; we just verify it is not a limited value.
		$mode = get_post_meta( $ticket_id, \Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true );
		$this->assertEquals( '', $mode, 'unlimited RSVP should have empty stock mode' );
		$this->assertTrue( in_array( $ticket->capacity(), [ -1, '', 0, null ], true ), 'unlimited RSVP capacity should be -1/empty, got ' . var_export( $ticket->capacity(), true ) );
	}

	/**
	 * @test
	 */
	public function it_should_only_allow_one_rsvp_per_event_v2(): void {
		$event_id = \Tribe__Events__API::createEvent( [ 'post_title' => 'V2 Event Single' ] );
		$record1  = $this->make_record( [ 'event_name' => 'V2 Event Single', 'ticket_name' => 'A' ] );
		$record2  = $this->make_record( [ 'event_name' => 'V2 Event Single', 'ticket_name' => 'B' ] );

		$sut = $this->make_instance();
		$sut->create_post( $record1 );

		\Tribe__Tickets__CSV_Importer__RSVP_Importer::reset_cache();
		$sut2 = $this->make_instance();
		$this->assertTrue( $sut2->match_existing_post( $record2 ), 'V2 should match existing RSVP regardless of ticket_name – one per event' );
	}

	/**
	 * @test
	 */
	public function it_should_verify_version_via_hook(): void {
		$sut = $this->make_instance();
		$ref = new \ReflectionMethod( $sut, 'is_rsvp_v2' );
		$ref->setAccessible( true );

		update_option( 'test_rsvp_version', RSVP_Controller::VERSION_1 );
		$this->assertFalse( $ref->invoke( $sut ), 'hook should report V1 when test_rsvp_version=v1' );

		update_option( 'test_rsvp_version', RSVP_Controller::VERSION_2 );
		$this->assertTrue( $ref->invoke( $sut ), 'hook should report V2 when test_rsvp_version=v2' );
	}

	/**
	 * @test
	 */
	public function it_should_map_legacy_date_fields_to_v2_open_close_dates(): void {
		$event_id = \Tribe__Events__API::createEvent( [ 'post_title' => 'V2 Event Dates' ] );
		$record   = $this->make_record( [
			'event_name'             => 'V2 Event Dates',
			'ticket_start_sale_date' => '2026-06-01',
			'ticket_start_sale_time' => '10:00 AM',
			'ticket_end_sale_date'   => '2026-06-10',
			'ticket_end_sale_time'   => '5:30 PM',
		] );

		$sut       = $this->make_instance();
		$ticket_id = $sut->create_post( $record );

		$ticket = $this->rsvp_module->get_ticket( $event_id, $ticket_id );
		$this->assertEquals( '2026-06-01', $ticket->start_date );
		$this->assertEquals( '10:00:00', $ticket->start_time );
		$this->assertEquals( '2026-06-10', $ticket->end_date );
		$this->assertEquals( '17:30:00', $ticket->end_time );
	}

	private function make_instance(): \Tribe__Tickets__CSV_Importer__RSVP_Importer {
		$instance = new \Tribe__Tickets__CSV_Importer__RSVP_Importer( $this->file_reader->reveal(), $this->image_uploader->reveal() );
		$map = [
			'event_name',
			'ticket_name',
			'ticket_description',
			'ticket_start_sale_date',
			'ticket_start_sale_time',
			'ticket_end_sale_date',
			'ticket_end_sale_time',
			'ticket_stock',
		];
		$instance->set_map( $map );
		return $instance;
	}

	private function make_record( array $overrides = [] ): array {
		$defaults = [
			'event_name'             => 'Some Event',
			'ticket_name'            => 'Ticket 1',
			'ticket_description'     => 'Desc',
			'ticket_start_sale_date' => '2026-05-11',
			'ticket_start_sale_time' => '9:00 AM',
			'ticket_end_sale_date'   => '2026-05-19',
			'ticket_end_sale_time'   => '8:00 PM',
			'ticket_stock'           => '100',
		];
		return array_values( array_merge( $defaults, $overrides ) );
	}
}
