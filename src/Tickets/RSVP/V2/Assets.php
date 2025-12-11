<?php
/**
 * Handles registering and setup for assets on RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Service_Provider;
use Tribe__Tickets__Main;

/**
 * Class Assets.
 *
 * Registers CSS and JavaScript assets for RSVP V2 admin and frontend.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Assets extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_admin_assets();
		$this->register_frontend_assets();
	}

	/**
	 * Registers admin assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_admin_assets(): void {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		// Admin CSS for RSVP V2 metabox panel.
		tec_asset(
			$tickets_main,
			'tribe-tickets-rsvp-v2-admin-style',
			'rsvp/v2/admin-panel.css',
			[
				'tribe-common-admin',
			],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'should_enqueue_admin_assets' ],
			]
		);

		// Admin JS for RSVP V2 metabox panel.
		tec_asset(
			$tickets_main,
			'tribe-tickets-rsvp-v2-admin-js',
			'rsvp/v2/admin-tickets.js',
			[
				'jquery',
				'tribe-common',
			],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'should_enqueue_admin_assets' ],
				'localize'     => [
					'name' => 'TribeRsvpV2Admin',
					'data' => [ $this, 'get_admin_localize_data' ],
				],
			]
		);
	}

	/**
	 * Registers frontend assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_frontend_assets(): void {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		// Frontend CSS for RSVP V2 block.
		tec_asset(
			$tickets_main,
			'tribe-tickets-rsvp-v2-style',
			'rsvp/v2/rsvp-v2.css',
			[
				'tribe-common-skeleton-style',
				'tribe-common-full-style',
			],
			null,
			[
				'groups' => [
					'tribe-tickets-rsvp-v2',
				],
				'print'  => true,
			]
		);

		// Frontend JS for RSVP V2 block.
		tec_asset(
			$tickets_main,
			'tribe-tickets-rsvp-v2-block-js',
			'rsvp/v2/rsvp-v2-block.js',
			[
				'jquery',
				'tribe-common',
				'wp-hooks',
			],
			null,
			[
				'groups'   => [
					'tribe-tickets-rsvp-v2',
				],
				'localize' => [
					'name' => 'TribeRsvpV2Block',
					'data' => [ $this, 'get_frontend_localize_data' ],
				],
			]
		);

		// Frontend JS for RSVP V2 manager.
		tec_asset(
			$tickets_main,
			'tribe-tickets-rsvp-v2-manager-js',
			'rsvp/v2/rsvp-v2-manager.js',
			[
				'jquery',
				'tribe-common',
				'tribe-tickets-rsvp-v2-block-js',
			],
			null,
			[
				'groups' => [
					'tribe-tickets-rsvp-v2',
				],
			]
		);
	}

	/**
	 * Checks if admin assets should be enqueued.
	 *
	 * @since TBD
	 *
	 * @return bool Whether to enqueue admin assets.
	 */
	public function should_enqueue_admin_assets(): bool {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		// Check if we're on a post edit screen for a ticketable post type.
		if ( 'post' !== $screen->base ) {
			return false;
		}

		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		return in_array( $screen->post_type, $ticketable_post_types, true );
	}

	/**
	 * Gets the localized data for admin scripts.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The localized data.
	 */
	public function get_admin_localize_data(): array {
		return [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonces'  => [
				'save' => wp_create_nonce( Metabox::NONCE_ACTION ),
			],
			'i18n'    => [
				'confirmDelete' => __( 'Are you sure you want to delete this RSVP?', 'event-tickets' ),
				'saving'        => __( 'Saving...', 'event-tickets' ),
				'saved'         => __( 'Saved', 'event-tickets' ),
				'error'         => __( 'An error occurred', 'event-tickets' ),
				'unlimited'     => __( 'Unlimited', 'event-tickets' ),
				'capacityLabel' => __( 'Capacity', 'event-tickets' ),
				'nameRequired'  => __( 'RSVP name is required', 'event-tickets' ),
			],
		];
	}

	/**
	 * Gets the localized data for frontend scripts.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The localized data.
	 */
	public function get_frontend_localize_data(): array {
		return [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'restUrl' => rest_url( 'tribe/tickets/v1/' ),
			'nonces'  => [
				'rsvpHandle' => wp_create_nonce( 'tribe_tickets_rsvp_v2' ),
			],
			'i18n'    => [
				'going'    => __( 'Going', 'event-tickets' ),
				'notGoing' => __( 'Not Going', 'event-tickets' ),
				'submit'   => __( 'Submit', 'event-tickets' ),
				'cancel'   => __( 'Cancel', 'event-tickets' ),
				'loading'  => __( 'Loading...', 'event-tickets' ),
				'error'    => __( 'An error occurred. Please try again.', 'event-tickets' ),
				'success'  => __( 'Your RSVP has been submitted!', 'event-tickets' ),
				'full'     => __( 'This RSVP is full.', 'event-tickets' ),
				'closed'   => __( 'RSVP is closed.', 'event-tickets' ),
			],
		];
	}

	/**
	 * Unregisters the provider.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		// Assets are automatically unregistered when the provider is unregistered.
	}
}
