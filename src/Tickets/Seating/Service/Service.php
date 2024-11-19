<?php
/**
 * A facade for the Service interaction hiding the details of the service.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Admin\Tabs\Map_Card;
use TEC\Tickets\Seating\Meta;
use WP_Error;

/**
 * Class Service.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Service;
 */
class Service {
	use OAuth_Token;

	/**
	 * The base URL of the service.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $backend_base_url;
	/**
	 * The base URL of the service for frontend requests.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $frontend_base_url;

	/**
	 * A reference to the Ephemeral_Token handler.
	 *
	 * @since 5.16.0
	 *
	 * @var Ephemeral_Token
	 */
	private Ephemeral_Token $epehemeral_token;

	/**
	 * A reference to the Layouts handler.
	 *
	 * @since 5.16.0
	 *
	 * @var Layouts
	 */
	private Layouts $layouts;

	/**
	 * A reference to the Seat Types handler.
	 *
	 * @since 5.16.0
	 *
	 * @var Seat_Types
	 */
	private Seat_Types $seat_types;

	/**
	 * A reference to the Maps handler.
	 *
	 * @since 5.16.0
	 *
	 * @var Maps
	 */
	private Maps $maps;

	/**
	 * A memoized reference to the Service Status value object.
	 *
	 * @since 5.16.0
	 *
	 * @var Service_Status|null
	 */
	private ?Service_Status $status = null;

	/**
	 * Service constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param string          $backend_base_url The base URL of the service.
	 * @param string          $frontend_base_url The base URL of the service for frontend requests.
	 * @param Ephemeral_Token $ephemeral_token The ephemeral token handler.
	 * @param Layouts         $layouts The Layouts handler.
	 * @param Seat_Types      $seat_types The Seat Types handler.
	 * @param Maps            $maps The Maps handler.
	 */
	public function __construct(
		string $backend_base_url,
		string $frontend_base_url,
		Ephemeral_Token $ephemeral_token,
		Layouts $layouts,
		Seat_Types $seat_types,
		Maps $maps
	) {
		$this->backend_base_url  = $backend_base_url;
		$this->frontend_base_url = $frontend_base_url;
		$this->epehemeral_token  = $ephemeral_token;
		$this->layouts           = $layouts;
		$this->seat_types        = $seat_types;
		$this->maps              = $maps;
	}

	/**
	 * Fetches all the Maps from the database.
	 *
	 * @since 5.16.0
	 *
	 * @return Map_Card[] Array of map card objects.
	 */
	public function get_map_cards() {
		return $this->maps->get_in_card_format();
	}

	/**
	 * Fetches an ephemeral token from the service.
	 *
	 * @since 5.16.0
	 *
	 * @param int         $expiration The expiration in seconds. While this value is arbitrary, the service will still
	 *                                return a token whose expiration has been set to 15', 30', 1 hour or 6 hours.
	 * @param string|null $scope The scope of the token to request. Defaults to `visitor` to get a token with the least
	 *                           permissions.
	 *
	 * @return string|WP_Error Either a valid ephemeral token, or a `WP_Error` indicating the failure reason.
	 */
	public function get_ephemeral_token( int $expiration = 900, string $scope = 'visitor' ) {
		return $this->epehemeral_token->get_ephemeral_token( $expiration, $scope );
	}

	/**
	 * Returns the layouts in option format.
	 *
	 * @since 5.16.0
	 *
	 * @return array<string, array{id: string, name: string, seats: int}> The layouts in option format.
	 */
	public function get_layouts_in_option_format(): array {
		return $this->layouts->get_in_option_format();
	}

