<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Generator;
use TEC\Common\StellarWP\Assets\Assets as Asset_Registry;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe__Timezones as Timezones;

class Assets_Test extends Controller_Test_Case {
	protected $controller_class = Assets::class;

	/**
	 * The `TEC_TICKETS_COMMERCE` environment variable before a test changed it: `false` when it was not set, `null`
	 * when the test left it alone.
	 *
	 * @var string|false|null
	 */
	private $tickets_commerce_env = null;

	/**
	 * @before
	 */
	public function log_in_as_administrator(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Setting the admin screen fires an onboarding redirect that hangs the test run.
		remove_all_actions( 'tec_admin_headers_about_to_be_sent' );
	}

	/**
	 * @after
	 */
	public function reset_screen(): void {
		wp_dequeue_script( Assets::CLASSIC_SCRIPT );
		wp_dequeue_script( Assets::BLOCK_EDITOR_SCRIPT );
		wp_dequeue_style( Assets::BLOCK_EDITOR_STYLE );
		set_current_screen( 'front' );

		if ( null !== $this->tickets_commerce_env ) {
			putenv( false === $this->tickets_commerce_env ? 'TEC_TICKETS_COMMERCE' : 'TEC_TICKETS_COMMERCE=' . $this->tickets_commerce_env );
			$this->tickets_commerce_env = null;
		}
	}

