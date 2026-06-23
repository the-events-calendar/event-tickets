<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Template;
use Tribe__Tickets__Main;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Tests for TC-RSVP commerce view templates touched by SOFT-3334.
 */
class Commerce_Templates_Test extends WPTestCase {
	use Ticket_Maker;

	private function get_template(): Tribe__Template {
		$template = new Tribe__Template();
		$template->set_template_origin( Tribe__Tickets__Main::instance() );
		$template->set_template_folder( 'src/views' );
		$template->set_template_context_extract( true );
		$template->set_template_folder_lookup( true );

		return $template;
	}

	private function create_ticket_object( int $post_id, array $overrides = [] ): Ticket_Object {
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, $overrides );
		$ticket    = tribe( Module::class )->get_ticket( $post_id, $ticket_id );
		$this->assertInstanceOf( Ticket_Object::class, $ticket );

		return $ticket;
	}

	public function test_rsvp_wrapper_outputs_data_iac_attribute(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket  = $this->create_ticket_object( $post_id );
		$ticket->iac = 'required';

		$template = $this->get_template();
		$template->add_template_globals(
			[
				'post_id'    => $post_id,
				'step'       => '',
				'must_login' => false,
				'threshold'  => 0,
			]
		);

		$html = $template->template(
			'v2/commerce/rsvp',
			[
				'rsvp'          => $ticket,
				'post_id'       => $post_id,
				'active_rsvps'  => [ $ticket ],
				'block_html_id' => 'rsvp-block-test',
				'step'          => '',
				'must_login'    => false,
			],
			false
		);

		$this->assertStringContainsString(
			sprintf( 'data-iac="%s"', esc_attr( $ticket->iac ) ),
			$html
		);
	}

	public function test_details_title_uses_rsvp_label_singular(): void {
		$post_id = static::factory()->post->create();
		$ticket  = $this->create_ticket_object( $post_id, [ 'ticket_name' => 'Custom Ticket Name' ] );

		$html = $this->get_template()->template(
			'v2/commerce/rsvp/details/title',
			[ 'rsvp' => $ticket ],
			false
		);

		$expected_label = tribe_get_rsvp_label_singular( 'rsvp_block_details_title' );

		$this->assertStringContainsString( esc_html( $expected_label ), $html );
		$this->assertStringNotContainsString( 'Custom Ticket Name', $html );
	}

	public function test_success_toggle_does_not_render_when_not_going(): void {
		$post_id = static::factory()->post->create();
		$ticket  = $this->create_ticket_object( $post_id );

		$html = $this->get_template()->template(
			'v2/commerce/rsvp/actions/success/toggle',
			[
				'rsvp'                 => $ticket,
				'opt_in_toggle_hidden' => false,
				'opt_in_checked'       => false,
				'opt_in_attendee_ids'  => '',
				'opt_in_nonce'         => '',
				'is_going'             => false,
			],
			false
		);

		$this->assertSame( '', $html );
	}

	public function test_success_toggle_renders_when_going(): void {
		$post_id = static::factory()->post->create();
		$ticket  = $this->create_ticket_object( $post_id );

		$html = $this->get_template()->template(
			'v2/commerce/rsvp/actions/success/toggle',
			[
				'rsvp'                 => $ticket,
				'opt_in_toggle_hidden' => false,
				'opt_in_checked'       => false,
				'opt_in_attendee_ids'  => '',
				'opt_in_nonce'         => '',
				'is_going'             => true,
			],
			false
		);

		$this->assertStringContainsString( 'tribe-tickets__rsvp-actions-success-going-toggle', $html );
	}

	public function ari_fields_template_provider(): Generator {
		yield 'iac active without meta still renders form shell' => [
			function (): Ticket_Object {
				$post_id = static::factory()->post->create();
				$ticket  = tribe( Module::class )->get_ticket(
					$post_id,
					$this->create_tc_rsvp_ticket( $post_id )
				);
				$ticket->iac = 'required';

				return $ticket;
			},
			true,
		];

		yield 'no iac and no meta renders nothing' => [
			function (): Ticket_Object {
				$post_id = static::factory()->post->create();

				return $this->create_ticket_object( $post_id );
			},
			false,
		];
	}

	/**
	 * @dataProvider ari_fields_template_provider
	 */
	public function test_ari_fields_template_respects_iac( Closure $ticket_factory, bool $should_render ): void {
		$ticket = $ticket_factory();

		$html = $this->get_template()->template(
			'v2/commerce/rsvp/ari/form/template/fields',
			[
				'rsvp'    => $ticket,
				'post_id' => static::factory()->post->create(),
			],
			false
		);

		if ( $should_render ) {
			$this->assertStringContainsString( 'tribe-tickets__form', $html );
		} else {
			$this->assertSame( '', trim( $html ) );
		}
	}
}
