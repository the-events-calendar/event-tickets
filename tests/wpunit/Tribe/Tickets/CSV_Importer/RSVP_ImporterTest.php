<?php

namespace Tribe\Tickets\CSV_Importer;

class RSVP_ImporterTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \Tribe__Tickets__RSVP
	 */
	protected $rsvp_tickets;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		\Tribe__Tickets__CSV_Importer__RSVP_Importer::reset_cache();
		$this->file_reader    = $this->prophesize( 'Tribe__Events__Importer__File_Reader' );
		$this->image_uploader = $this->prophesize( 'Tribe__Events__Importer__Featured_Image_Uploader' );
		$this->rsvp_tickets   = \Tribe__Tickets__RSVP::get_instance();
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$this->assertInstanceOf( 'Tribe__Tickets__CSV_Importer__RSVP_Importer', $this->make_instance() );
	}

	/**
	 * @test
	 * it should import an RSVP ticket
	 */
	public function it_should_import_an_rsvp_ticket() {
		$overrides = [
			'event_name'             => 'Event 1',
			'ticket_name'            => 'Ticket 1',
			'ticket_description'     => 'The first RSVP Ticket Entry',
			'ticket_start_sale_date' => '2016-05-11',
			'ticket_start_sale_time' => '9:00 AM',
			'ticket_end_sale_date'   => '2016-05-19',
			'ticket_end_sale_time'   => '8:00 PM',
			'ticket_stock'           => '100',
		];
		$record    = $this->make_record( $overrides );
		$event_id  = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 1' ] );

		$sut       = $this->make_instance();
		$ticket_id = $sut->create_post( $record );

		$this->assertNotFalse( $ticket_id );

		$ticket = $this->rsvp_tickets->get_ticket( $event_id, $ticket_id );

		$this->assertNotEmpty( $ticket );

		$this->assertEquals( $event_id, $ticket->get_event()->ID );
		$this->assertEquals( 'Ticket 1', $ticket->name );
		$this->assertEquals( 'The first RSVP Ticket Entry', $ticket->description );
		$this->assertEquals( '2016-05-11', $ticket->start_date );
		$this->assertEquals( '2016-05-19', $ticket->end_date );
		$this->assertEquals( '09:00:00', $ticket->start_time );
		$this->assertEquals( '20:00:00', $ticket->end_time );
		$this->assertEquals( '100', $ticket->stock() );
	}

	/**
	 * @test
	 * it should match an existing ticket by same event name and ticket name
	 */
	public function it_should_match_an_existing_ticket_by_same_event_name_and_ticket_name() {
		$overrides = [
			'event_name'  => 'Event 2',
			'ticket_name' => 'Ticket 21',
		];
		$record    = $this->make_record( $overrides );
		$event_id  = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 2' ] );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $record );

		$this->assertTrue( $match );
	}

	/**
	 * @test
	 * it should match an existing ticket by same event ID and ticket name
	 */
	public function it_should_match_an_existing_ticket_by_same_event_id_and_ticket_name() {
		$event_id  = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 3' ] );
		$overrides = [
			'event_name'  => $event_id,
			'ticket_name' => 'Ticket 22',
		];
		$record    = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $record );

		$this->assertTrue( $match );
	}

	/**
	 * @test
	 * it should match an existing ticket when importing by event name and ID
	 */
	public function it_should_match_an_existing_ticket_when_importing_by_event_name_and_id() {
		$event_id  = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 4' ] );
		$overrides = [
			'event_name'  => $event_id,
			'ticket_name' => 'Ticket 23',
		];
		$record    = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $this->make_record( [ 'event_name' => 'Event 4', 'ticket_name' => 'Ticket 23' ] ) );

		$this->assertTrue( $match );
	}

	/**
	 * @test
	 * it should match an existing ticket when importing by event name and slug
	 */
	public function it_should_match_an_existing_ticket_when_importing_by_event_name_and_slug() {
		$event_id  = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 5', 'post_name' => 'event-5' ] );
		$overrides = [
			'event_name'  => 'Event 5',
			'ticket_name' => 'Ticket 24',
		];
		$record    = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $this->make_record( [ 'event_name' => 'event-5', 'ticket_name' => 'Ticket 24' ] ) );

		$this->assertTrue( $match );
	}

	/**
	 * @test
	 * it should match an existing ticket when importing by event slug and ID
	 */
	public function it_should_match_an_existing_ticket_when_importing_by_event_slug_and_id() {
		$event_id  = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 6', 'post_name' => 'event-6' ] );
		$overrides = [
			'event_name'  => $event_id,
			'ticket_name' => 'Ticket 25',
		];
		$record    = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $this->make_record( [ 'event_name' => 'event-6', 'ticket_name' => 'Ticket 25' ] ) );

		$this->assertTrue( $match );
	}


	/**
	 * @test
	 * it should match an existing ticket by same event slug and ticket name
	 */
	public function it_should_match_an_existing_ticket_by_same_event_slug_and_ticket_name() {
		\Tribe__Events__API::createEvent( [ 'post_title' => 'Event 7', 'post_name' => 'event-7' ] );
		$overrides = [
			'event_name'  => 'event-7',
			'ticket_name' => 'Ticket 26',
		];
		$record    = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $this->make_record( [ 'event_name' => 'event-7', 'ticket_name' => 'Ticket 26' ] ) );

		$this->assertTrue( $match );
	}

	/**
	 * @test
	 * it should not match existing ticket when ticket has same name and different event name
	 */
	public function it_should_not_match_existing_ticket_when_ticket_has_same_name_and_different_event_name() {
		\Tribe__Events__API::createEvent( [ 'post_title' => 'Event 8' ] );
		\Tribe__Events__API::createEvent( [ 'post_title' => 'Event 9' ] );
		$overrides = [
			'event_name'  => 'Event 8',
			'ticket_name' => 'Ticket 27',
		];
		$record    = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $this->make_record( [ 'event_name' => 'Event 9', 'ticket_name' => 'Ticket 27' ] ) );

		$this->assertFalse( $match );
	}

	/**
	 * @test
	 * it should not match existing ticket when ticket has same name and different event ID
	 */
	public function it_should_not_match_existing_ticket_when_ticket_has_same_name_and_different_event_id() {
		$event_id_1 = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 10' ] );
		$event_id_2 = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 11' ] );
		$overrides  = [
			'event_name'  => $event_id_1,
			'ticket_name' => 'Ticket 28',
		];
		$record     = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $this->make_record( [ 'event_name' => $event_id_2, 'ticket_name' => 'Ticket 28' ] ) );

		$this->assertFalse( $match );
	}

	/**
	 * @test
	 * it should not match existing ticket when ticket has same name and different event slug
	 */
	public function it_should_not_match_existing_ticket_when_ticket_has_same_name_and_different_event_slug() {
		\Tribe__Events__API::createEvent( [ 'post_title' => 'Event 12', 'post_name' => 'event-12' ] );
		\Tribe__Events__API::createEvent( [ 'post_title' => 'Event 13', 'post_name' => 'event-13' ] );
		$overrides = [
			'event_name'  => 'event-12',
			'ticket_name' => 'Ticket 29',
		];
		$record    = $this->make_record( $overrides );

		$sut = $this->make_instance();
		$sut->create_post( $record );
		$match = $sut->match_existing_post( $this->make_record( [ 'event_name' => 'event-13', 'ticket_name' => 'Ticket 29' ] ) );

		$this->assertFalse( $match );
	}

	/**
	 * @test
	 * it should not update a ticket when reimporting
	 */
	public function it_should_not_update_a_ticket_when_reimporting() {
		$event_id             = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 14' ] );
		$first_import_record  = $this->make_record( [
			'event_name'  => 'Event 14',
			'ticket_name' => 'Ticket 30',
		] );
		$second_import_record = $this->make_record( [
			'event_name'             => 'Event 14',
			'ticket_name'            => 'Ticket 30',
			'ticket_description'     => 'Updated description',
			'ticket_start_sale_date' => 'May 12, 2016',
			'ticket_start_sale_time' => '10:00 AM',
			'ticket_end_sale_date'   => 'May 21, 2016',
			'ticket_end_sale_time'   => '9:00 PM',
			'ticket_stock'           => '50',
		] );

		$sut_1     = $this->make_instance();
		$ticket_id = $sut_1->create_post( $first_import_record );

		\Tribe__Tickets__CSV_Importer__RSVP_Importer::reset_cache();

		$sut_2 = $this->make_instance();
		$sut_2->update_post( $ticket_id, $second_import_record );

		$ticket = $this->rsvp_tickets->get_ticket( $event_id, $ticket_id );

		$this->assertEquals( $event_id, $ticket->get_event()->ID );
		$this->assertEquals( 'Ticket 30', $ticket->name );
		$this->assertEquals( 'The first RSVP Ticket Entry', $ticket->description );
		$this->assertEquals( '2016-05-11', $ticket->start_date );
		$this->assertEquals( '2016-05-19', $ticket->end_date );
		$this->assertEquals( '09:00:00', $ticket->start_time );
		$this->assertEquals( '20:00:00', $ticket->end_time );
		$this->assertEquals( '100', $ticket->stock() );
	}

	/**
	 * @test
	 * it should mark record as invalid if referring non existing event name
	 */
	public function it_should_mark_record_as_invalid_if_referring_non_existing_event_name() {
		$sut = $this->make_instance();

		$record = $this->make_record( [ 'event_name' => 'Not an existing event' ] );
		$out    = $sut->is_valid_record( $record );

		$this->assertFalse( $out );
	}

	/**
	 * @test
	 * it should mark record as invalid if referring non existing event slug
	 */
	public function it_should_mark_record_as_invalid_if_referring_non_existing_event_slug() {
		$sut = $this->make_instance();

		$out = $sut->is_valid_record( $this->make_record( [ 'event_name' => 'foo-bar-baz' ] ) );

		$this->assertFalse( $out );
	}

	/**
	 * @test
	 * it should mark record as invalid if referring non existing event ID
	 */
	public function it_should_mark_record_as_invalid_if_referring_non_existing_event_id() {
		$sut = $this->make_instance();

		$out = $sut->is_valid_record( $this->make_record( [ 'event_name' => 12342342 ] ) );

		$this->assertFalse( $out );
	}

	/**
	 * @test
	 * it should mark record as invalid if referring recurring event
	 */
	public function it_should_mark_record_as_invalid_if_referring_recurring_event() {
		$event_id = \Tribe__Events__API::createEvent( [ 'post_title' => 'Event 15' ] );
		add_filter( 'tribe_is_recurring_event', function ( $recurring, $post_id ) use ( $event_id ) {
			return $post_id ===$event_id ? true : $recurring;
		}, 10, 2 );

		$sut = $this->make_instance();

		$out = $sut->is_valid_record( $this->make_record( [ 'event_name' => $event_id ] ) );

		$this->assertFalse( $out );
	}


	/**
	 * @return \Tribe__Tickets__CSV_Importer__RSVP_Importer
	 */
	private function make_instance() {
		$instance = new \Tribe__Tickets__CSV_Importer__RSVP_Importer( $this->file_reader->reveal(), $this->image_uploader->reveal(), $this->rsvp_tickets );

		$map = array(
			'event_name',
			'ticket_name',
			'ticket_description',
			'ticket_start_sale_date',
			'ticket_start_sale_time',
			'ticket_end_sale_date',
			'ticket_end_sale_time',
			'ticket_stock',
		);

		$instance->set_map( $map );

		return $instance;
	}

	/**
	 * @param array $overrides
	 *
	 * @return array
	 */
	private function make_record( array $overrides = [] ) {
		$defaults = [
			'event_name'             => 'Some Event',
			'ticket_name'            => 'Ticket 1',
			'ticket_description'     => 'The first RSVP Ticket Entry',
			'ticket_start_sale_date' => '2016-05-11',
			'ticket_start_sale_time' => '9:00 AM',
			'ticket_end_sale_date'   => '2016-05-19',
			'ticket_end_sale_time'   => '8:00 PM',
			'ticket_stock'           => '100',
		];

		return array_values( array_merge( $defaults, $overrides ) );
	}

}