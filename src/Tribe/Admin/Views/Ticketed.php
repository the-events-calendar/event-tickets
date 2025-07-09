<?php

use Tribe__Tickets__Query as Query;

/**
 * Class Tribe__Tickets__Admin__Views__Ticketed
 *
 * Adds ticket status related views to the post edit screens.
 */
class Tribe__Tickets__Admin__Views__Ticketed {

	/**
	 * @var string
	 */
	protected $post_type;

	/**
	 * Tribe__Tickets__Admin__Views__Ticketed constructor.
	 *
	 * @param string $post_type
	 */
	public function __construct( $post_type = 'post' ) {
		$this->post_type = $post_type;
	}

	/**
	 * Filters the views for this post type to add the ticket status related ones.
	 *
	 * @param array $views An array of views for this post type.
	 *
	 * @return array
	 */
	public function filter_edit_link( array $views = [] ) {
		/** @var Query $query */
		$query                    = tribe( 'tickets.query' );
		$ticketed_query_var       = Query::$has_tickets;
		$ticketed_query_var_value = get_query_var( $ticketed_query_var );

		$ticketed_args  = [
			'post_type'         => $this->post_type,
			$ticketed_query_var => '1',
			'post_status'       => 'any',
			'paged'             => 1,
		];
		$ticketed_url   = add_query_arg( $ticketed_args );
		$ticketed_label = __( 'Ticketed', 'event-tickets' );
		$ticketed_count = $query->get_ticketed_count( $this->post_type );
		$ticketed_class = '1' === $ticketed_query_var_value ? 'class="current"' : '';

		$views['tickets-ticketed'] = sprintf( '<a href="%s" %s>%s</a> (%d)', esc_url( $ticketed_url ), $ticketed_class, $ticketed_label, $ticketed_count );

		$unticketed_args  = [
			'post_type'         => $this->post_type,
			$ticketed_query_var => '0',
			'post_status'       => 'any',
			'paged'             => 1,
		];
		$unticketed_url   = add_query_arg( $unticketed_args );
		$unticketed_label = __( 'Unticketed', 'event-tickets' );
		$unticketed_count = $query->get_unticketed_count( $this->post_type );
		$unticketed_class = '0' === $ticketed_query_var_value ? 'class="current"' : '';

		$views['tickets-unticketed'] = sprintf(
			'<a href="%s" %s>%s</a> (%d)',
			esc_url( $unticketed_url ),
			$unticketed_class,
			$unticketed_label,
			$unticketed_count 
		);

		return $views;
	}
}
