<?php

class Tribe__Tickets__Commerce__PayPal__Orders__Report {

	/**
	 * Slug of the admin page for orders
	 *
	 * @var string
	 */
	public static $orders_slug = 'tpp-orders';

	/**
	 * @var The menu slug of the orders page
	 */
	public $orders_page;

	public function hook() {
		add_filter( 'post_row_actions', array( $this, 'add_orders_row_action' ), 10, 2 );
//		add_action( 'tribe_tickets_attendees_page_inside', array( $this, 'render_tabbed_view' ) );
//		add_action( 'admin_menu', array( $this, 'register_orders_page' ) );
	}

	public function add_orders_row_action( array $actions, $post ) {
		$post_id = Tribe__Main::post_id_helper( $post );
		$post    = get_post( $post_id );

		// only if tickets are active on this post type
		if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types(), true ) ) {
			return $actions;
		}

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		$has_tickets = count( $paypal->get_tickets_ids( $post->ID ) );

		if ( ! $has_tickets ) {
			return $actions;
		}

		$url         = $paypal->get_event_reports_link( $post->ID, true );
		$post_labels = get_post_type_labels( get_post_type_object( $post->post_type ) );
		$post_type   = strtolower( $post_labels->singular_name );

		$actions['tickets_orders'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			sprintf( esc_html__( 'See PayPal purchases for this %s', 'event-tickets-plus' ), $post_type ),
			esc_url( $url ),
			esc_html__( 'PayPal Orders', 'event-tickets-plus' )
		);

		return $actions;
	}

	/**
	 * Renders the tabbed view header before the report.
	 *
	 * @param Tribe__Tickets__Tickets_Handler $handler
	 */
	public function render_tabbed_view( Tribe__Tickets__Tickets_Handler $handler ) {
		$post = $handler->get_post();

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		$has_tickets = count( (array) $paypal->get_tickets_ids( $post->ID ) );
		if ( ! $has_tickets ) {
			return;
		}

		$handler->should_render_title( false );

		$tabbed_view = new Tribe__Tickets__Commerce__PayPal__Orders__Tabbed_View( $post->ID );
		$tabbed_view->render();
	}

	public function register_orders_page(  ) {
		$cap = 'edit_posts';
		$post_id = absint( ! empty( $_GET['post_id'] ) && is_numeric( $_GET['post_id'] ) ? $_GET['post_id'] : 0 );

		if ( ! current_user_can( 'edit_posts' ) && $post_id ) {
			$event = get_post( $post_id );

			if ( $event instanceof WP_Post && get_current_user_id() === (int) $event->post_author ) {
				$cap = 'read';
			}
		}

		$this->orders_page = add_submenu_page( null, 'Attendee list', 'Attendee list', $cap, self::$attendees_slug, array( $this, 'attendees_page_inside' ) );

//		add_action( 'admin_enqueue_scripts', array( $this, 'attendees_page_load_css_js' ) );
//		add_action( 'admin_enqueue_scripts', array( $this, 'attendees_page_load_pointers' ) );
		add_action( 'load-' . $this->orders_page, array( $this, 'attendees_page_screen_setup' ) );

		/**
		 * This is a workaround to fix the problem
		 *
		 * @see  https://central.tri.be/issues/46198
		 * @todo  we need to remove this
		 */
		add_action( 'admin_init', array( $this, 'attendees_page_screen_setup' ), 1 );
	}
}