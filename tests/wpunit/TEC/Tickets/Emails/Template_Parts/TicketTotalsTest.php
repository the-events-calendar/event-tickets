<?php

namespace TEC\Tickets\Emails\Template_Parts;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Emails\Admin\Preview_Data;
use Tribe__Template as Template;
use WP_Post;

/**
 * Class TicketTotalsTest
 *
 * Simple tests for the email template parts related to fees and coupons.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails\Template_Parts
 */
class TicketTotalsTest extends WPTestCase {

	/**
	 * @var Template
	 */
	protected $template;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();

		// Set up the template engine.
		$this->template = new Template();
		$this->template->set_template_origin( tribe( 'tickets.main' ) );
		$this->template->set_template_folder( 'src/views' );
		$this->template->set_template_folder_lookup( true );
		$this->template->set_template_context_extract( true );
	}

		/**
	 * Creates a simple order with fees.
	 *
	 * @return WP_Post The order with fees.
	 */
	protected function create_order_with_fees(): WP_Post {
		$order = new WP_Post( (object) [
			'ID' => 123,
			'fees' => [
				[
					'display_name' => 'Processing Fee',
					'sub_total'    => 5.00,
					'quantity'     => 1,
				],
			],
			'total_value' => Currency_Value::create_from_float( 105.00 ),
		] );

		return $order;
	}

	/**
	 * Creates a simple order with coupons.
	 *
	 * @return WP_Post The order with coupons.
	 */
	protected function create_order_with_coupons(): WP_Post {
		$order = new WP_Post( (object) [
			'ID' => 123,
			'coupons' => [
				'SAVE10' => [
					'sub_total' => Value::create( '10.00' ),
				],
			],
			'total_value' => Currency_Value::create_from_float( 90.00 ),
		] );

		return $order;
	}

	/**
	 * Creates a simple order without fees.
	 *
	 * @return WP_Post The order without fees.
	 */
	protected function create_order_without_fees(): WP_Post {
		$order = new WP_Post( (object) [
			'ID' => 123,
			'fees' => [],
			'total_value' => Currency_Value::create_from_float( 100.00 ),
		] );

		return $order;
	}

	/**
	 * Creates a simple order without coupons.
	 *
	 * @return WP_Post The order without coupons.
	 */
	protected function create_order_without_coupons(): WP_Post {
		$order = new WP_Post( (object) [
			'ID' => 123,
			'coupons' => [],
			'total_value' => Currency_Value::create_from_float( 100.00 ),
		] );

		return $order;
	}

		/**
	 * Tests that fees appear in the template when fees exist.
	 *
	 * @test
	 */
	public function test_fees_appear_when_present(): void {
		$order = $this->create_order_with_fees();
		$html = $this->template->template(
			'emails/template-parts/body/order/ticket-totals/fees-row',
			[ 'order' => $order ],
			false
		);

		$this->assertStringContainsString( 'Fees:', $html );
		$this->assertStringContainsString( '$5.00', $html );
	}

	/**
	 * Tests that fees don't appear when no fees exist.
	 *
	 * @test
	 */
	public function test_fees_hidden_when_not_present(): void {
		$order = $this->create_order_without_fees();
		$html = $this->template->template(
			'emails/template-parts/body/order/ticket-totals/fees-row',
			[ 'order' => $order ],
			false
		);

		$this->assertEmpty( $html );
	}

	/**
	 * Tests that coupons appear in the template when coupons exist.
	 *
	 * @test
	 */
	public function test_coupons_appear_when_present(): void {
		$order = $this->create_order_with_coupons();
		$html = $this->template->template(
			'emails/template-parts/body/order/ticket-totals/coupons-row',
			[ 'order' => $order ],
			false
		);

		$this->assertStringContainsString( 'Discount:', $html );
	}

	/**
	 * Tests that coupons don't appear when no coupons exist.
	 *
	 * @test
	 */
	public function test_coupons_hidden_when_not_present(): void {
		$order = $this->create_order_without_coupons();
		$html = $this->template->template(
			'emails/template-parts/body/order/ticket-totals/coupons-row',
			[ 'order' => $order ],
			false
		);

		$this->assertEmpty( $html );
	}

	/**
	 * Tests that order total always appears.
	 *
	 * @test
	 */
	public function test_order_total_always_appears(): void {
		$order = $this->create_order_with_fees();
		$html = $this->template->template(
			'emails/template-parts/body/order/ticket-totals/total-row',
			[ 'order' => $order ],
			false
		);

		$this->assertStringContainsString( 'Order Total', $html );
		$this->assertStringContainsString( '$105.00', $html );
	}
}
