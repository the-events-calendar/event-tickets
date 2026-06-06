<?php
/**
 * Classic Editor delegate for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Event;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Classic_Editor
 *
 * Handles Classic Editor integration for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Classic_Editor {
	/**
	 * Filters the enabled form toggles that would render in the default Tickets metabox to
	 * remove the RSVP one.
	 *
	 * @since TBD
	 *
	 * @param array<string,bool> $enabled A map from ticket types to their enabled status.
	 *
	 * @return array<string,bool> The filtered map of ticket types to their enabled status.
	 */
	public function do_not_render_rsvp_form_toggle( array $enabled ): array {
		$enabled['rsvp'] = false;

		return $enabled;
	}

	/**
	 * Filters the list table data to remove the RSVP tickets from the list.
	 *
	 * @since TBD
	 *
	 * @param array<string,array<Ticket_Object>> $ticket_types The ticket types and their tickets.
	 *
	 * @return array<string,array<Ticket_Object>> The filtered ticket types and their tickets.
	 */
	public function do_not_show_rsvp_in_tickets_metabox( array $ticket_types ): array {
		$ticket_types['rsvp'] = [];

		return $ticket_types;
	}

	/**
	 * Saves TC-RSVP ticket data when the parent post is saved in the Classic Editor.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID being saved.he post object being saved.
	 *
	 * @return void
	 */
	public function save_rsvp_on_post_save( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$post_data = wp_unslash( $_POST );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$this->process_rsvp_post_save( $post_id, $post_data );
	}


	/**
	 * Processes TC-RSVP ticket data from metabox POST values.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id   The post ID being saved.
	 * @param array<string,mixed> $post_data The metabox POST data.
	 *
	 * @return void
	 */
	public function process_rsvp_post_save( int $post_id, array $post_data ): void {
		if ( empty( $post_data['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $post_data['ticket_type'] ) {
			return;
		}

		if ( ! isset( $post_data['tec_tickets_rsvp_enable'] ) ) {
			return;
		}

		$data = $this->map_post_to_ticket_data( $post_data );

		/**
		 * Filters the ticket data before saving TC-RSVP from the Classic Editor post save.
		 *
		 * @since TBD
		 *
		 * @param array $data    The mapped ticket data for ticket_add().
		 * @param int   $post_id The parent post ID.
		 * @param array $post_data The raw POST data from the metabox.
		 */
		$data = apply_filters( 'tec_tickets_rsvp_v2_classic_save_data', $data, $post_id, $post_data );

		unset( $data['ticket_provider'] );

		$event_id = Event::filter_event_id( $post_id, 'tickets-rsvp-classic-save' ) ?? $post_id;

		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		update_post_meta( $event_id, $tickets_handler->key_provider_field, Module::class );

		Module::get_instance()->ticket_add( $event_id, $data );
	}

	/**
	 * Maps Classic Editor metabox POST fields to ticket_add() data.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $post_data The POST data from the metabox.
	 *
	 * @return array<string,mixed> The mapped ticket data.
	 */
	public function map_post_to_ticket_data( array $post_data ): array {
		$rsvp_id = absint( $post_data['rsvp_id'] ?? 0 );
		$limit   = trim( (string) ( $post_data['rsvp_limit'] ?? '' ) );

		$tribe_ticket = [];

		if ( '' !== $limit && (int) $limit > 0 ) {
			$tribe_ticket['mode']     = Global_Stock::OWN_STOCK_MODE;
			$tribe_ticket['capacity'] = (int) $limit;
		} else {
			$tribe_ticket['mode'] = '';
		}

		$data = [
			'ticket_id'          => $rsvp_id ?: null,
			'ticket_name'        => 'RSVP',
			'ticket_description' => '',
			'ticket_price'       => 0,
			'ticket_type'        => Constants::TC_RSVP_TYPE,
			'ticket_provider'    => sanitize_text_field( $post_data['ticket_provider'] ?? Module::class ),
			'show_not_going'     => isset( $post_data['show_not_going'] ) ? tribe_is_truthy( $post_data['show_not_going'] ) : false,
			'tribe-ticket'       => $tribe_ticket,
		];

		if ( ! empty( $post_data['rsvp_start_date'] ) ) {
			$data['ticket_start_date'] = sanitize_text_field( $post_data['rsvp_start_date'] );
		}

		if ( ! empty( $post_data['rsvp_start_time'] ) ) {
			$data['ticket_start_time'] = sanitize_text_field( $post_data['rsvp_start_time'] );
		}

		if ( ! empty( $post_data['rsvp_end_date'] ) ) {
			$data['ticket_end_date'] = sanitize_text_field( $post_data['rsvp_end_date'] );
		}

		if ( ! empty( $post_data['rsvp_end_time'] ) ) {
			$data['ticket_end_time'] = sanitize_text_field( $post_data['rsvp_end_time'] );
		}

		return $data;
	}

	/**
	 * Registers save_post hooks for ticket-enabled post types.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_save_post_hooks(): void {
		foreach ( (array) tribe_get_option( 'ticket-enabled-post-types', [] ) as $post_type ) {
			add_action( "save_post_{$post_type}", [ $this, 'save_rsvp_on_post_save' ], 20, 2 );
		}
	}

	/**
	 * Unregisters save_post hooks for ticket-enabled post types.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister_save_post_hooks(): void {
		foreach ( (array) tribe_get_option( 'ticket-enabled-post-types', [] ) as $post_type ) {
			remove_action( "save_post_{$post_type}", [ $this, 'save_rsvp_on_post_save' ], 20 );
		}
	}
}
