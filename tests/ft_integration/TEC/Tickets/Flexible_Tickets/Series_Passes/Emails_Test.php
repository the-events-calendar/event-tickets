<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Closure;
use Generator;
use stdClass;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Emails\Admin\Emails_Tab;
use TEC\Tickets\Emails\Admin\Preview_Modal;
use TEC\Tickets\Emails\Admin\Settings;
use TEC\Tickets\Emails\Email\Ticket;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\Email_Handler;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Emails\Series_Pass;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Settings;

class Emails_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Series_Pass_Factory;
	use Order_Maker;

	protected string $controller_class = Emails::class;

	/**
	 * It should register the Series Pass email template among available templates
	 *
	 * @test
	 */
	public function should_register_the_series_pass_email_template_among_available_templates(): void {
		$email_handler = tribe( Email_Handler::class );
		$this->assertCount( 0, array_filter(
				$email_handler->get_emails(),
				fn( Email_Abstract $email ) => $email instanceof Series_Pass )
		);

		$this->make_controller()->register();

		$emails                = $email_handler->get_emails();
		$ticket_email_position = null;
		foreach ( $emails as $position => $email ) {
			if ( $email instanceof Ticket ) {
				$ticket_email_position = $position;
				break;
			}
		}

		$this->assertNotNull( $ticket_email_position );
		$this->assertInstanceOf( Series_Pass::class, $emails[ $ticket_email_position + 1 ] );
	}

	/**
	 * It should correctly render the email settings tab
	 *
	 * @test
	 */
	public function should_correctly_render_the_email_settings_tab(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Simulate a request to get the Emails > Series Pass Email section.
		$_GET = [
			'page' => 'tec-tickets-settings',
			'tab'  => 'emails',
		];

		$this->make_controller()->register();

		$settings = tribe( Tribe__Settings::class );

		ob_start();
		$settings->initTabs();
		$emails_tab = tribe( Emails_Tab::class );
		$tab        = $emails_tab->register_tab( 'tec-tickets-settings' );
		$tab->doContent();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should correctly render the series pass settings section
	 *
	 * @test
	 */
	public function should_correctly_render_the_series_pass_settings_section(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Simulate a request to get the Emails > Series Pass Email section.
		$_GET = [
			'page'    => 'tec-tickets-settings',
			'tab'     => 'emails',
			'section' => 'tec_tickets_emails_series-pass',
		];

		$this->make_controller()->register();

		ob_start();
		$emails_tab = tribe( Emails_Tab::class );
		$tab        = $emails_tab->register_tab( 'tec-tickets-settings' );
		$tab->doContent();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function preview_email_data_provider(): Generator {
		yield 'default values' => [
			fn() => []
		];

		yield 'changed subject, heading, additional content' => [
			function () {
				return [
					'subject'    => 'Welcome to {series_name}',
					'heading'    => 'Hey {attendee_name}, here is your ticket to {series_name}',
					'addContent' => 'Terms and conditions apply.'
				];
			}
		];

		yield 'no series excerpt, no upcoming events list' => [
			function () {
				return [
					'includeSeriesExcerpt' => 'false',
					'showEventsInEmail'    => 'false'
				];
			}
		];
	}

	/**
	 * @dataProvider preview_email_data_provider
	 */
	public function test_preview_series_pass_email( Closure $fixture ): void {
		$overrides = $fixture();
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Set up the request to preview the Series Pass email.
		$request = array_merge( [
			'action'               => 'tribe_tickets_admin_manager',
			'request'              => 'tec_tickets_preview_email',
			'currentEmail'         => tribe( Series_Pass::class )->id,
			'useTicketEmail'       => 'false',
			'enabled'              => 'true',
			'subject'              => 'Your Series Pass to {series_name}',
			'heading'              => 'Here\\\'s your series pass, {attendee_name}!',
			'includeSeriesExcerpt' => 'true',
			'showEventsInEmail'    => 'true',
			'addContent'           => '',
			'nonce'                => '8168736eb1',
		], $overrides );

		$controller = $this->make_controller();
		$controller->register();

		$preview_modal = tribe( Preview_Modal::class );
		$modal_content = $preview_modal->get_modal_content_ajax( '', $request );

		$this->assertMatchesHtmlSnapshot( $modal_content );
	}

	private function extract_json_ld_from_message( $message ): ?stdClass {
		preg_match( '~<script type="application/ld\\+json">(?<jsonld>.*?)</script>~us', $message, $matches );
		$json_ld = $matches['jsonld'] ?? null;
		$this->assertNotNull( $json_ld );

		return json_decode( $json_ld )[0] ?? null;
	}

	/**
	 * It should send Series Pass email using Series Pass email handler
	 *
	 * @test
	 */
	public function should_send_series_pass_email_using_series_pass_email_handler(): void {
		// Become Administrator;
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [ 'post_type' => Series::POSTTYPE ] );
		// Create 6 Events part of the Series.
		$public_event_ids = [];
		foreach ( range( 1, 6 ) as $k ) {
			$public_event_ids[] = tribe_events()->set_args( [
				'title'      => "Public Event $k",
				'start_date' => "+{$k} day 10:00",
				'duration'   => 3 * HOUR_IN_SECONDS,
				'status'     => 'publish',
				'series'     => $series_id,
			] )->create()->ID;
		}
		// Create 2 private Events part of the Series.
		$private_event_ids = [];
		foreach ( range( 1, 2 ) as $k ) {
			$private_event_ids[] = tribe_events()->set_args( [
				'title'      => "Private Event $k",
				'start_date' => "+{$k} day 10:00",
				'duration'   => 3 * HOUR_IN_SECONDS,
				'status'     => 'private',
				'series'     => $series_id,
			] )->create()->ID;
		}
		// Create 2 Events that are not part of the Series.
		$other_event_ids = [];
		foreach ( range( 1, 2 ) as $k ) {
			$other_event_ids[] = tribe_events()->set_args( [
				'title'      => "Other Event $k",
				'start_date' => "+{$k} day 10:00",
				'duration'   => 3 * HOUR_IN_SECONDS,
				'status'     => 'private',
			] )->create()->ID;
		}
		// Create a TC Series Pass for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		// Create an attendee for the Series Pass.
		$order_id = $this->create_order( [ $series_pass_id => 1 ], [
			'purchaser_email' => 'attendee@example.com'
		] )->ID;
		// Intercept sent emails to log them.
		$sent_emails = [];
		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $message, $headers = '', $attachments = array() ) use ( &$sent_emails ) {
			$sent_emails[] = [
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => $attachments,
			];

			return true;
		}, true );
		$args     = [
			'subject'            => false,
			'from_name'          => false,
			'from_email'         => false,
			'provider'           => 'tc',
			'post_id'            => $series_id,
			'order_id'           => $order_id,
			'send_purchaser_all' => true,
		];
		$commerce = Module::get_instance();

		$attendees                     = $commerce->get_attendees_from_module(
			tribe_attendees()->where( 'event', $series_id )->get_ids(),
			$series_id
		);
		$series_pass_attendee_id       = $attendees[0]['ID'];
		$series_pass_attendee_security = $attendees[0]['security'];
		$public_event_1                = tribe_get_event( $public_event_ids[0] );

		$controller = $this->make_controller();
		$controller->register();

		$commerce->send_tickets_email_for_attendee( 'attendee@example.com', $attendees, $args );

		$this->assertCount( 1, $sent_emails );
		$series_pass_mail = $sent_emails[0];
		$message          = $series_pass_mail['message'];
		$json_ld          = $this->extract_json_ld_from_message( $message );
		$this->assertEquals( $public_event_1->post_title, $json_ld->reservationFor->name );
		$first_five_public_event_provisional_ids = array_slice(
			Occurrence::where_in( 'post_id', $public_event_ids )->map( fn( Occurrence $o ) => $o->provisional_id )
			, 0,
			5
		);
		$this->assertEqualSets(
			$first_five_public_event_provisional_ids,
			$controller->get_upcoming_events_list()->get_event_ids()
		);
	}

	public function sent_email_data_provider(): Generator {
		/** @var Series_Pass $email */
		$email = tribe( Series_Pass::class );

		yield 'default settings' => [
			fn() => [ true, false ]
		];

		yield 'changed subject, heading, additional content' => [
			function () use ( $email ) {
				tribe_update_option( $email->get_option_key( 'subject' ), 'Welcome to {series_name}' );
				tribe_update_option( $email->get_option_key( 'heading' ), 'Hey {attendee_name}, here is your ticket to {series_name}' );
				tribe_update_option( $email->get_option_key( 'additional-content' ), 'Terms and conditions apply.' );

				return [ true, false ];
			}
		];

		yield 'no series excerpt, no upcoming events list' => [
			function () use ( $email ) {
				tribe_update_option( $email->get_option_key( 'include-series-excerpt' ), false );
				tribe_update_option( $email->get_option_key( 'show-events-in-email' ), false );

				return [ true, false ];
			}
		];

		yield 'no upcoming events' => [
			fn() => [ false, false ]
		];

		yield 'setting upcoming events count to 6' => [
			function () {
				add_filter(
					'tec_tickets_flexible_tickets_series_pass_email_upcoming_events_list_count',
					fn() => 6
				);

				return [ true, false ];
			}
		];

		yield 'setting upcoming events count to 2' => [
			function () {
				add_filter(
					'tec_tickets_flexible_tickets_series_pass_email_upcoming_events_list_count',
					fn() => 2
				);

				return [ true, false ];
			}
		];

		yield 'with images' => [
			fn() => [ true, true ]
		];
	}

	/**
	 * @dataProvider sent_email_data_provider
	 */
	public function test_send_series_pass_email( Closure $fixture ): void {
		[ $has_upcoming_events, $include_images ] = $fixture();
		// Become Administrator;
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [ 'post_type' => Series::POSTTYPE ] );
		// Create 6 Events part of the Series.
		$public_event_ids = [];
		foreach ( range( 1, 6 ) as $k ) {
			$public_event_ids[] = tribe_events()->set_args( [
				'title'      => "Public Event $k",
				'start_date' => "2022-10-2{$k} 10:00:00",
				'duration'   => 3 * HOUR_IN_SECONDS,
				'status'     => 'publish',
				'series'     => $series_id,
			] )->create()->ID;
		}
		// If images should be included, add a header image and a thumbnail to the first upcoming Event.
		if ( $include_images ) {
			$thumbnail    = codecept_root_dir( 'src/resources/images/series-pass-example-series-thumbnail.png' );
			$thumbnail_id = static::factory()->attachment->create_upload_object( $thumbnail, $public_event_ids[0] );
			wp_update_post( [ 'ID' => $thumbnail_id, 'post_content' => 'Description' ] );
			update_post_meta( $thumbnail_id, '_wp_attachment_image_alt', 'Alt text' );
			set_post_thumbnail( $public_event_ids[0], $thumbnail_id );
			$series_header_image = codecept_root_dir( 'src/resources/images/series-pass-example-header-image.png' );
			$header_image_id     = static::factory()->attachment->create_upload_object( $series_header_image, $series_id );
			tribe_update_option( Settings::$option_header_image_url, get_permalink( $header_image_id ) );
		}
		// Create a TC Series Pass for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		// Create an attendee for the Series Pass.
		$order_id = $this->create_order( [ $series_pass_id => 1 ], [
			'purchaser_email' => 'attendee@example.com'
		] )->ID;
		// Intercept sent emails to log them.
		$sent_emails = [];
		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $message, $headers = '', $attachments = array() ) use ( &$sent_emails ) {
			$sent_emails[] = [
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => $attachments,
			];

			return true;
		}, true );
		$args     = [
			'subject'            => false,
			'from_name'          => false,
			'from_email'         => false,
			'provider'           => 'tc',
			'post_id'            => $series_id,
			'order_id'           => $order_id,
			'send_purchaser_all' => true,
		];
		$commerce = Module::get_instance();

		$attendees                     = $commerce->get_attendees_from_module(
			tribe_attendees()->where( 'event', $series_id )->get_ids(),
			$series_id
		);
		$series_pass_attendee_id       = $attendees[0]['ID'];
		$series_pass_attendee_security = $attendees[0]['security'];
		$public_event_1                = tribe_get_event( $public_event_ids[0] );
		// Mock the upcoming Events results.
		add_filter(
			'tec_tickets_flexible_tickets_series_pass_email_upcoming_events',
			function ( $fetched, $series_id, $num ) use ( $has_upcoming_events, $public_event_ids ) {
				if ( $has_upcoming_events ) {
					return [ array_slice( $public_event_ids, 0, $num ), 6 ];
				}

				return [ [], 0 ];
			}, 10, 3
		);

		$controller = $this->make_controller();
		$controller->register();

		$commerce->send_tickets_email_for_attendee( 'attendee@example.com', $attendees, $args );

		$this->assertCount( 1, $sent_emails );
		$series_pass_mail = $sent_emails[0];

		$subject = $series_pass_mail['subject'];
		$message = $series_pass_mail['message'];

		$search  = [
			$series_pass_attendee_security,
			$public_event_1->ID,
			$public_event_1->dates->start->format( 'c' ),
			$public_event_1->dates->end->format( 'c' ),
			$public_event_ids[1],
			$public_event_ids[2],
			$public_event_ids[3],
			$public_event_ids[4],
			$public_event_ids[5],
			$order_id,
			$series_id,
			get_the_title( $series_id ),
			get_the_excerpt( $series_id ),
			get_post_field( 'post_name', $series_id ),
			$series_pass_id,
			$series_pass_attendee_id,
		];
		$replace = [
			'SERIES_PASS_ATTENDEE_SECURITY',
			'PUBLIC_EVENT_1_ID',
			'PUBLIC_EVENT_1_START_DATE',
			'PUBLIC_EVENT_1_END_DATE',
			'PUBLIC_EVENT_2_ID',
			'PUBLIC_EVENT_3_ID',
			'PUBLIC_EVENT_4_ID',
			'PUBLIC_EVENT_5_ID',
			'PUBLIC_EVENT_6_ID',
			'ORDER_ID',
			'SERIES_ID',
			'SERIES_TITLE',
			'SERIES_EXCERPT',
			'SERIES_NAME',
			'SERIES_PASS_ID',
			'SERIES_PASS_ATTENDEE_ID',
		];

		if ( $include_images ) {
			$search[]  = $thumbnail_id;
			$search[]  = wp_get_attachment_url( $thumbnail_id );
			$search[]  = $header_image_id;
			$replace[] = 'THUMBNAIL_ID';
			$replace[] = 'THUMBNAIL_URL';
			$replace[] = 'HEADER_IMAGE_ID';
		}

		// Bundle the subject and message together to snapshot them.
		$this->assertMatchesHtmlSnapshot( str_replace( $search, $replace, $subject . "\n\r\n\r" . $message ) );
	}
}
