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
	 * Configure all action and filters user by this Class
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'add_meta_boxes', array( $this, 'configure' ) );
	}

	/**
	 * Configures the Tickets Editor into a Post Type
	 *
	 * @since  TBD
	 *
	 * @param  string $post_type Which post type we are trying to configure
	 *
	 * @return void
	 */
	public function configure( $post_type = null ) {
		$modules = Tribe__Tickets__Tickets::modules();
		if ( empty( $modules ) ) {
			return;
		}

		if ( ! in_array( $post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
			return;
		}

		add_meta_box(
			'tribetickets',
			esc_html__( 'Tickets', 'event-tickets' ),
			array( $this, 'render' ),
			$post_type,
			'normal',
			'high'
		);

		// If we get here means that we will need Thickbox
		add_thickbox();
	}

	/**
	 * Render the actual Metabox
	 *
	 * @since  TBD
	 *
	 * @param  int   $post_id  Which post we are dealing with
	 *
	 * @return string|bool
	 */
	public function render( $post_id ) {
		$modules = Tribe__Tickets__Tickets::modules();
		if ( empty( $modules ) ) {
			return false;
		}

		$post = get_post( $post_id );

		// Prepare all the variables required
		$start_date = date( 'Y-m-d H:00:00' );
		$end_date   = date( 'Y-m-d H:00:00' );
		$start_time = Tribe__Date_Utils::time_only( $start_date, false );
		$end_time   = Tribe__Date_Utils::time_only( $start_date, false );

		$show_global_stock = Tribe__Tickets__Tickets::global_stock_available();
		$tickets           = Tribe__Tickets__Tickets::get_event_tickets( $post->ID );
		$global_stock      = new Tribe__Tickets__Global_Stock( $post->ID );

		return tribe( 'tickets.admin.views' )->template( array( 'editor', 'metabox' ), get_defined_vars() );
	}

	/**
	 * Registers the tickets metabox if there's at least
	 * one Tribe Tickets module (provider) enabled
	 *
	 * @deprecated TBD
	 *
	 * @param $post_type
	 */
	public static function maybe_add_meta_box( $post_type ) {
		tribe( 'tickets.metabox' )->configure( $post_type );
	}

	/**
	 * Loads the content of the tickets metabox if there's at
	 * least one Tribe Tickets module (provider) enabled
	 *
	 * @deprecated TBD
	 *
	 * @param $post_id
	 */
	public static function do_modules_metaboxes( $post_id ) {
		tribe( 'tickets.metabox' )->render( $post_id );
	}

	/**
	 * Enqueue the tickets metabox JS and CSS
	 *
	 * @deprecated 4.6
	 *
	 * @param $unused_hook
	 */
	public static function add_admin_scripts( $unused_hook ) {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets__Assets::admin_enqueue_scripts' );
	}

	// leaving this alone for now as Community Tickets uses it
	public static function localize_decimal_character() {
		$locale  = localeconv();
		$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

		/**
		 * Filter the decimal point character used in the price
		 */
		$decimal = apply_filters( 'tribe_event_ticket_decimal_point', $decimal );

		wp_localize_script( 'event-tickets-js', 'price_format', array(
			'decimal' => $decimal,
			'decimal_error' => __( 'Please enter in without thousand separators and currency symbols.', 'event-tickets' ),
		) );
	}
}
