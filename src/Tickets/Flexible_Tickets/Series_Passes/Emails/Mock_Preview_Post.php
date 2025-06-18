<?php
/**
 * Methods common to mock post generators.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;

use TEC\Common\StellarWP\DB\DB;
use WP_Post;

/**
 * Trait Mock_Preview_Post.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */
trait Mock_Preview_Post {
	/**
	 * The mock post ID.
	 *
	 * @since 5.8.4
	 *
	 * @var int|null
	 */
	private ?int $mock_post_id = null;

	/**
	 * The post mock meta fields in the array of arrays format returned by a `get_post_meta($id)` call.
	 *
	 * @since 5.8.4
	 * @var array<string,array<mixed>>
	 */
	private array $mock_meta = [];

	/**
	 * The mock post object
	 *
	 * @since 5.8.4
	 *
	 * @var WP_Post|null
	 */
	private ?WP_Post $mock_post = null;

	/**
	 * Cleans the post cache when the mock post is garbage-collected.
	 *
	 * @since 5.8.4
	 */
	public function __destruct() {
		$this->clean_post_cache();
	}

	/**
	 * Builds and returns the mock post object.
	 *
	 * @since 5.8.4
	 *
	 * @return WP_Post The mock post object.
	 */
	public function get_post(): WP_Post {
		return $this->mock_post ?? $this->build_mock_post();
	}


	/**
	 * Finds an unused post ID to use for the Series mock post.
	 *
	 * @since 5.8.4
	 *
	 * @return int The available post ID.
	 */
	private function get_available_mock_id(): int {
		global $wpdb;
		$highest_post_id = (int) DB::get_var(
			DB::prepare(
				'SELECT ID FROM %i ORDER BY ID DESC LIMIT 1',
				$wpdb->posts
			)
		);

		// Add some padding for good measure and some rudimentary thread-safety.
		$id_padding = (int)(tribe_cache()['mock_preview_post_id_padding'] ?? 1000);
		$mock_id = $highest_post_id + $id_padding;

		// Increment the padding for the next call.
		tribe_cache()['mock_preview_post_id_padding'] = $id_padding + 1;

		return $mock_id;
	}

	/**
	 * Cleans the post cache for the mock post.
	 *
	 * @since 5.8.4
	 *
	 * @return void The mock post cache is removed.
	 */
	public function clean_post_cache(): void {
		if ( $this->mock_post_id === null ) {
			return;
		}

		$cached = wp_cache_get( $this->mock_post_id, 'posts' );

		if ( ! ( is_object( $cached ) && isset( $cached->_is_mock_post ) ) ) {
			// Either the cache was already cleared, or it's been overridden.
			return;
		}

		clean_post_cache( $this->mock_post_id );
	}

	/**
	 * Builds the mock post and adds it to the cache.
	 *
	 * @since 5.8.4
	 *
	 * @return WP_Post The mock post object.
	 */
	private function build_mock_post(): WP_Post {
		// Find the first unused post ID.
		$mock_post_id = $this->get_available_mock_id();

		// Create the post data and mock the post in WordPress cache layer.
		[ $post_data, $post_meta ] = $this->get_post_data( $mock_post_id );

		// Set a flag that will help identifying the mock and avoid evicting it from cache if overwritten.
		$post_data['_is_mock_post'] = true;

		$this->mock_post = new \WP_Post( (object) $post_data );

		wp_cache_add( $mock_post_id, $this->mock_post, 'posts' );
		wp_cache_add( $mock_post_id, $post_meta, 'post_meta' );

		// Evict the post from the cache on shutdown, i.e. at the end of this request.
		add_filter( 'shutdown', [ $this, 'clean_post_cache' ] );

		$this->mock_post_id = $mock_post_id;

		return $this->mock_post;
	}
}
