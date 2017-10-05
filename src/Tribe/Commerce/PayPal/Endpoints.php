<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Endpoints
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Endpoints {

	/**
	 * @var array
	 */
	protected $template_data = array();

	/**
	 * Hooks the class to actions and filters.
	 *
	 * @since TBD
	 */
	public function hook() {
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'replace_posts' ) );
		add_filter( 'the_content', array( $this, 'filter_the_content' ) );
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
	}

	public function add_rewrite_rules() {
		$success_slug = preg_quote( $this->success_slug(), '/' );

		// match `/tpp-success/WR45SDF5689FGD`
		add_rewrite_rule(
			'^' . $success_slug . '/(\\w+)/?',
			'index.php?tribe-tpp-page=success&tribe-tpp-order=$matches[1]',
			'top'
		);
	}

	/**
	 * Returns the full URL to the success endpoint.
	 *
	 * @since TBD
	 *
	 * @param string $path An optional path that should be appended to the success URL.
	 *
	 * @return string
	 */
	public function success_url( $path = '' ) {
		$url = home_url( $this->success_slug() );

		return ! empty( $path ) ? trailingslashit( $url ) . trim( $path, '/' ) : $url;
	}

	/**
	 * Returns the filtered and localized slug used by the success endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function success_slug() {
		$success_slug = _x( 'tpp-success', 'PayPal tickets success slug', 'event-tickets' );

		/**
		 * Filters the PayPal tickets success endpoint slug after it has been localized.
		 *
		 * @since TBD
		 *
		 * @param string $success_slug
		 */
		return apply_filters( 'tribe_tickets_paypal_success_slug', $success_slug );
	}


	/**
	 * Add new query vars needed to identify and manage the endpoints.
	 *
	 * @since TBD
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function filter_query_vars( $vars ) {
		$vars[] = 'tribe-tpp-page';
		$vars[] = 'tribe-tpp-order';

		return $vars;
	}

	/**
	 * Returns the slug of the PayPal tickets page set in the query variables if any.
	 *
	 * This only works after parse_query has run.
	 *
	 * @return string|bool
	 */
	protected function get_page_slug() {
		$slug = get_query_var( 'tribe-tpp-page', false );
		if ( ! in_array( $slug, $this->supported_endpoints(), true ) ) {
			return false;
		}

		return $slug;
	}

	/**
	 * Intercepts the_content from the posts to include the content of an endpoint managed by the class.
	 *
	 * @since TBD
	 *
	 * @param  string $content Normally the_content of a post
	 *
	 * @return string
	 */
	public function filter_the_content( $content = '' ) {
		// Prevents firing more then it needs too outside of the loop
		$in_the_loop = isset( $GLOBALS['wp_query']->in_the_loop ) && $GLOBALS['wp_query']->in_the_loop;

		$slug = $this->get_page_slug();

		// Prevents Weird
		if ( false === (bool) $slug || ! $in_the_loop ) {
			return $content;
		}

		$template = $this->build_template_for( $slug );
		$template->enqueue_resources();

		extract( $template->get_template_data( $this->template_data ), EXTR_OVERWRITE );

		ob_start();
		include Tribe__Tickets__Templates::get_template_hierarchy( "tickets/tpp-{$slug}.php" );
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Returns a list of endpoint slugs supported by the class.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function supported_endpoints() {
		return array( 'success' );
	}

	/**
	 * Replaces the query vars for the main query to fetch, instead, the post associated with an order.
	 *
	 * @since TBD
	 *
	 * @param \WP_Query $query
	 */
	public function replace_posts( WP_Query $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		$order = $query->get( 'tribe-tpp-order', false );

		if ( empty( $order ) ) {
			return;
		}

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal  = tribe( 'tickets.commerce.paypal' );
		$post_id = $paypal->get_post_id_from_order( $order );

		$query->parse_query( array( 'p' => $post_id, 'tribe-tpp-page' => 'success', 'tribe-tpp-order' => $order ) );

		$this->template_data['post_id']      = $post_id;
		$this->template_data['order_number'] = $order;
	}

	/**
	 * Builds the correct template class to handle a template.
	 *
	 * @since TBD
	 *
	 * @param string $slug
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Endpoints__Template_Interface
	 */
	protected function build_template_for( $slug ) {
		switch ($slug) {
			case 'success':
				$template      = tribe( 'tickets.commerce.paypal.endpoints.templates.success' );
				break;
		}

		return $template;
	}
}