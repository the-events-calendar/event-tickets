<?php

/**
 *    Class in charge of registering and displaying
 *  the tickets metabox in the event edit screen.
 *  Metabox will only be added if there's a
 *     Tickets Pro provider (child of TribeTickets)
 *     available.
 */
class Tribe__Tickets__Metabox {

	/**
	 * Registers the tickets metabox if there's at least
	 * one Tribe Tickets module (provider) enabled
	 * @static
	 *
	 * @param $post_type
	 */
	public static function maybe_add_meta_box( $post_type ) {
		$modules = apply_filters( 'tribe_events_tickets_modules', null );
		if ( empty( $modules ) ) {
			return;
		}

		if ( ! in_array( $post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
			return;
		}

		add_meta_box(
			'tribetickets',
			esc_html__( 'Tickets', 'event-tickets' ),
			array(
				'Tribe__Tickets__Metabox',
				'do_modules_metaboxes',
			),
			$post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Loads the content of the tickets metabox if there's at
	 * least one Tribe Tickets module (provider) enabled
	 * @static
	 *
	 * @param $post_id
	 */
	public static function do_modules_metaboxes( $post_id ) {

		$modules = apply_filters( 'tribe_events_tickets_modules', null );
		if ( empty( $modules ) ) {
			return;
		}

		Tribe__Tickets__Tickets_Handler::instance()->do_meta_box( $post_id );
	}

	/**
	 * Enqueue the tickets metabox JS and CSS
	 * @static
	 *
	 * @param $hook
	 */
	public static function add_admin_scripts( $hook ) {
		global $post;

		$modules = apply_filters( 'tribe_events_tickets_modules', null );

		/* Only load the resources in the event edit screen, and if there's a provider available */
		if ( ( $hook != 'post-new.php' && $hook != 'post.php' ) || ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types() ) || empty( $modules ) ) {
			return;
		}

		$resources_url = plugins_url( 'src/resources', dirname( dirname( __FILE__ ) ) );

		wp_enqueue_style( 'event-tickets', $resources_url .'/css/tickets.css', array(), Tribe__Tickets__Main::instance()->css_version() );
		wp_enqueue_script( 'event-tickets', $resources_url .'/js/tickets.js', array( 'jquery-ui-datepicker' ), Tribe__Tickets__Main::instance()->js_version(), true );

		$upload_header_data = array(
			'title'  => esc_html__( 'Ticket header image', 'event-tickets' ),
			'button' => esc_html__( 'Set as ticket header', 'event-tickets' ),
		);
		wp_localize_script( 'event-tickets', 'HeaderImageData', $upload_header_data );


		$nonces = array(
			'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
			'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
			'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
		);

		wp_localize_script( 'event-tickets', 'TribeTickets', $nonces );
	}
}