	/**
	 * @test
	 */
	public function should_enqueue_the_classic_script_on_the_event_edit_screen(): void {
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertTrue( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * The script bundles a copy of moment-timezone that would replace the one the block editor's dates rely on.
	 *
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_in_the_block_editor(): void {
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );
		get_current_screen()->is_block_editor( true );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_without_tickets_commerce(): void {
		// The environment variable wins over the setting and its filter.
		$this->tickets_commerce_env = getenv( 'TEC_TICKETS_COMMERCE' );
		putenv( 'TEC_TICKETS_COMMERCE=0' );
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_localize_the_zone_the_server_resolves_each_manual_offset_to(): void {
		$data = $this->get_localized_data();

		preg_match_all( '/value="(UTC[+-][^"]*)"/', wp_timezone_choice( '' ), $matches );
		$this->assertNotEmpty( $matches[1] );
		$this->assertSame( $matches[1], array_keys( $data['timezones'] ) );
		foreach ( $data['timezones'] as $offset => $zone ) {
			$this->assertSame( Timezones::build_timezone_object( $offset )->getName(), $zone, $offset );
		}
	}

	/**
	 * @test
	 */
	public function should_localize_the_all_day_times_of_the_default_cutoff(): void {
		$data = $this->get_localized_data();

		$this->assertSame(
			[
				'start'   => '00:00:00',
				'end'     => '23:59:59',
				'endDays' => 0,
			],
			$data['allDay']
		);
	}

	/**
	 * @test
	 */
	public function should_localize_the_all_day_times_of_a_cutoff_that_ends_the_day_on_the_next_one(): void {
		tribe_update_option( 'multiDayCutoff', '06:00' );

		$data = $this->get_localized_data();

		$this->assertSame(
			[
				'start'   => '06:00:00',
				'end'     => '05:59:59',
				'endDays' => 1,
			],
			$data['allDay']
		);
	}

	/**
	 * @test
	 */
	public function should_localize_the_time_format_and_the_helper_texts(): void {
		update_option( 'time_format', 'H:i' );
		// `tribe_get_time_format()` keeps the first option value it reads for the rest of the request.
		tribe_unset_var( 'tribe_get_time_format' );

		$data = $this->get_localized_data();

		$this->assertSame( 'H:i', $data['timeFormat'] );
		$this->assertSame( 'Sales start %1$s at %2$s', $data['text']['start'] );
		$this->assertSame( 'Sales end %1$s at %2$s', $data['text']['end'] );
		$this->assertSame( 'Ticket sales cannot end before they start. Please adjust the sales window.', $data['text']['invalidWindow'] );
		$this->assertSame( sprintf( 'Enter a number from %d to %d.', Boundary::MIN_VALUE, Boundary::MAX_VALUE ), $data['text']['relativeValueOutOfRange'] );
	}

	/**
	 * @test
	 */
	public function should_localize_the_time_format_through_the_tec_time_format_filter(): void {
		add_filter( 'tribe_time_format', static fn(): string => 'G\\hi' );

		$this->assertSame( 'G\\hi', $this->get_localized_data()['timeFormat'] );
	}

	/**
	 * @return Generator<string,array{0: string, 1: string, 2: string, 3: string}>
	 */
	public function tec_date_formats_provider(): Generator {
		yield 'set' => [ 'j/n/Y', 'j/n', 'j/n/Y', 'j/n' ];
		// The block editor falls back to the same formats, so both editors show the same text.
		yield 'blank' => [ '', '', 'F j, Y', 'F j' ];
	}

	/**
	 * @test
	 * @dataProvider tec_date_formats_provider
	 */
	public function should_localize_the_tec_date_formats( string $with_year, string $no_year, string $expected_with_year, string $expected_no_year ): void {
		tribe_update_option( 'dateWithYearFormat', $with_year );
		tribe_update_option( 'dateWithoutYearFormat', $no_year );

		$data = $this->get_localized_data();

		$this->assertSame( $expected_with_year, $data['dateWithYear'] );
		$this->assertSame( $expected_no_year, $data['dateNoYear'] );
	}

	/**
	 * @test
	 */
	public function should_localize_the_date_format_of_the_tickets_list(): void {
		tribe_update_option( 'dateWithYearFormat', 'd/m/Y' );

		$data = $this->get_localized_data();

		$this->assertSame( tribe_get_date_format( true ), $data['listDateFormat'] );
		$this->assertSame( 'd/m/Y', $data['listDateFormat'] );
	}

	/**
	 * @test
	 */
	public function should_load_the_translations_of_the_classic_script(): void {
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertSame( 'event-tickets', wp_scripts()->registered[ Assets::CLASSIC_SCRIPT ]->textdomain ?? null );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_on_the_page_edit_screen(): void {
		$this->make_controller()->register();
		set_current_screen( 'page' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_on_the_events_list(): void {
		$this->make_controller()->register();
		set_current_screen( 'edit-tribe_events' );

		do_action( 'admin_enqueue_scripts', 'edit.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_enqueue_the_block_editor_script_on_the_event_edit_screen(): void {
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );
		get_current_screen()->is_block_editor( true );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertTrue( wp_script_is( Assets::BLOCK_EDITOR_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_block_editor_script_without_tickets_commerce(): void {
		// The environment variable wins over the setting and its filter.
		$this->tickets_commerce_env = getenv( 'TEC_TICKETS_COMMERCE' );
		putenv( 'TEC_TICKETS_COMMERCE=0' );
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );
		get_current_screen()->is_block_editor( true );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertFalse( wp_script_is( Assets::BLOCK_EDITOR_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_block_editor_script_on_the_page_edit_screen(): void {
		$this->make_controller()->register();
		set_current_screen( 'page' );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertFalse( wp_script_is( Assets::BLOCK_EDITOR_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_load_the_translations_of_the_block_editor_script(): void {
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );
		get_current_screen()->is_block_editor( true );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertSame( 'event-tickets', wp_scripts()->registered[ Assets::BLOCK_EDITOR_SCRIPT ]->textdomain ?? null );
	}

	/**
	 * @test
	 */
	public function should_enqueue_the_block_editor_style_on_the_event_edit_screen(): void {
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );
		get_current_screen()->is_block_editor( true );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertTrue( wp_style_is( Assets::BLOCK_EDITOR_STYLE, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_block_editor_style_on_the_page_edit_screen(): void {
		$this->make_controller()->register();
		set_current_screen( 'page' );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertFalse( wp_style_is( Assets::BLOCK_EDITOR_STYLE, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_block_editor_script_in_the_classic_editor(): void {
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );
		get_current_screen()->is_block_editor( false );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertFalse( wp_script_is( Assets::BLOCK_EDITOR_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_localize_the_relative_values_the_block_editor_offers_by_default(): void {
		$data = $this->get_block_editor_localized_data();

		$this->assertSame(
			[
				'start' => Editor::DEFAULT_RELATIVE_START,
				'end'   => Editor::DEFAULT_RELATIVE_END,
			],
			$data['defaults']
		);
	}

	/**
	 * @test
	 */
	public function should_localize_the_event_date_settings_of_the_classic_script_to_the_block_editor(): void {
		$this->make_controller()->register();

		$classic = $this->read_localized_data( Assets::CLASSIC_SCRIPT, 'tec.tickets.relativeSaleDates.classicData' );
		$block   = $this->read_localized_data( Assets::BLOCK_EDITOR_SCRIPT, 'tec.tickets.relativeSaleDates.blockEditorData' );

		$this->assertNotEmpty( $classic['timezones'] );
		$this->assertSame( $classic['timezones'], $block['timezones'] );
		$this->assertSame( $classic['allDay'], $block['allDay'] );
	}

	/**
	 * @test
	 */
	public function should_localize_the_helper_text_formats_to_the_block_editor(): void {
		tribe_update_option( 'dateWithYearFormat', 'd/m/Y' );
		tribe_update_option( 'dateWithoutYearFormat', 'd/m' );
		update_option( 'time_format', 'H:i' );

		$data = $this->get_block_editor_localized_data();

		$this->assertSame(
			[
				'dateWithYear' => tribe_get_date_format( true ),
				'dateNoYear'   => tribe_get_date_format( false ),
				'time'         => 'H:i',
			],
			$data['formats']
		);
		$this->assertSame( 'd/m/Y', $data['formats']['dateWithYear'] );
		$this->assertSame( 'd/m', $data['formats']['dateNoYear'] );
	}

	/**
	 * @test
	 */
	public function should_load_the_block_editor_script_after_the_core_date_script(): void {
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertContains( 'wp-date', wp_scripts()->registered[ Assets::BLOCK_EDITOR_SCRIPT ]->deps );
	}

	/**
	 * Builds the data the classic script is localized with, as the browser receives it.
	 *
	 * The data is read from the registered asset: the library prints each localized object once per request, so
	 * printing the script would only show it to the first test.
	 *
	 * @return array{timeFormat: string, dateWithYear: string, dateNoYear: string, listDateFormat: string, timezones: array<string,string>, allDay: array{start: string, end: string, endDays: int}, text: array{start: string, end: string, invalidWindow: string, relativeValueOutOfRange: string}} The localized data.
	 */
	private function get_localized_data(): array {
		$this->make_controller()->register();

		$asset    = Asset_Registry::init()->get( Assets::CLASSIC_SCRIPT );
		$localized = array_column( $asset->get_custom_localize_scripts(), 1, 0 );
		$localize  = $localized['tec.tickets.relativeSaleDates.classicData'] ?? null;
		$this->assertIsCallable( $localize );

		return json_decode( wp_json_encode( $localize( $asset ) ), true );
	}

	/**
	 * Builds the data the Ticket block script is localized with, as the browser receives it.
	 *
	 * @return array{defaults: array{start: array{mode: string, value: int, unit: int, anchor: string}, end: array{mode: string, value: int, unit: int, anchor: string}}, timezones: array<string,string>, allDay: array{start: string, end: string, endDays: int}, formats: array{dateWithYear: string, dateNoYear: string, time: string}} The localized data.
	 */
	private function get_block_editor_localized_data(): array {
		$this->make_controller()->register();

		return $this->read_localized_data( Assets::BLOCK_EDITOR_SCRIPT, 'tec.tickets.relativeSaleDates.blockEditorData' );
	}

	/**
	 * Reads the data a registered script is localized with, as the browser receives it.
	 *
	 * @param string $handle      The script handle.
	 * @param string $object_name The name of the localized object.
	 *
	 * @return array{timeFormat: string, listDateFormat: string, timezones: array<string,string>, allDay: array{start: string, end: string, endDays: int}, text: array{start: string, end: string, invalidWindow: string}}|array{defaults: array{start: array{mode: string, value: int, unit: int, anchor: string}, end: array{mode: string, value: int, unit: int, anchor: string}}, timezones: array<string,string>, allDay: array{start: string, end: string, endDays: int}, formats: array{dateWithYear: string, dateNoYear: string, time: string}} The localized data of the classic or the Ticket block script.
	 */
	private function read_localized_data( string $handle, string $object_name ): array {
		$asset     = Asset_Registry::init()->get( $handle );
		$localized = array_column( $asset->get_custom_localize_scripts(), 1, 0 );
		$localize  = $localized[ $object_name ] ?? null;
		$this->assertIsCallable( $localize );

		return json_decode( wp_json_encode( $localize( $asset ) ), true );
	}
}
