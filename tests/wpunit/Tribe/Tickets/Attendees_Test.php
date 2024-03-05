<?php

namespace Tribe\Tickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use ReflectionObject;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Attendees as Attendees;
use Tribe__Tickets__Attendees_Table;
use WP_Error;
use Tribe\Tests\Traits\With_Uopz;

class Attendees_Test extends WPTestCase {
	use RSVP_Ticket_Maker;
	use Attendee_Maker;
	use SnapshotAssertions;
	use With_Uopz;

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
		$sut->attendees_table = new Tribe__Tickets__Attendees_Table();

		// Reflection hack to call private method generate_filtered_list().
		$reflector = new ReflectionObject( $sut );

		$method = $reflector->getMethod( 'generate_filtered_list' );
		$method->setAccessible( true );

		// Generate filtered list of attendees.
		$items = $method->invoke( $sut, $post_id );

		// Sanitize list of attendees.
		$items = $sut->sanitize_csv_rows( $items );

		// Get the 'Ticket Holder Name' column from the arrays.
		$ticket_holder = wp_list_pluck( $items, 6 );

		// Get the 'Purchaser Name' column from the arrays.
		$purchaser_name = wp_list_pluck( $items, 8 );

		$this->assertEquals(
			[
				'Ticket Holder Name',
				'\'=cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
				'\'-1+1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
				'\'+1-1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
				'\'@cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			], $ticket_holder
		);

		$this->assertEquals(
			[
				'Purchaser Name',
				'\'=cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
				'\'-1+1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
				'\'+1-1|cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
				'\'@cmd|\'/C ping -t 192.0.0.1\'!\'A1\'',
			], $purchaser_name
		);
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

	public function event_details_top_fixture_provider(): Generator {
		yield 'not an existing post' => [
			function () {
				return [ PHP_INT_MAX, false ];
			}
		];

		yield 'post' => [
			function () {
				$post_id = static::factory()->post->create();

				return [ $post_id, true ];
			}
		];

		yield 'event' => [
			function () {
				$post_id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 09:00:00',
					'end_date'   => '2020-01-01 11:30:00',
				] )->create()->ID;

				return [ $post_id, true ];
			}
		];

		yield 'legit id, unregistered post type' => [
			function () {
				$post_id = static::factory()->post->create( [ 'post_type' => 'not_registered_post_type' ] );

				return [ $post_id, false ];
			}
		];
	}

	/**
	 * @dataProvider event_details_top_fixture_provider
	 */
	public function test_event_details_top( Closure $fixture ): void {
		[ $post_id, $expect_output ] = $fixture();

		ob_start();
		$attendees = new Attendees();
		$attendees->event_details_top( $post_id );
		$html = ob_get_contents();
		// Replace post IDs with a placeholder to avoid snapshot mismatches.
		$html = str_replace( $post_id, '{{POST_ID}}', $html );
		ob_end_clean();

		if ( $expect_output ) {
			$this->assertMatchesHtmlSnapshot( $html );
		} else {
			$this->assertEquals( '', $html );
		}
	}

	/**
	 * @test
	 * @dataProvider accessCheckDataProvider
	 */
	public function should_have_attendees_list_access( Closure $fixture ) {
		list( $event_id, $nonce, $type, $send_to, $expected ) = $fixture();

		$attendees = new Attendees();
		$result    = $attendees->has_attendees_list_access( $event_id, $nonce, $type, $send_to );

		if ( $expected instanceof WP_Error ) {
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertEquals( $expected->get_error_code(), $result->get_error_code() );
		} else {
			$this->assertEquals( $expected, $result );
		}
	}

	public function access_check_dat_provider() {
		yield 'invalid event id' => [
			function () {
				return [ null, 'invalid_nonce', 'user', '1', new WP_Error( 'no-event-id', 'Invalid Event ID' ) ];
			},
		];

		yield 'valid access' => [
			function () {
				$admin_user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
				wp_set_current_user( $admin_user_id );
				$this->set_fn_return( 'wp_verify_nonce', true );
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '2020-01-01 09:00:00',
						'end_date'   => '2020-01-01 11:30:00',
					]
				)->create()->ID;
				return [ $event_id, '1234567890', 'user', $admin_user_id, true ];
			},
		];

		yield 'invalid nonce' => [
			function () {
				$this->set_fn_return( 'wp_verify_nonce', false );
				return [ 123, 'invalid_nonce', 'user', '1', new WP_Error( 'nonce-fail', 'Cheatin Huh?' ) ];
			},
		];

		yield 'invalid email format' => [
			function () {
				$admin_user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
				wp_set_current_user( $admin_user_id );
				$this->set_fn_return( 'wp_verify_nonce', true );
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '2020-01-01 09:00:00',
						'end_date'   => '2020-01-01 11:30:00',
					]
				)->create()->ID;
				return [
					$event_id,
					'valid_nonce',
					'email',
					'not_an_email',
					new WP_Error( 'invalid-email', 'Invalid Email' ),
				];
			},
		];

		yield 'invalid user ID format' => [
			function () {
				$admin_user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
				wp_set_current_user( $admin_user_id );
				$this->set_fn_return( 'wp_verify_nonce', true );
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '2020-01-01 09:00:00',
						'end_date'   => '2020-01-01 11:30:00',
					]
				)->create()->ID;
				return [
					$event_id,
					'valid_nonce',
					'user',
					'not_a_number',
					new WP_Error( 'invalid-user', 'Invalid User ID' ),
				];
			},
		];
	}



}
