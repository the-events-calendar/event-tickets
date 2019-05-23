<?php

class Tribe__Tickets__Editor__Blocks__Attendees
	extends Tribe__Editor__Blocks__Abstract {

	/**
	 * Which is the name/slug of this block
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function slug() {
		return 'attendees';
	}

	/**
	 * Set the default attributes of this block
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	public function default_attributes() {

		$defaults = array(
			'title' => __( "Who's coming?", 'event-tickets' ),
		);

		return $defaults;
	}

	/**
	 * Register block assets
	 *
	 * @since 4.9
	 *
	 * @param  array $attributes
	 *
	 * @return void
	 */
	public function assets() {
		$gutenberg = Tribe__Tickets__Main::instance();

		tribe_asset(
			$gutenberg,
			'tribe-tickets-gutenberg-block-attendees-style',
			'app/attendees/frontend.css',
			array(),
			null
		);
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it
	 *
	 * @since 4.9
	 *
	 * @param  array $attributes
	 *
	 * @return string
	 */
	public function render( $attributes = array() ) {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template        = tribe( 'tickets.editor.template' );
		$args['post_id'] = $post_id = $template->get( 'post_id', null, false );

		if ( empty( $post_id ) ) {
			return '';
		}

		$args['attributes'] = $this->attributes( $attributes );
		$args['attendees']  = $this->get_attendees( $post_id );

		// Add the rendering attributes into global context
		$template->add_template_globals( $args );

		// enqueue assets
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-attendees-style' );

		return $template->template( array( 'blocks', $this->slug() ), $args, false );
	}

	/**
	 * Get the attendees for the event.
	 *
	 * @since 4.9
	 *
	 * @param WP_Post|int $post_id Post object or ID.
	 *
	 * @return array
	 */
	public function get_attendees( $post_id ) {
		$post   = get_post( $post_id );
		$output = [];

		if ( ! $post instanceof WP_Post ) {
			return $output;
		}

		$args = [
			'by' => [
				// Exclude people who have opted out or not specified optout.
				'optout' => 'no_or_none',
			],
		];

		/**
		 * Allow for adjusting the limit of attendees fetched from the database for the front-end "Who's Coming?" list.
		 *
		 * @since 4.10.6
		 *
		 * @param int $limit_attendees Number of attendees to retrieve. Default is no limit -1.
		 */
		$limit_attendees = (int) apply_filters( 'tribe_tickets_attendees_list_limit_attendees', -1 );

		if ( 0 < $limit_attendees ) {
			$args['per_page'] = $limit_attendees;
		}

		$attendees = Tribe__Tickets__Tickets::get_event_attendees( $post->ID, $args );
		$emails    = [];

		// Bail if there are no attendees
		if ( empty( $attendees ) || ! is_array( $attendees ) ) {
			return $output;
		}

		$excluded_statuses = [
			'no',
			'failed',
		];

		foreach ( $attendees as $key => $attendee ) {
			// Skip when we already have another email like this one.
			if ( in_array( $attendee['purchaser_email'], $emails, true ) ) {
				continue;
			}

			// Skip "Failed" orders and folks who've RSVPed as "Not Going".
			if ( in_array( $attendee['order_status'], $excluded_statuses, true ) ) {
				continue;
			}

			$emails[] = $attendee['purchaser_email'];
			$output[] = $attendee;
		}

		return $output;
	}
}
