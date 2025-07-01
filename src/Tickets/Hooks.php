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
 * @since 5.1.6
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Payments_Tab;
use Tribe__Tickets__RSVP;
use WP_Query;
use WP_Post;

/**
 * Class Hooks.
 *
 * @since 5.1.6
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
		$this->container->register( Ticket_Cache_Controller::class );

		add_action( 'admin_post_tec_tickets_remove_orphans', [ $this, 'remove_orphans' ] );
		add_action( 'tec_tickets_remove_orphans_action', [ $this, 'remove_orphans_action' ], 10, 1 );

		// Cache invalidation hooks for orphaned posts.
		add_action( 'deleted_post', [ $this, 'clear_orphaned_posts_cache' ] );
		add_action( 'wp_trash_post', [ $this, 'clear_orphaned_posts_cache' ] );
		add_action( 'untrashed_post', [ $this, 'clear_orphaned_posts_cache' ] );
	}

	/**
	 * Trash orphaned entries.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function remove_orphans() {
		// Bail if not admin.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html_x( 'Not allowed.', 'Orphaned posts', 'event-tickets' ) );
		}

		// Bail if nonce verification fails.
		$nonce = tec_get_request_var( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, 'tec_tickets_remove_orphans' ) ) {
			wp_die( esc_html_x( 'Nonce verification failed.', 'Orphaned posts', 'event-tickets' ) );
		}

		// Bail if no provider specified.
		$provider = tec_get_request_var( 'provider', false );
		if ( ! $provider ) {
			wp_die( esc_html_x( 'No provider specified.', 'Orphaned posts', 'event-tickets' ) );
		}

		// Get IDs.
		if ( $provider === 'rsvp' ) {
			$ids = tribe( Tribe__Tickets__RSVP::class )->get_orphaned_posts( false );
		} elseif ( $provider === 'tc_ticket' ) {
			$ids = tribe( \TEC\Tickets\Commerce\Module::class )->get_orphaned_posts( false );
		} else {
			wp_die( esc_html_x( 'Unsupported provider.', 'Orphaned posts', 'event-tickets' ) );
		}

		// Return URL.
		$url = add_query_arg( 'page', 'tec-tickets-settings', wp_get_referer() );

		// Bail if no post IDs.
		if ( empty( $ids ) ) {
			$this->render_orphan_notice( 'no_posts' );

			wp_safe_redirect( esc_url_raw( $url ) );
			tribe_exit();
		}

		// Count IDs. If less than 25, don't offload. If more, schedule action.
		if ( count( $ids ) < 25 ) {
			// Delete posts.
			foreach ( $ids as $id ) {
				wp_delete_post( $id );
			}
			$this->render_orphan_notice( 'done' );
		} else {
			as_schedule_single_action( time(), 'tec_tickets_remove_orphans_action', [ $provider ], 'tec_tickets_cleanup_actions' );
			$this->render_orphan_notice( 'scheduled' );
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		tribe_exit();
	}

	/**
	 * Schedule the action to delete orphaned posts.
	 *
	 * @since TBD
	 *
	 * @param string $provider The main service provider.
	 *
	 * @return void
	 */
	public function remove_orphans_action( $provider ) {
		// Get IDs.
		if ( $provider === 'rsvp' ) {
			$ids = tribe( Tribe__Tickets__RSVP::class )->get_orphaned_posts( false );
		} elseif ( $provider === 'tc_ticket' ) {
			$ids = tribe( \TEC\Tickets\Commerce\Module::class )->get_orphaned_posts( false );
		}

		if ( empty( $ids ) ) {
			return;
		}

		as_schedule_single_action( time(), 'tec_tickets_remove_orphans_action', [ $provider ], 'tec_tickets_cleanup_actions' );

		foreach ( $ids as $id ) {
			wp_delete_post( $id );
		}
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
		add_filter( 'tribe_dropdown_tec_tickets_list_ticketables_ajax', [ $this, 'provide_events_results_to_ajax' ], 10, 2 );
	}

	/**
	 * Clear the cache for orphaned post IDs.
	 *
	 * @since TBD
	 */
	public function clear_orphaned_posts_cache() {
		delete_transient( 'tec_tickets_orphaned_posts_tribe__tickets__rsvp' );
		delete_transient( 'tec_tickets_orphaned_posts_tecticketscommercemodule' );
	}

	/**
	 * Renders a notice about orphaned posts cleanup status.
	 *
	 * @since TBD
	 *
	 * @param string $type The type of notice to render. Possible values: 'scheduled', 'done', 'no_posts'.
	 *
	 * @return void
	 */
	public function render_orphan_notice( $type ) {
		switch ( $type ) {
			case 'scheduled':
				$message = esc_html__( 'Orphaned posts cleanup scheduled.', 'event-tickets' );
				break;
			case 'done':
				$message = esc_html__( 'Orphaned posts cleaned up.', 'event-tickets' );
				break;
			case 'no_posts':
				$message = esc_html__( 'No orphaned posts found.', 'event-tickets' );
				break;
			default:
				$message = esc_html__( 'Orphaned posts cleanup scheduled.', 'event-tickets' );
		}
		

		if ( $type === 'scheduled' ) {	
			tribe_transient_notice(
				'tec-tickets-orphan-cleanup-done',
				$message,
				[
					'type'    => 'warning',
					'dismiss' => 1,
					'wrap'    => 'p',
				],
				MINUTE_IN_SECONDS,
			);
		}
	}
	
}
