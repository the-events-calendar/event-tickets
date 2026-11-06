<?php

namespace TEC\Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Admin\Help_Hub\Section_Builder\Link_Section_Builder;
use TEC\Tickets\Admin\Help_Hub\ET_Hub_Resource_Data;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use TEC\Tickets\Commerce\Gateways\Stripe\Settings as Stripe_Settings;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Main as Tickets_Main;

class Help_Links_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @test
	 */
	public function it_should_render_the_settings_sidebar_links(): void {
		$sidebar = include Tickets_Main::instance()->plugin_path . 'src/admin-views/settings/sidebars/default-sidebar.php';

		ob_start();
		$sidebar->render();
		$html = ob_get_clean();

		$expected = [
			'Getting Started with Tickets' => 'https://evnt.is/1aot',
			'Ticket Settings'              => 'https://evnt.is/ticket-settings',
			'Using Tickets Commerce'       => 'https://evnt.is/1axs',
			'Creating Tickets'             => 'https://evnt.is/1ap2',
			'Using RSVPs'                  => 'https://evnt.is/1aox',
			'Managing Orders'              => 'https://evnt.is/1aoy',
			'Managing Attendees'           => 'https://evnt.is/manage-attendees',
			'Tickets &amp; WooCommerce'    => 'https://evnt.is/1ap1',
		];

		$this->assertEquals( $expected, $this->get_links( $html ) );
		$this->assertStringNotContainsString( 'Beginner Resources', $html );
		$this->assertStringNotContainsString( 'Advanced Plus Features', $html );
	}

	/**
	 * @test
	 */
	public function it_should_list_the_help_hub_ticket_and_attendee_links(): void {
		( new ET_Hub_Resource_Data() )->create_resource_sections();

		$expected = [
			'tickets_rsvps'       => [
				'Using RSVPs'                 => 'https://evnt.is/1aox',
				'Setting up Tickets Commerce' => 'https://evnt.is/1axs',
				'Creating Tickets'            => 'https://evnt.is/1ap2',
			],
			'attendee_management' => [
				'Using Attendee Registration'           => 'https://evnt.is/1ap11',
				'Managing Attendees'                    => 'https://evnt.is/manage-attendees',
				'Managing Orders'                       => 'https://evnt.is/1aoy',
				'Refunding and Canceling Ticket Orders' => 'https://evnt.is/1ars',
			],
		];

		foreach ( $expected as $slug => $links ) {
			$section = Link_Section_Builder::get_section( $slug );
			$this->assertEquals( $links, array_column( $section['links'], 'url', 'title' ), $slug );
		}
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public function gateway_provider(): array {
		return [
			'stripe' => [ 'stripe' ],
			'paypal' => [ 'paypal' ],
			'square' => [ 'square' ],
		];
	}

	/**
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_not_render_a_troubleshooting_link_for_the_gateway( string $gateway ): void {
		$html = tribe( 'tickets.admin.views' )->template(
			"settings/tickets-commerce/{$gateway}/connect/help-links",
			[ 'merchant_status' => [ 'connected' => true ] ],
			false
		);

		$this->assertStringContainsString( 'tec-tickets__admin-settings-tickets-commerce-gateway-help-link', $html );
		$this->assertStringNotContainsString( 'Get troubleshooting help', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_link_the_stripe_checkout_type_tooltip(): void {
		$this->set_class_fn_return( Merchant::class, 'is_connected', true );

		$settings = tribe( Stripe_Settings::class )->get_settings();
		$tooltip  = $settings[ Stripe_Settings::$option_checkout_element ]['tooltip'];

		$this->assertStringStartsWith( 'Additional payment methods are available', $tooltip );
		$this->assertStringNotContainsString( '<a ', $tooltip );
	}

	/**
	 * @test
	 */
	public function it_should_not_link_the_stripe_webhooks_description(): void {
		$html = tribe( 'tickets.admin.views' )->template( 'settings/tickets-commerce/stripe/webhook-description', [], false );

		$this->assertStringContainsString( 'Setting up webhooks will enable you', $html );
		$this->assertStringNotContainsString( 'Learn more', $html );
	}

	/**
	 * @test
	 */
	public function it_should_link_the_payments_help_to_the_tickets_commerce_article(): void {
		$html = implode( '', array_column( tribe( Commerce_Settings::class )->get_settings(), 'html' ) );

		$links = $this->get_links( $html );

		$this->assertEquals( 'https://evnt.is/1axs', $links['Learn more about configuring payment options with Tickets Commerce'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_link_the_paypal_connection_unavailable_notices(): void {
		$this->set_fn_return( 'is_ssl', false );
		$non_ssl = tribe( 'tickets.admin.views' )->template( 'settings/tickets-commerce/paypal/connect/non-ssl-notice', [], false );
		$signup  = tribe( 'tickets.admin.views' )->template(
			'settings/tickets-commerce/paypal/signup-link',
			[
				'url'          => false,
				'country_code' => '',
			],
			false
		);

		foreach ( [ $non_ssl, $signup ] as $html ) {
			$this->assertStringContainsString( 'PayPal connection unavailable', $html );
			$this->assertStringNotContainsString( 'Learn more', $html );
		}
	}

	/**
	 * @test
	 */
	public function it_should_link_the_paypal_configuring_help_to_the_paypal_article(): void {
		$html = tribe( 'tickets.admin.views' )->template( 'settings/tickets-commerce/paypal/connect/help-links', [], false );

		$links = $this->get_links( $html );

		$this->assertEquals( 'https://evnt.is/tickets-paypal', $links['Learn more about configuring PayPal payments'] );
	}

	/**
	 * @test
	 */
	public function it_should_link_the_welcome_screen_to_the_tickets_commerce_article(): void {
		ob_start();
		include Tickets_Main::instance()->plugin_path . 'src/admin-views/admin-welcome-message.php';
		$html = ob_get_clean();

		$links = $this->get_links( $html );

		$this->assertEquals( 'https://evnt.is/1axs', $links['Set Up Tickets Commerce'] );
	}

	/**
	 * @param string $html The rendered HTML.
	 *
	 * @return array<string,string> The link labels mapped to their URLs, in page order.
	 */
	private function get_links( string $html ): array {
		preg_match_all( '#<a[^>]+href="([^"]+)"[^>]*>\s*([^<]+?)\s*</a>#', $html, $matches );

		return array_combine( $matches[2], $matches[1] );
	}
}
