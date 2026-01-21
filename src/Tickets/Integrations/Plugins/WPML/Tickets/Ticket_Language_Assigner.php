<?php
/**
 * Ensure ticket posts get WPML language details on creation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Tickets
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Tickets;

use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;

/**
 * Class Ticket_Language_Assigner
 *
 * Assigns WPML language details to tickets when they are created.
 *
 * @since TBD
 */
class Ticket_Language_Assigner {

	/**
	 * @since TBD
	 *
	 * @var Wpml_Adapter
	 */
	private Wpml_Adapter $wpml;

	/**
	 * @since TBD
	 *
	 * @param Wpml_Adapter $wpml WPML adapter instance.
	 */
	public function __construct( Wpml_Adapter $wpml ) {
		$this->wpml = $wpml;
	}

	/**
	 * Register hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'tribe_tickets_ticket_added', [ $this, 'handle' ], 10, 2 );
	}

	/**
	 * Handle ticket creation.
	 *
	 * @since TBD
	 *
	 * @param int $post_id Event ID.
	 * @param int $ticket_id Ticket ID.
	 *
	 * @return void
	 */
	public function handle( $post_id, $ticket_id ): void {
		$ticket_id = is_numeric( $ticket_id ) ? (int) $ticket_id : 0;

		if ( $ticket_id <= 0 ) {
			return;
		}

		$post_type = (string) get_post_type( $ticket_id );
		if ( '' === $post_type ) {
			return;
		}

		$current_language = (string) apply_filters( 'wpml_current_language', null );
		if ( '' === $current_language ) {
			return;
		}

		$this->wpml->set_language_details( $ticket_id, $post_type, $current_language );
	}
}
