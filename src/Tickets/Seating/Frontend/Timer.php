<?php
/**
 * The Seating feature frontend timer handler.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Frontend;
 */

namespace TEC\Tickets\Seating\Frontend;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Seating\Built_Assets;
use TEC\Tickets\Seating\Tables\Sessions;
use TEC\Tickets\Seating\Template;
use Tribe__Tickets__Main as ET;

/**
 * Class Cookie.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Frontend;
 */
class Timer extends Controller_Contract {
	use Built_Assets;

	/**
	 * The cookie name used to store the ephemeral token.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'tec-tickets-seating-timer';

	/**
	 * The AJAX action used from the JS code to start the timer.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_START = 'tec_tickets_seating_timer_start';

	/**
	 * The AJAX action used from the JS code to get the time left in the timer.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_TIME_LEFT = 'tec_tickets_seating_timer_time_left';

	/**
	 * The AJAX action used from the JS code to redirect to the purchase page.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_REDIRECT = 'tec_tickets_seating_timer_redirect';

	/**
	 * A reference to the template object.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Timer constructor.
	 *
	 * since TBD
	 *
	 * @param Container $container A reference to the container object.
	 * @param Template  $template  A reference to the template object.
	 */
	public function __construct( Container $container, Template $template ) {
		parent::__construct( $container );
		$this->template = $template;
	}