	/**
	 * Checks if the connection to the service is working from the backend.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the connection is working or not.
	 */
	public function check_connection(): bool {
		$response = wp_remote_head( $this->get_backend_url() );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns a service URL built from the base URL and the given path.
	 *
	 * @since 5.16.0
	 *
	 * @param string $path The path to append to the base URL.
	 *
	 * @return string The URL built from the base URL and the given path.
	 */
	public function get_backend_url( string $path = '' ): string {
		return rtrim( $this->backend_base_url . '/' . ltrim( $path, '/' ), '/' );
	}

	/**
	 * Checks if the access token is valid with the service.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the access token is valid or not.
	 */
	public function is_access_token_valid(): bool {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$response = wp_remote_get(
			$this->get_backend_url( '/api/v1/check' ),
			[
				'headers' => [
					'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
					'Accept'        => 'application/json',
				],
			]
		);

		return ! ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Returns the URL to load the service route to create a new map and associated layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The ephemeral token used to secure the iframe communication with the service.
	 *
	 * @return string The URL to load the service route to create a new map and associated layout.
	 */
	public function get_map_create_url( string $token ): string {
		return add_query_arg(
			[
				'token' => urlencode( $token ),
			],
			$this->get_frontend_url( '/embed/create-map-layout/' )
		);
	}

	/**
	 * Returns a service URL built from the base frontend URL and the given path.
	 *
	 * @since 5.16.0
	 *
	 * @param string $path The path to append to the base URL.
	 *
	 * @return string  The URL built from the base frontend URL and the given path.
	 */
	public function get_frontend_url( string $path = '' ): string {
		$args = 1 === (int) tribe_get_request_var( 'isNew', 0 ) ? [ 'isNew' => '1' ] : [];
		return add_query_arg( $args, rtrim( $this->frontend_base_url . '/' . ltrim( $path, '/' ), '/' ) );
	}

	/**
	 * Returns the URL to load the Maps create and edit page.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The ephemeral token used to secure the iframe communication with the service.
	 * @param string $map_id ID of th Map to return the edit URL for.
	 *
	 * @return string The URL to load the Maps create and edit page.
	 */
	public function get_map_edit_url( string $token, string $map_id ): string {
		return add_query_arg(
			[
				'token' => urlencode( $token ),
				'mapId' => urlencode( $map_id ),
			],
			$this->get_frontend_url( '/embed/seat-map/' )
		);
	}

	/**
	 * Returns the URL to load the service route to create a new seat layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The ephemeral token used to secure the iframe communication with the service.
	 * @param string $map_id ID of the Map to create the layout for.
	 *
	 * @return string The URL to load the service route to create a new seat layout.
	 */
	public function get_layout_create_url( string $token, string $map_id ): string {
		return add_query_arg(
			[
				'token' => urlencode( $token ),
				'mapId' => urlencode( $map_id ),
			],
			$this->get_frontend_url( '/embed/seat-layout/' )
		);
	}

	/**
	 * Returns the URL to load the service route to edit a seat layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token     The ephemeral token used to secure the iframe communication with the service.
	 * @param string $layout_id The ID of the layout to edit.
	 *
	 * @return string The URL to load the service route to edit a seat layout.
	 */
	public function get_layout_edit_url( string $token, string $layout_id ): string {
		$associated_posts = Layouts::get_associated_posts_by_id( $layout_id );

		return add_query_arg(
			[
				'token'            => urlencode( $token ),
				'layoutId'         => urlencode( $layout_id ),
				'associatedEvents' => urlencode( $associated_posts ),
			],
			$this->get_frontend_url( '/embed/seat-layout/' )
		);
	}

	/**
	 * Returns the URL to load the service route to purchase tickets with assigned seating.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token   The ephemeral token used to secure the iframe communication with the service.
	 * @param int    $post_id The post ID of the post to purchase tickets for.
	 * @param int    $timeout The timeout in seconds.
	 *
	 * @return string The URL to load the Seat Selection page.
	 */
	public function get_seat_selection_url( string $token, int $post_id, int $timeout = 15 * 60 ): string {
		$post_uuid = $this->get_post_uuid( $post_id );
		$layout_id = get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true );

		return add_query_arg(
			[
				'token'    => urlencode( $token ),
				'eventId'  => urlencode( $post_uuid ),
				'layoutId' => urlencode( $layout_id ),
				'timeout'  => $timeout,
			],
			$this->get_frontend_url( '/embed/purchasing/' )
		);
	}

	/**
	 * Returns the UUID of the post.
	 *
	 * If the post UUID is not set, it will be generated and set.
	 *
	 * @since 5.16.0
	 *
	 * @param int $event_id The post ID of the post.
	 *
	 * @return string The UUID of the post.
	 */
	public function get_post_uuid( int $event_id ): string {
		$post_uuid = get_post_meta( $event_id, Meta::META_KEY_UUID, true );

		if ( empty( $post_uuid ) ) {
			$post_uuid = wp_generate_uuid4();
			update_post_meta( $event_id, Meta::META_KEY_UUID, $post_uuid );
		}

		return $post_uuid;
	}

	/**
	 * Returns the seat types for given Layout ID.
	 *
	 * @since 5.16.0
	 *
	 * @param string $layout_id The layout ID to get the seat types for.
	 *
	 * @return array<array{id: string, name: string, seats: int}> The seat types in option format.
	 */
	public function get_seat_types_by_layout( string $layout_id ): array {
		return $this->seat_types->get_in_option_format( [ $layout_id ] );
	}

	/**
	 * Deletes a map.
	 *
	 * @since 5.16.0
	 *
	 * @param string $map_id The ID of the map.
	 *
	 * @return bool
	 */
	public function delete_map( string $map_id ): bool {
		return $this->maps->delete( $map_id );
	}

	/**
	 * Deletes a layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $layout_id The ID of the layout.
	 * @param string $map_id The map ID of the layout.
	 *
	 * @return bool
	 */
	public function delete_layout( string $layout_id, string $map_id ): bool {
		return $this->layouts->delete( $layout_id, $map_id );
	}

	/**
	 * Returns the seat report URL.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token   The ephemeral token used to secure the iframe communication with the service.
	 * @param int    $post_id The post ID of the post to get the seat report for.
	 *
	 * @return string The seat report URL.
	 */
	public function get_seat_report_url( string $token, int $post_id ): string {
		$query_args = [
			'token'   => urlencode( $token ),
			'eventId' => urlencode( $this->get_post_uuid( $post_id ) ),
		];

		$layout_id = get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true );

		if ( $layout_id ) {
			$query_args['layoutId'] = urlencode( $layout_id );
		}

		return add_query_arg(
			$query_args,
			$this->get_frontend_url( '/embed/seat-assignment/' )
		);
	}

	/**
	 * Returns the Service Status instance.
	 *
	 * @since 5.16.0
	 *
	 * @param bool $force Whether to force the rebuilding of the status for this request.
	 *
	 * @return Service_Status The Service Status instance.
	 */
	public function get_status( bool $force = false ): Service_Status {
		if ( $force || ! $this->status instanceof Service_Status ) {
			$context = is_admin() ? 'admin' : 'frontend';

			if ( defined( 'REST_REQUEST' ) ) {
				// This might be a REST request from the frontend or the editor. User logged in or not.
				$context = 'rest';
			}

			$this->status = new Service_Status( $this->backend_base_url, null, $context );
		}

		/**
		 * Filters the Service Status instance.
		 *
		 * @since 5.16.0
		 *
		 * @param Service_Status $status The Service Status instance.
		 * @param string         $base_url The base URL of the service.
		 */
		return apply_filters(
			'tec_tickets_seating_service_status',
			$this->status,
			$this->backend_base_url
		);
	}
}
