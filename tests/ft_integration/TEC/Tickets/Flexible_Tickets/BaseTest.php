<?php

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;

class BaseTest extends Controller_Test_Case {
	protected string $controller_class = Base::class;

	/**
	 * It should add Series to ticketable post types
	 *
	 * @test
	 */
	public function should_add_series_to_ticketable_post_types(): void {
		$this->assertNotContains(
			Series::POSTTYPE
			, apply_filters( 'tribe_tickets_settings_post_types', [] ),
			'Series should not appear ticketable post types if FT is not active.'
		);

		$this->make_controller()->register();

		$this->assertNotContains(
			Series::POSTTYPE
			, apply_filters( 'tribe_tickets_settings_post_types', [] ),
			'The controller should add Series to the list of ticketable post typesj.'
		);
	}

	/**
	 * It should make series ticketable on first activation by default
	 *
	 * @test
	 */
	public function should_make_series_ticketable_on_first_activation_by_default(): void {
		// Reset the state to a first activation.
		tribe_update_option( 'ticket-enabled-post-types', [] );
		tribe_update_option( 'flexible_tickets_activated', false );

		$this->assertNotContains(
			Series::POSTTYPE,
			tribe_get_option( 'ticket-enabled-post-types', [] ),
			'Series should not be ticketable by default.'
		);

		$this->make_controller()->register();

		$this->assertContains(
			Series::POSTTYPE,
			tribe_get_option( 'ticket-enabled-post-types', [] ),
			'Series should be ticketable by default on first activation.'
		);
		$this->assertTrue(
			tribe_get_option( 'flexible_tickets_activated', false ),
			'Flexible Tickets activation flag should be set.'
		);
	}

	/**
	 * It should not reset Series ticketable state after first activation.
	 *
	 * @test
	 */
	public function should_not_reset_series_ticketable_state_after_first_activation_(): void {
		// Set a state where Flexible Tickets was activated and the Series were unchecked.
		tribe_update_option( 'ticket-enabled-post-types', [ 'post' ] );
		tribe_update_option( 'flexible_tickets_activated', true );

		$this->make_controller()->register();

		$this->assertNotContains(
			Series::POSTTYPE,
			tribe_get_option( 'ticket-enabled-post-types', [] ),
			'Series ticketable state should not be reset when Flexible Tickets is activated again.'
		);
	}
}
