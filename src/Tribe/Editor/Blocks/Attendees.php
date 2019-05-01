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
	 * Get the attendees for the event
	 *
	 * @since 4.9
	 *
	 * @param  array $attributes
	 *
	 * @return string
	 */
	public function get_attendees( $post_id ) {

		$post   = get_post( $post_id );
		$output = array();
		if ( ! $post instanceof WP_Post ) {
			return $output;
		}

		// @todo Determine how to best optimize this request so the foreach below is not relied on as heavily.
		$attendees = Tribe__Tickets__Tickets::get_event_attendees( $post->ID );
		$emails    = array();

		// Bail if there are no attendees
		if ( empty( $attendees ) || ! is_array( $attendees ) ) {
			return;
		}

		foreach ( $attendees as $key => $attendee ) {
			// Only Check for optout when It's there
			// @todo Convert this to ORM somehow.
			if ( isset( $attendee['optout'] ) && false !== $attendee['optout'] ) {
				continue;
			}

			// Skip when we already have another email like this one.
			if ( in_array( $attendee['purchaser_email'], $emails, true ) ) {
				continue;
			}

			// Skip folks who've RSVPed as "Not Going".
			// @todo Convert this to ORM order_status__not_in.
			if ( 'no' === $attendee['order_status'] ) {
				continue;
			}

			// Skip "Failed" orders
			// @todo Convert this to ORM order_status__not_in.
			if ( 'failed' === $attendee['order_status'] ) {
				continue;
			}

			$emails[] = $attendee['purchaser_email'];
			$output[] = $attendee;
		}

		return $output;

	}
}
