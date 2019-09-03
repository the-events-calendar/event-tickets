<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Attendees as Attendees;

class Attendees_Test extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		$GLOBALS['hook_suffix'] = 'tribe_events_page_tickets-attendees';
	}

	private function make_instance() {
		return new Attendees();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Attendees::class, $sut );
	}

	/**
	 * It should sanitize CSV rows from generated RSVP list.
	 *
	 * @test
	 */
	public function should_sanitize_csv_rows_from_generated_rsvp_list() {
		$post_id = $this->factory->post->create();

		// Set the URL variable up like we are in the admin.
		$_GET['event_id'] = $post_id;

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
			'full_name' => '=cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		] );
		$this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
			'full_name' => '-1+1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		] );
		$this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
			'full_name' => '+1-1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		] );
		$this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
			'full_name' => '@cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		] );

		$sut = $this->make_instance();

		// Setup attendees table.
		$sut->attendees_table = new \Tribe__Tickets__Attendees_Table();

		// Reflection hack to call private method generate_filtered_list().
		$reflector = new \ReflectionObject( $sut );

		$method = $reflector->getMethod( 'generate_filtered_list' );
		$method->setAccessible( true );

		// Generate filtered list of attendees.
		$items = $method->invoke( $sut, $post_id );

		// Sanitize list of attendees.
		$items = $sut->sanitize_csv_rows( $items );

		// Get the 'Customer Name' column from the arrays.
		$full_names = wp_list_pluck( $items, 7 );

		$this->assertEquals( [
			'Customer Name',
			'\'=cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			'\'-1+1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			'\'+1-1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			'\'@cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		], $full_names );
	}

	/**
	 * It should sanitize CSV rows.
	 *
	 * @test
	 */
	public function should_sanitize_csv_rows() {
		$data = $this->get_formula_values();

		$rows                    = [];
		$expected_sanitized_rows = [];

		foreach ( $data as $row ) {
			$rows[] = [
				'some_column' => $row['value'],
			];

			$expected_sanitized_rows[] = [
				'some_column' => $row['sanitized_value'],
			];
		}

		$sut = $this->make_instance();

		// Sanitize rows.
		$sanitized_rows = $sut->sanitize_csv_rows( $rows );

		$this->assertEquals( $expected_sanitized_rows, $sanitized_rows );
	}

	/**
	 * It should sanitize a CSV value.
	 *
	 * @param string $value                    Value to be sanitized.
	 * @param string $expected_sanitized_value Expected sanitized value.
	 *
	 * @test
	 * @dataProvider get_formula_values
	 */
	public function should_sanitize_csv_value( $value, $expected_sanitized_value ) {
		$sut = $this->make_instance();

		// Sanitize list of attendees.
		$sanitized_value = $sut->sanitize_csv_value( $value );

		$this->assertEquals( $expected_sanitized_value, $sanitized_value );
	}

	/**
	 * Get formula values for testing with.
	 */
	public function get_formula_values() {
		yield 'equals sign formula' => [
			'value'           => '=cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			'sanitized_value' => '\'=cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		];

		yield 'minus sign formula' => [
			'value'           => '-1+1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			'sanitized_value' => '\'-1+1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		];

		yield 'plus sign formula' => [
			'value'           => '+1-1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			'sanitized_value' => '\'+1-1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		];

		yield 'at sign formula' => [
			'value'           => '@cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			'sanitized_value' => '\'@cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
		];
	}

}
