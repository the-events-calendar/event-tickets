<?php
/**
 * Simulates a Series Post for the purpose of the email preview.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use WP_Post;

/**
 * Class Mock_Series_Post.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */
class Mock_Series_Post {
	use Mock_Preview_Post;

	/**
	 * Filters the start date for the mock series post.
	 *
	 * @since 5.8.4
	 *
	 * @param string|null $start_date   The start date.
	 * @param WP_Post     $post         The post the start date is being filtered for.
	 * @param bool        $display_time Whether to display the time.
	 * @param string      $date_format  The date format.
	 *
	 * @return string  The filtered start date, if filtering for the mock post.
	 * @internal
	 */
	public function filter_start_date( $start_date, $post, $display_time, $date_format ) {
		if ( $post->ID !== $this->mock_post_id ) {
			return $start_date;
		}
		$mock_date = wp_date( 'Y-m-d H:i:s', strtotime( '9/22' ) );

		return tribe_format_date( $mock_date, false, $date_format );
	}

	/**
	 * Filters the end date for the mock series post.
	 *
	 * @since 5.8.4
	 *
	 * @param string|null $end_date     The end date.
	 * @param WP_Post     $post         The post the end date is being filtered for.
	 * @param bool        $display_time Whether to display the time.
	 * @param string      $date_format  The date format.
	 *
	 * @return string  The filtered end date, if filtering for the mock post.
	 * @internal
	 */
	public function filter_end_date( $end_date, $post, $display_time, $date_format ) {
		if ( $post->ID !== $this->mock_post_id ) {
			return $end_date;
		}
		$mock_date = wp_date( 'Y-m-d H:i:s', strtotime( '11/25' ) );

		return tribe_format_date( $mock_date, false, $date_format );
	}

	/**
	 * Builds the Series mock post data.
	 *
	 * @since 5.8.4
	 *
	 * @param int $mock_post_id The mock post ID.
	 *
	 * @return array{0: array<string,mixed>, 1: array<string,array<mixed>>} The mock post data and meta, respectively.
	 */
	private function get_post_data( int $mock_post_id ): array {
		$post_data = [
			'ID'             => $mock_post_id,
			'post_author'    => get_current_user_id(),
			'post_date'      => '2023-04-17 17:06:56',
			'post_date_gmt'  => '2023-04-17 17:06:56',
			'post_title'     => _x(
				'Sidewalk Fall Film Festival',
				'Email preview series pass title',
				'event-tickets'
			),
			'post_excerpt'   => _x(
				'Let the SideWalk Fall Film Festival be your ticket to the best films of past seasons.',
				'Email preview series pass excerpt',
				'event-tickets'
			),
			'post_status'    => 'publish',
			'post_permalink' => '#',
			'post_name'      => "preview-series-{$mock_post_id}",
			'post_type'      => Series::POSTTYPE,
			'filter'         => 'raw',
		];

		add_filter( 'tribe_get_start_date', [ $this, 'filter_start_date' ], 100, 4 );
		add_filter( 'tribe_get_end_date', [ $this, 'filter_end_date' ], 100, 4 );

		return [ $post_data, [] ];
	}
}
