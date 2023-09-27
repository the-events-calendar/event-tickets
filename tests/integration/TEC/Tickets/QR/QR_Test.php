<?php

namespace TEC\Tickets\QR;

use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

/**
 * Class QR_Test.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\QR
 */
class QR_Test extends \Codeception\TestCase\WPTestCase {
	use MatchesSnapshots;

	protected $driver;

	public function setUp(): void {
		parent::setUp();

		// Setup a new HTML output driver to make sure our stuff is tolerable.
		$this->driver = new WPHtmlOutputDriver( home_url(), 'http://views.dev' );
	}

	/**
	 * @test
	 */
	public function should_create_instance_of_module(): void {
		$qr_code = tribe( QR::class );
		$this->assertInstanceOf( QR::class, $qr_code );
	}

	/**
	 * @test
	 */
	public function should_not_create_instance_of_module_and_return_WP_Error(): void {
		add_filter( 'tec_tickets_qr_code_can_use',  '__return_false' );

		$qr_code = tribe( QR::class );
		$this->assertNotInstanceOf( QR::class, $qr_code );
		$this->assertInstanceOf( \WP_Error::class, $qr_code );

		remove_filter( 'tec_tickets_qr_code_can_use',  '__return_false' );
	}

	/**
	 * @test
	 */
	public function should_create_new_instance_everytime(): void {
		$qr_code_one = tribe( QR::class );
		$qr_code_two = tribe( QR::class );
		$this->assertEquals( $qr_code_one, $qr_code_two );
	}

	public function qr_code_data_provider() {
		yield 'should_allow_basic_url' => [
			'https://theeventscalendar.com',
			'basic_url',
			'',
		];
		yield 'should_allow_url_with_query_args' => [
			'https://theeventscalendar.com?foo=bar',
			'url_with_query_args',
			'',
		];
		yield 'should_allow_random_text' => [
			'foo bar baz',
			'random_text',
			'',
		];
		yield 'should_allow_json_encoded' => [
			json_encode( [ 'development' => 12345, 'devel' => true, 'dev' => gmdate( 'Y-m-d', '2023-10-20' ) ] ),
			'json_encoded',
			'',
		];
		yield 'should_allow_folder_usage' => [
			'data_for_folder_test',
			'folder_usage',
			'test-folder',
		];
	}

	/**
	 * @test
	 * @dataProvider qr_code_data_provider
	 */
	public function should_create_png_as_base64( $data  ): void {
		$qr_code = tribe( QR::class );
		$this->assertMatchesSnapshot( $qr_code->get_png_as_base64( $data ), $this->driver );
	}

	/**
	 * @test
	 * @dataProvider qr_code_data_provider
	 */
	public function should_create_png_as_string( $data ): void {
		$qr_code = tribe( QR::class );
		$this->assertMatchesSnapshot( $qr_code->get_png_as_string( $data ), $this->driver );
	}

	/**
	 * @test
	 * @dataProvider qr_code_data_provider
	 */
	public function should_create_png_as_file( $data, $slug, $folder ): void {
		$qr_code = tribe( QR::class );
		$upload = $qr_code->get_png_as_file( $data, $slug, $folder );

		$this->assertMatchesSnapshot( json_encode( $upload ), $this->driver );
		$this->assertFileExists( $upload['file'] );
		$this->assertEmpty( $upload['error'] );

		if ( ! empty( $folder ) ) {
			$this->assertContains( $folder, $upload['file'] );
		}

		unlink( $upload['file'] );
	}


}