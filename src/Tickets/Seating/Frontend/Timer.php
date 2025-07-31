<?php
/**
 * The Seating feature frontend timer handler.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Frontend;
 */

namespace TEC\Tickets\Seating\Frontend;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\Asset;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Settings;
use TEC\Tickets\Seating\Tables\Sessions;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Template;
use Tribe__Tickets__Main as ET;

/**
 * Class Cookie.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Frontend;
 */
class Timer extends Controller_Contract {
	/**
	 * The AJAX action used from the JS code to start the timer.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	const ACTION_START = 'tec_tickets_seating_timer_start';

	/**
	 * The AJAX action used from the JS code to sync the timer with the backend.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	const ACTION_SYNC = 'tec_tickets_seating_timer_sync';

	/**
	 * The AJAX action used from the JS code to get the data to render the redirection modal.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	const ACTION_INTERRUPT_GET_DATA = 'tec_tickets_seating_timer_interrupt_get_data';

	/**
	 * The AJAX action used from the JS code to signal the timer should pause to allow the user to checkout.
	 *
	 * @since 5.17.0
	 *
	 * @var string
	 */
	const ACTION_PAUSE_TO_CHECKOUT = 'tec_tickets_seating_timer_pause_to_checkout';

	/**
	 * A reference to the template object.
	 *
	 * @since 5.16.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * A reference to the Sessions table handler.
	 *
	 * @since 5.16.0
	 *
	 * @var Sessions
	 */
	private Sessions $sessions;

	/**
	 * A reference to the Service object.
	 *
	 * @since 5.16.0
	 *
	 * @var Reservations
	 */
	private Reservations $reservations;

	/**
	 * A reference to the Session object.
	 *
	 * @since 5.16.0
	 *
	 * @var Session
	 */
	private Session $session;

	/**
	 * A reference to the Frontend object.
	 *
	 * @since 5.16.0
	 *
	 * @var Frontend
	 */
	private Frontend $frontend;

	/**
	 * The current token used to render the timer.
	 * Set on explicit render requests.
	 *
	 * @since 5.16.0
	 *
	 * @var string|null
	 */
	private ?string $current_token = null;

	/**
	 * The current post ID used to render the timer.
	 * Set on explicit render requests.
	 *
	 * @since 5.16.0
	 *
	 * @var int|null
	 */
	private ?int $current_post_id = null;

