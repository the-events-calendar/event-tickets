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
			[],
			null
		);
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it
	 *
	 * @since 4.9
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function render( $attributes = [] ) {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template        = tribe( 'tickets.editor.template' );
		$args['post_id'] = $post_id = $template->get( 'post_id', null, false );

		if ( empty( $post_id ) ) {
			return '';
		}

		$args['attributes'] = $this->attributes( $attributes );

		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		$args['attendees'] = $attendees_list->get_attendees_for_post( $post_id );

		/**
		 * Add the rendering attributes into global context.
		 *
		 * Start with the following for template files loading this global context.
		 * Keep all templates with this starter block of comments updated if these global args update.
		 *
		 * @var Tribe__Tickets__Editor__Template $this       Template object.
		 * @var int                              $post_id    [Global] The current Post ID to which tickets are attached.
		 * @var array                            $attributes [Global] Attendee block's attributes (such as Title above block).
		 * @var array                            $attendees  [Global] List of attendees with attendee data.
		 */
		$template->add_template_globals( $args );

		// enqueue assets
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-attendees-style' );

		return $template->template( [ 'blocks', $this->slug() ], $args, false );
	}

	/**
	 * Get the attendees for the event.
	 *
	 * @since 4.9
	 * @since 4.12.0 Changed to use \Tribe\Tickets\Events\Attendees_List::get_attendees_for_post().
	 *
	 * @param WP_Post|int $post_id Post object or ID.
	 *
	 * @return array
	 */
	public function get_attendees( $post_id ) {
		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		return $attendees_list->get_attendees_for_post( $post_id );
	}
}
