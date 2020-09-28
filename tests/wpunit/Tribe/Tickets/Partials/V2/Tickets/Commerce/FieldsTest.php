<?php

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class FieldsTest extends V2TestCase {
	protected $partial_path = 'v2/tickets/commerce/fields';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {

		/**
		 * @var \Tribe__Tickets__Commerce__PayPal__Main
		 */
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		return [
			'provider'                    => $provider,
			'provider_id'                 => $provider->class_name,
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_input_field_with_provider_class_name() {
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, $this->get_default_args(), false );

		$args   = $this->get_default_args();
		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [ $args['provider_id'] ] );

		$this->assertContains( '<input name="provider"', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}