	/**
	 * Timer constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param Container    $container    A reference to the container object.
	 * @param Template     $template     A reference to the template object.
	 * @param Sessions     $sessions     A reference to the Sessions table handler.
	 * @param Reservations $reservations A reference to the Reservations object.
	 * @param Session      $session      A reference to the Session object.
	 * @param Frontend     $frontend     A reference to the Frontend object.
	 */
	public function __construct(
		Container $container,
		Template $template,
		Sessions $sessions,
		Reservations $reservations,
		Session $session,
		Frontend $frontend
	) {
		parent::__construct( $container );
		$this->template     = $template;
		$this->sessions     = $sessions;
		$this->reservations = $reservations;
		$this->session      = $session;
		$this->frontend     = $frontend;
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_seating_seat_selection_timer', [ $this, 'render' ], 10, 2 );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		add_action( 'wp_ajax_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_SYNC, [ $this, 'ajax_sync' ] );
		add_action( 'wp_ajax_' . self::ACTION_SYNC, [ $this, 'ajax_sync' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_INTERRUPT_GET_DATA, [ $this, 'ajax_interrupt' ] );
		add_action( 'wp_ajax_' . self::ACTION_INTERRUPT_GET_DATA, [ $this, 'ajax_interrupt' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_PAUSE_TO_CHECKOUT, [ $this, 'ajax_pause_to_checkout' ] );
		add_action( 'wp_ajax_' . self::ACTION_PAUSE_TO_CHECKOUT, [ $this, 'ajax_pause_to_checkout' ] );

		// Tickets Commerce checkout page: here the timer should be hydrated from the cookie, no arguments are needed.
		add_action(
			'tribe_template_after_include:tickets/v2/commerce/checkout/cart/header',
			[ $this, 'render_to_sync' ],
			10,
			0
		);

		// Attendee Registration page: here the timer should be hydrated from the cookie, no arguments are needed.
		add_action(
			'tribe_template_after_include:tickets-plus/v2/attendee-registration/content/event/summary/title',
			[ $this, 'render_to_sync' ],
			10,
			0
		);

		// Attendee Registration modal: here the timer should be hydrated from the cookie, no arguments are needed.
		add_action(
			'tribe_template_before_include:tickets-plus/v2/modal/cart',
			[ $this, 'render_to_sync' ],
			10,
			0
		);

		Asset::add(
			'tec-tickets-seating-session',
			'frontend/session.js',
			ET::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->set_dependencies(
				'tribe-dialog-js',
				'wp-hooks',
				'wp-i18n',
				'tec-tickets-seating-utils'
			)
			->add_localize_script( 'tec.tickets.seating.frontend.sessionData', fn() => $this->get_localized_data() )
			->enqueue_on( 'tec_tickets_seating_seat_selection_timer' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-session-style',
			'frontend/style-session.css',
			ET::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->set_dependencies( 'tribe-dialog' )
			->enqueue_on( 'tec_tickets_seating_seat_selection_timer' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Unregisters the controller by unsubscribing from front-end hooks.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_seating_seat_selection_timer', [ $this, 'render' ], );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		remove_action( 'wp_ajax_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_SYNC, [ $this, 'ajax_sync' ] );
		remove_action( 'wp_ajax_' . self::ACTION_SYNC, [ $this, 'ajax_sync' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_INTERRUPT_GET_DATA, [ $this, 'ajax_interrupt' ] );
		remove_action( 'wp_ajax_' . self::ACTION_INTERRUPT_GET_DATA, [ $this, 'ajax_interrupt' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_PAUSE_TO_CHECKOUT, [ $this, 'ajax_pause_to_checkout' ] );
		remove_action( 'wp_ajax_' . self::ACTION_PAUSE_TO_CHECKOUT, [ $this, 'ajax_pause_to_checkout' ] );

		// Tickets Commerce checkout page: here the timer should be hydrated from the cookie, no arguments are needed.
		remove_action(
			'tribe_template_after_include:tickets/v2/commerce/checkout/cart/header',
			[ $this, 'render_to_sync' ]
		);

		// Attendee Registration page: here the timer should be hydrated from the cookie, no arguments are needed.
		remove_action(
			'tribe_template_after_include:tickets-plus/v2/attendee-registration/content/event/summary/title',
			[ $this, 'render_to_sync' ]
		);

		// Attendee Registration modal: here the timer should be hydrated from the cookie, no arguments are needed.
		remove_action(
			'tribe_template_before_include:tickets-plus/v2/modal/cart',
			[ $this, 'render_to_sync' ]
		);
	}

	/**
	 * Renders the seat selection timer HTML.
	 *
	 * Note it's the JS code responsibility to start the timer by means of a request to the backend.
	 *
	 * @since 5.16.0
	 *
	 * @param string|null $token        The ephemeral token used to secure the iframe communication with the service
	 *                                  and identify the seat selection session.
	 * @param int|null    $post_id      The ID of the post to purchase tickets for.
	 * @param bool        $sync_on_load Whether to sync the timer with the backend on DOM ready or not, defaults to
	 *                                  `false`.
	 *
	 * @return void The seat selection timer HTML is rendered.
	 */
	public function render( string $token = null, int $post_id = null, bool $sync_on_load = false ): void {
		if ( ! ( $token && $post_id ) ) {
			// Token and post ID did not come from the action, pull them from the cookie, if possible.
			$cookie_timer_token_post_id = $this->session->get_session_token_object_id();

			if ( null === $cookie_timer_token_post_id ) {
				// The timer cannot be rendered.
				return;
			}

			[ $token, $post_id ] = $cookie_timer_token_post_id;
		} else {
			if ( ! tec_tickets_seating_enabled( $post_id ) ) {
				// The post is not using assigned seating, do not render the timer.
				return;
			}

			// If a cookie and token were passed, store them for later use.
			$this->current_token   = $token;
			$this->current_post_id = $post_id;
		}

		if ( ! tec_tickets_seating_enabled( $post_id ) ) {
			// The post is not using assigned seating, do not render the timer.
			return;
		}

		wp_enqueue_script( 'tec-tickets-seating-session' );
		wp_enqueue_style( 'tec-tickets-seating-session-style' );

		/** @noinspection UnusedFunctionResultInspection */
		$this->template->template(
			'seat-selection-timer',
			[
				'token'        => $token,
				'redirect_url' => get_permalink( $post_id ),
				'post_id'      => $post_id,
				'sync_on_load' => $sync_on_load,
			]
		);
	}

	/**
	 * Renders the seat selection timer HTML adding the attribute that will trigger its immediate
	 * synchronization with the backend.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function render_to_sync(): void {
		$this->render( $this->current_token, $this->current_post_id, true );
	}

	/**
	 * Returns the seat-selection timeout for a post in seconds.
	 *
	 * @since 5.16.0
	 *
	 * @param int $post_id The post ID the iframe is for.
	 *
	 * @return int The seat-selection timeout for a post in seconds.
	 */
	public function get_timeout( $post_id ): int {
		$limit_in_minutes = tribe( Settings::class )->get_reservation_time_limit();

		/**
		 * Filters the seat selection timeout, default is 15 minutes.
		 *
		 * @since 5.16.0
		 *
		 * @param int $timeout The timeout in seconds.
		 * @param int $post_id The post ID the iframe is for.
		 */
		return apply_filters( 'tec_tickets_seating_selection_timeout', $limit_in_minutes * 60, $post_id );
	}

	/**
	 * Returns the data to be localized on the timer frontend.
	 *
	 * @since 5.16.0
	 *
	 * @return array{
	 *     ajaxUrl: string,
	 *     ajaxNonce: string,
	 *     checkoutGraceTime: int,
	 *     ACTION_START: string,
	 *     ACTION_TIME_LEFT: string,
	 *     ACTION_REDIRECT: string,
	 *     ACTION_INTERRUPT_GET_DATA: string,
	 *     ACTION_PAUSE_TO_CHECKOUT: string,
	 * } The data to be localized on the timer frontend.
	 */
	public function get_localized_data(): array {
		return [
			'ajaxUrl'                   => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'                 => wp_create_nonce( Session::COOKIE_NAME ),
			'checkoutGraceTime'         => $this->get_checkout_grace_time(),
			'ACTION_START'              => self::ACTION_START,
			'ACTION_SYNC'               => self::ACTION_SYNC,
			'ACTION_INTERRUPT_GET_DATA' => self::ACTION_INTERRUPT_GET_DATA,
			'ACTION_PAUSE_TO_CHECKOUT'  => self::ACTION_PAUSE_TO_CHECKOUT,
		];
	}

	/**
	 * Checks the AJAX request parameters and returns them if they are valid.
	 *
	 * @since 5.16.0
	 *
	 * @return array{0: string, 1: int}|false The token and post ID or `false` if the nonce verification failed.
	 */
	private function ajax_check_request() {
		if ( ! check_ajax_referer( Session::COOKIE_NAME, '_ajaxNonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			// This will never be reached, but we need to return something.
			return false;
		}

		$token   = tribe_get_request_var( 'token', null );
		$post_id = tribe_get_request_var( 'postId', null );

		if ( ! ( $token && $post_id ) ) {
			wp_send_json_error(
				[
					'error' => 'Missing required parameters',
				],
				400
			);

			// This will never be reached, but we need to return something.
			return false;
		}

		return [ $token, $post_id ];
	}

	/**
	 * Handles the AJAX request to start the timer.
	 *
	 * This request will create a new session in the database and will return the number of seconds left in the timer.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function ajax_start(): void {
		$token_and_post_id = $this->ajax_check_request();

		if ( ! $token_and_post_id ) {
			return;
		}

		[ $token, $post_id ] = $token_and_post_id;

		$timeout = $this->get_timeout( $post_id );

		// When starting a new session, we need to remove the previous sessions for the same post.
		$this->session->cancel_previous_for_object( $post_id, $token );

		// We're in the context of an XHR/AJAX request: the browser will set the cookie for us.
		$now        = microtime( true );
		$expiration = (int) $now + $timeout;
		$this->session->add_entry( $post_id, $token );

		if ( ! $this->sessions->insert_or_update( $token, $post_id, $expiration ) ) {
			wp_send_json_error(
				[
					'error' => 'Failed to start timer',
				],
				500
			);

			return;
		}

		wp_send_json_success(
			[
				'secondsLeft' => $timeout,
				'timestamp'   => $now,
			]
		);
	}

	/**
	 * Handles the AJAX request to sync the timer with the backend.
	 *
	 * This request will read the number of seconds left in the timer from the database to allow the
	 * frontend to update the timer with a synced value.
	 *
	 * @since 5.16.0
	 *
	 * @return void The AJAX response is sent back to the browser.
	 */
	public function ajax_sync(): void {
		[ $token, $post_id ] = $this->ajax_check_request();

		$has_tickets_available = $this->frontend->get_events_ticket_capacity_for_seating( $post_id );

		// If no tickets are available, the users should be interrupted.
		$seconds_left = $has_tickets_available ? $this->sessions->get_seconds_left( $token ) : 0;

		wp_send_json_success(
			[
				'secondsLeft' => $seconds_left,
				'timestamp'   => microtime( true ),
			]
		);
	}

	/**
	 * Handles an AJAX request to interrupt the user flow and clear the seat selection.
	 *
	 * @since 5.16.0
	 *
	 * @return void  The AJAX response is sent back to the browser.
	 */
	public function ajax_interrupt(): void {
		$token_and_post_id = $this->ajax_check_request();

		if ( ! $token_and_post_id ) {
			return;
		}

		[ $token, $post_id ] = $token_and_post_id;


		$post_type             = get_post_type( $post_id );
		$post_type_object      = get_post_type_object( $post_type );
		$has_tickets_available = $this->frontend->get_events_ticket_capacity_for_seating( $post_id );

		if ( $has_tickets_available ) {
			$content      = _x(
				'Your seat selections are no longer reserved, but tickets are still available.',
				'Seat selection expired timer content',
				'event-tickets'
			);
			$button_label = _x( 'Find Seats', 'Seat selection expired timer button label', 'event-tickets' );
			$redirect_url = get_permalink( $post_id );
		} else {
			if ( $post_type_object ) {
				$post_type_label = strtolower( get_post_type_labels( $post_type_object )->singular_name ) ?? 'event';
			} else {
				$post_type_label = 'event';
			}

			$content = sprintf(
			// Translators: %s: The post type singular name.
				_x( 'This %s is now sold out.', 'Seat selection expired timer content', 'event-tickets' ),
				$post_type_label
			);

			if ( 'tribe_events' === $post_type ) {
				$button_label = sprintf(
				// Translators: %s: The post type singular name.
					_x( 'Find Another %s', 'Seat selection expired timer button label', 'event-tickets' ),
					ucfirst( $post_type_label )
				);

				$redirect_url = get_post_type_archive_link( $post_type );
			} else {
				$button_label = _x( 'Return to Home Page', 'Seat selection expired timer button label', 'event-tickets' );
				$redirect_url = get_home_url();
			}
		}

		/**
		 * Fires when a seat selection session is interrupted due to the timer expiring or the seat selection session
		 * being otherwise interrupted.
		 *
		 * @since 5.16.0
		 *
		 * @param int    $post_id The post ID the session is being interrupted for.
		 * @param string $token   The ephemeral token the session is being interrupted for.
		 */
		do_action( 'tec_tickets_seating_session_interrupt', $post_id, $token );

		// Remove the seat selection session cookie entry.
		$this->session->remove_entry( $post_id, $token );

		// Cancel the reservations for the post ID and token, remove the session associated with the token from the database.
		if ( ! (
			$this->reservations->cancel( $post_id, $this->sessions->get_reservation_uuids_for_token( $token ) )
			&& $this->sessions->delete_token_session( $token )
		) ) {
			wp_send_json_error(
				[
					'error' => 'Failed to cancel the reservations',
				],
				500
			);

			return;
		}

		// Check whether the session was interrupted due to the timer expiring or the tickets being sold out.
		$interrupt_title = ! $has_tickets_available ? _x( 'Sold Out', 'Seat selection sold out timer title', 'event-tickets' ) : _x( 'Time limit expired', 'Seat selection expired timer title', 'event-tickets' );

		$data = [
			'title'       => esc_html( $interrupt_title ),
			'content'     => esc_html( $content ),
			'buttonLabel' => esc_html( $button_label ),
			'redirectUrl' => esc_url( $redirect_url ),
		];

		/**
		 * Filters the seat selection expired timer data.
		 *
		 * @since 5.16.0
		 *
		 * @param array<string,string>   $data The seat selection expired timer data.
		 * @param int                    $post_id The post ID the session is being interrupted for.
		 * @param string                 $token   The ephemeral token the session is being interrupted for.
		 */
		$data = apply_filters( 'tec_tickets_seat_selection_timer_expired_data', $data, $post_id, $token );

		wp_send_json_success( $data );
	}

	/**
	 * Returns the current token set from a previous render in the context of this request.
	 *
	 * @since 5.16.0
	 *
	 * @return string|null The current token, or `null` if not set.
	 */
	public function get_current_token(): ?string {
		return $this->current_token;
	}

	/**
	 * Returns the current post ID set from a previous render in the context of this request.
	 *
	 * @since 5.16.0
	 *
	 * @return int|null The current post ID, or `null` if not set.
	 */
	public function get_current_post_id(): ?int {
		return $this->current_post_id;
	}

	/**
	 * Returns the filtered checkout grace time given to a user to complete the checkout process.
	 *
	 * @since 5.17.0
	 *
	 * @return int The filtered checkout grace time.
	 */
	public function get_checkout_grace_time(): int {
		/**
		 * Filters the grace time given to a user to complete the checkout process.
		 *
		 * @since 5.17.0
		 *
		 * @param int $grace_time The grace time allowed to a user to complete the checkout process.
		 */
		return (int) apply_filters( 'tec_tickets_seating_checkout_grace_time', 60 );
	}

	/**
	 * Handles the action from the backend signaling the user is checking out.
	 *
	 * @since 5.17.0
	 *
	 * @return void  The AJAX response is sent back to the browser.
	 */
	public function ajax_pause_to_checkout(): void {
		[ $token, $post_id ] = $this->ajax_check_request();

		$has_tickets_available = $this->frontend->get_events_ticket_capacity_for_seating( $post_id );

		if ( ! $has_tickets_available ) {
			wp_send_json_success(
				[
					'secondsLeft' => 0,
					'timestamp'   => microtime( true ),
				]
			);

			return;
		}

		// From this moment, give the user about 60 seconds to complete the checkout flow.
		$grace_time         = $this->get_checkout_grace_time();
		$updated_expiration = $this->sessions->set_token_expiration_timestamp( $token, time() + $grace_time, true );

		// If no tickets are available or the timestamp expiration update failed, the users should be interrupted.
		$seconds_left = $updated_expiration !== false ? $this->sessions->get_seconds_left( $token ) : 0;

		wp_send_json_success(
			[
				'secondsLeft' => $seconds_left,
				'timestamp'   => microtime( true ),
			]
		);
	}
}
