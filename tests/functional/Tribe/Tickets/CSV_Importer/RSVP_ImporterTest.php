<?php
namespace Tribe\Tickets\CSV_Importer;

class RSVP_ImporterTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->file_reader    = $this->prophesize( 'Tribe__Events__Importer__File_Reader' );
		$this->image_uploader = $this->prophesize( 'Tribe__Events__Importer__Featured_Image_Uploader' );
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
	 * @return \Tribe__Tickets__CSV_Importer__RSVP_Importer
	 */
	private function make_instance() {
		return new \Tribe__Tickets__CSV_Importer__RSVP_Importer( $this->file_reader->reveal(), $this->image_uploader->reveal() );
	}

}