	/**
	 * Removes the ephemeral token cookie.
	 *
	 * @since TBD
	 *
	 * @return void The ephemeral token cookie is removed.
	 */
	public function remove_timer_cookie(): void {
		setcookie( self::COOKIE_NAME,
			'',
			time() - 3600,
			COOKIEPATH,
			COOKIE_DOMAIN,
			true,
			false
		);
		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	/**
	 * Renders the seat selection timer HTML.
	 *
	 * Note it's the JS code responsibility to start the timer by means of a request to the backend.
	 *
	 * @since TBD
	 *
	 * @param string|null $token   The ephemeral token used to secure the iframe communication with the service
	 *                             and identify the seat selection session.
	 * @param int|null    $post_id The ID of the post to purchase tickets for.
	 *
	 * @return void The seat selection timer HTML is rendered.
	 */
	public function render( string $token = null, int $post_id = null ): void {
		if ( ! ( $token && $post_id ) ) {
			// Token and post ID did not come from the action, pull them from the cookie, if possible.
			$cookie_timer_token_post_id = $this->get_timer_token_and_post_id();

			if ( $cookie_timer_token_post_id === null ) {
				// The timer cannot be rendered.
				return;
			}

			[ $token, $post_id ] = $cookie_timer_token_post_id;
		}

		$this->template->template( 'seat-selection-timer', [
			'token'        => $token,
			'redirect_url' => get_post_permalink( $post_id ),
			'post_id'      => $post_id,
		] );
	}

	/**
	 * Returns the token and post ID from the cookie.
	 *
	 * @since TBD
	 *
	 * @return array{0: string, 1: int}|null The token and post ID from the cookie, or `null` if not found.
	 */
	public function get_timer_token_and_post_id(): ?array {
		$cookie = $_COOKIE[ self::COOKIE_NAME ] ?? null;

		if ( ! $cookie ) {
			return null;
		}

		[ $token, $post_id ] = array_replace( [ '', '' ], explode( '|||', $cookie, 2 ) );

		return $token && $post_id ? [ $token, $post_id ] : null;
	}

	public function ajax_start(): void {
		if ( ! check_ajax_referer( self::COOKIE_NAME, '_ajaxNonce', false ) ) {
			wp_send_json_error( [
				'error' => 'Nonce verification failed',
			], 401 );
		}

		$token   = tribe_get_request_var( 'token', null );
		$post_id = tribe_get_request_var( 'post_id', null );

		if ( ! ( $token && $post_id ) ) {
			wp_send_json_error( [
				'error' => 'Missing required parameters',
			], 400 );
		}

		$timeout = $this->get_timeout( $post_id );

		// We're in the context of an XHR request, but modern browsers should set cookies for us.
		$now        = microtime( true );
		$expiration = (int) $now + $timeout;
		setcookie( self::COOKIE_NAME,
			$this->format_timer_cookie_string( $token, $post_id ),
			$expiration,
			COOKIEPATH,
			COOKIE_DOMAIN,
			true,
			false );
		$_COOKIE[ self::COOKIE_NAME ] = $this->format_timer_cookie_string( $token, $post_id );

		if ( ! Sessions::upsert( $token, $post_id, $expiration ) ) {
			wp_send_json_error( [
				'error' => 'Failed to start timer',
			], 500 );
		}

		wp_send_json_success( [
			'secondsLeft' => $timeout,
			'timestamp'   => $now,
		] );
	}

	/**
	 * Returns the seat-selection timeout for a post in seconds.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID the iframe is for.
	 *
	 * @return int The seat-selection timeout for a post in seconds.
	 */
	public function get_timeout( $post_id ): int {
		/**
		 * Filters the seat selection timeout, default is 15 minutes.
		 *
		 * @since TBD
		 *
		 * @param int $timeout The timeout in seconds.
		 * @param int $post_id The post ID the iframe is for.
		 */
		$timeout = apply_filters( 'tec_tickets_seating_selection_timeout', 15 * 60, $post_id );

		return $timeout;
	}

	/**
	 * Formats the timer cookie string.
	 *
	 * @since TBD
	 *
	 * @param string $token   The ephemeral token.
	 * @param int    $post_id The post ID.
	 *
	 * @return string The formatted timer cookie string.
	 */
	public function format_timer_cookie_string( string $token, int $post_id ): string {
		return $token . '|||' . $post_id;
	}

	/**
	 * Unregisters the controller by unsubscribing from front-end hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_seating_seat_selection_timer', [ $this, 'render_seat_selection_timer' ], );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		remove_action( 'wp_ajax_' . self::ACTION_START, [ $this, 'ajax_start' ] );
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_seating_seat_selection_timer', [ $this, 'render_seat_selection_timer' ], 10, 2 );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		add_action( 'wp_ajax_' . self::ACTION_START, [ $this, 'ajax_start' ] );

		Asset::add(
			'tec-tickets-seating-timer',
			$this->built_asset_url( 'frontend/timer.js' ),
			ET::VERSION
		)
		     ->set_dependencies(
			     'tribe-dialog-js',
		     )
		     ->add_localize_script( 'tec.tickets.seating.frontend.timer',
			     fn() => $this->get_localized_data() )
		     ->enqueue_on( 'tec_tickets_seating_seat_selection_timer' )
		     ->add_to_group( 'tec-tickets-seating-frontend' )
		     ->add_to_group( 'tec-tickets-seating' )
		     ->register();

		Asset::add(
			'tec-tickets-seating-timer-style',
			$this->built_asset_url( 'frontend/timer.css' ),
			ET::VERSION
		)
		     ->enqueue_on( 'tec_tickets_seating_seat_selection_timer' )
		     ->add_to_group( 'tec-tickets-seating-frontend' )
		     ->add_to_group( 'tec-tickets-seating' )
		     ->register();
	}

	/**
	 * Returns the data to be localized on the timer frontend.
	 *
	 * @since TBD
	 *
	 * @return array{
	 *     ajaxUrl: string,
	 *     ajaxNonce: string,
	 *     ACTION_START: string,
	 *     ACTION_TIME_LEFT: string,
	 *     ACTION_REDIRECT: string
	 * } The data to be localized on the timer frontend.
	 */
	private function get_localized_data(): array {
		return [
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'        => wp_create_nonce( Timer::COOKIE_NAME ),
			'ACTION_START'     => Timer::ACTION_START,
			'ACTION_TIME_LEFT' => Timer::ACTION_TIME_LEFT,
			'ACTION_REDIRECT'  => Timer::ACTION_REDIRECT,
		];
	}
}
