<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets..hooks' ), 'some_method' ] );
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Payments_Tab;
use WP_Query;
use WP_Post;

/**
 * Class Hooks.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets component.
	 *
	 * @since 5.1.6
	 */
	protected function add_actions() {
		add_action( 'tribe_settings_do_tabs', [ tribe( Payments_Tab::class ), 'register_tab' ], 15 );
		add_action( 'tribe_settings_after_save_' . Payments_Tab::$slug, [ $this, 'generate_payments_pages' ] );

		$this->container->register( Ticket_Cache_Controller::class );
	}

	/**
	 * Provides the results for the events dropdown in the Orders table.
	 *
	 * @since 5.20.0
	 *
	 * @param array<string,mixed>  $results The results.
	 * @param array<string,string> $search The search.
	 *
	 * @return array<string,mixed>
	 */
	public function provide_events_results_to_ajax( $results, $search ) {
		if ( empty( $search['term'] ) ) {
			return $results;
		}

		$term = $search['term'];

		$args = [
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'post_type'              => (array) tribe_get_option( 'ticket-enabled-post-types', [] ),
			'post_status'            => 'any',
			'posts_per_page'         => 10,
			's'                      => $term,
			// Default to show most recent first.
			'orderby'                => 'ID',
			'order'                  => 'DESC',
		];

		$query = new WP_Query( $args );

		if ( empty( $query->posts ) ) {
			return $results;
		}

		$results = array_map(
			function ( WP_Post $result ) {
				return [
					'id'   => $result->ID,
					'text' => get_the_title( $result->ID ),
				];
			},
			$query->posts
		);

		return [ 'results' => $results ];
	}

	/**
	 * Generate TicketsCommerce Pages.
	 *
	 * @since 5.2.1
	 */
	public function generate_payments_pages() {
		$this->container->make( Payments_Tab::class )->maybe_generate_pages();
	}

	/**
	 * Adds the filters required by each Tickets component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_settings_tabs_ids', [ tribe( Payments_Tab::class ), 'settings_add_tab_id' ] );
		add_filter( 'tribe_dropdown_tec_tickets_list_ticketables_ajax', [ $this, 'provide_events_results_to_ajax' ], 10, 2 );
	}
}
