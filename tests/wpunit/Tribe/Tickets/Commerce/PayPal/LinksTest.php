<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe__Tickets__Commerce__PayPal__Links as Links;

class LinksTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;

	public static $driver;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$driver = new WPHtmlOutputDriver( home_url(), 'http://commerce.dev' );
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Links::class, $sut );
	}

	/**
	 * @return Links
	 */
	private function make_instance() {
		return new Links();
	}

	/**
	 * Test ipn_notification_history snapshot
	 */
	public function test_ipn_notification_history_snapshot() {
		$links = $this->make_instance();
		$this->assertEmpty( $links->ipn_notification_history( 'bar' ) );
		$this->assertMatchesSnapshot( $links->ipn_notification_history( 'link' ), self::$driver );
		$this->assertMatchesSnapshot( $links->ipn_notification_history( 'tag' ), self::$driver );
	}

	/**
	 * Test ipn_notification_settings snapshot
	 */
	public function test_ipn_notification_settings_snapshot() {
		$links = $this->make_instance();
		$this->assertEmpty( $links->ipn_notification_settings( 'bar' ) );
		$this->assertMatchesSnapshot( $links->ipn_notification_settings( 'link' ), self::$driver );
		$this->assertMatchesSnapshot( $links->ipn_notification_settings( 'tag' ), self::$driver );
	}

	public function order_link_inputs() {
		yield[ 'bar', 'foo-bar-some' ];
		yield[ 'link', 'foo-bar-some' ];
		yield[ 'tag', 'foo-bar-some' ];
		yield[ 'tag', 'foo-bar-some', 'See Order' ];
	}

	/**
	 * Test order_link snapshot
	 *
	 * @dataProvider order_link_inputs
	 */
	public function test_order_link_snapshot( $what, $order_id, $text = null ) {
		$links = $this->make_instance();
		$this->assertMatchesSnapshot( $links->order_link( $what, $order_id, $text ), self::$driver );
	}

}
