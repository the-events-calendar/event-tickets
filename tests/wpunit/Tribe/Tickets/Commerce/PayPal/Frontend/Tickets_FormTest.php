<?php

namespace Tribe\Tickets\Commerce\PayPal\Frontend;

use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form as Form;

class Tickets_FormTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;
	use Ticket_Maker;

	/**
	 * @var WPHtmlOutputDriver
	 */
	public $driver;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		$this->driver = new WPHtmlOutputDriver( home_url(), 'http://commerce.dev' );
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
		$sut = $this->make_instance();

		$this->assertInstanceOf( Form::class, $sut );
	}

	/**
	 * @return Form
	 */
	private function make_instance() {
		/** @var Form $instance */
		$instance = tribe( 'tickets.commerce.paypal.frontend.tickets-form' );
		$instance->has_rendered( false );

		return $instance;

	}

	/**
	 * Test render snapshot with no tickets
	 */
	public function test_render_snapshot_with_no_tickets() {
		global $post;
		$post = $this->factory->post->create_and_get();

		$form     = $this->make_instance();
		$rendered = $this->render_form( $form );

		$this->driver->setTolerableDifferences( [ $post->ID ] );
		$this->driver->setTolerableDifferencesPrefixes( [ 'quantity_' ] );
		$this->assertMatchesSnapshot( $rendered, $this->driver );
	}

	protected function render_form( Form $form ) {
		ob_start();
		$form->render();

		return ob_get_clean();
	}

	/**
	 * Test render snapshot with no available tickets
	 */
	public function test_render_snapshot_with_no_available_tickets() {
		$this->markTestSkipped( 'Snapshot testing seem very unstable, need more exploration before required' );

		global $post;
		$post = $this->factory->post->create_and_get();

		$tickets = [];
		for ( $i = 0; $i < 3; $i ++ ) {
			$tickets[] = $this->create_paypal_ticket_basic( $post->ID, 1, [
					'meta_input' => [
						'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-10 day' ) ),
						'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '-5 day' ) ),
					],
				]
			);
		}

		$form     = $this->make_instance();
		$rendered = $this->render_form( $form );

		$this->driver->setTolerableDifferences( array_merge( [ $post->ID ], $tickets ) );
		$this->driver->setTolerableDifferencesPrefixes( [ 'quantity_' ] );
		$this->assertMatchesSnapshot( $rendered, $this->driver );
	}

	/**
	 * Test render snapshot with available tickets
	 */
	public function test_render_snapshot_with_available_tickets() {
		$this->markTestSkipped( 'Snapshot testing seem very unstable, need more exploration before required' );

		global $post;
		$post = $this->factory->post->create_and_get();

		$tickets = [];
		for ( $i = 0; $i < 3; $i ++ ) {
			$tickets[] = $this->create_paypal_ticket_basic( $post->ID, 1, [
				'meta_input' => [
					'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-10 day' ) ),
					'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+10 day' ) ),
				],
			] );
		}

		$form     = $this->make_instance();
		$rendered = $this->render_form( $form );

		$this->driver->setTolerableDifferences( array_merge( [ $post->ID ], $tickets ) );
		$this->driver->setTolerableDifferencesPrefixes( [ 'quantity_' ] );
		$this->assertMatchesSnapshot( $rendered, $this->driver );
	}
}
