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
use TEC\Tickets\RSVP\V2\Data_Transfer_Objects\Classic_Editor_Post_Data;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Classic_Editor
 *
 * Handles Classic Editor integration for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 *
 * @phpstan-import-type Post_Data from Classic_Editor_Post_Data
 * @phpstan-import-type Ticket_Add_Data from Classic_Editor_Post_Data
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
	 * Hooked on the generic `save_post` action and guarded to only run for ticket-able
	 * post types, so we register a single hook instead of one per post type.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID being saved.
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

		if ( ! tribe_tickets_post_type_enabled( get_post_type( $post_id ) ) ) {
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
	 * @param array<string,mixed> $post_data The unslashed $_POST data from the metabox.
	 * @phpstan-param Post_Data   $post_data
	 *
	 * @return void
	 */
	private function process_rsvp_post_save( int $post_id, array $post_data ): void {
		if ( empty( $post_data['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $post_data['ticket_type'] ) {
			return;
		}

		if ( ! isset( $post_data['tec_tickets_rsvp_enable'] ) ) {
			return;
		}

		$data = Classic_Editor_Post_Data::from_post_data( $post_data )->to_ticket_add_data();

		/**
		 * Filters the ticket data before saving TC-RSVP from the Classic Editor post save.
		 *
		 * @since TBD
		 *
		 * @param Ticket_Add_Data $data      The serialized ticket data for ticket_add().
		 * @param int             $post_id   The parent post ID.
		 * @param Post_Data       $post_data The raw POST data from the metabox.
		 */
		$data = apply_filters( 'tec_tickets_rsvp_v2_classic_save_data', $data, $post_id, $post_data );

		unset( $data['ticket_provider'] );

		$event_id = Event::filter_event_id( $post_id, 'tickets-rsvp-classic-save' ) ?? $post_id;

		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		update_post_meta( $event_id, $tickets_handler->key_provider_field, Module::class );

		Module::get_instance()->ticket_add( $event_id, $data );
	}
}
