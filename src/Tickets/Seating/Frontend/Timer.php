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
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Built_Assets;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Service\Reservations;
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
	const COOKIE_NAME = 'tec-tickets-seating-session';

	/**
	 * The AJAX action used from the JS code to start the timer.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_START = 'tec_tickets_seating_timer_start';

	/**
	 * The AJAX action used from the JS code to sync the timer with the backend.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_SYNC = 'tec_tickets_seating_timer_sync';

	/**
	 * The AJAX action used from the JS code to get the data to render the redirection modal.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_INTERRUPT_GET_DATA = 'tec_tickets_seating_timer_interrupt_get_data';

	/**
	 * A reference to the template object.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * A reference to the Sessions table handler.
	 *
	 * @since TBD
	 *
	 * @var Sessions
	 */
	private Sessions $sessions;

	/**
	 * A reference to the Service object.
	 *
	 * @since TBD
	 *
	 * @var Reservations
	 */
	private Reservations $reservations;

	/**
	 * Whether the redirection modal was already rendered in the context of this request or not.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private $did_render_interruppt_modal = false;

	/**
	 * Timer constructor.
	 *
	 * @since TBD
	 *
	 * @param Container    $container    A reference to the container object.
	 * @param Template     $template     A reference to the template object.
	 * @param Sessions     $sessions     A reference to the Sessions table handler.
	 * @param Reservations $reservations A reference to the Reservations object.
	 */
	public function __construct(
		Container $container,
		Template $template,
		Sessions $sessions,
		Reservations $reservations
	) {
		parent::__construct( $container );
		$this->template     = $template;
		$this->sessions     = $sessions;
		$this->reservations = $reservations;
	}

	/**
	 * Renders the seat selection timer HTML.
	 *
	 * Note it's the JS code responsibility to start the timer by means of a request to the backend.
	 *
	 * @since TBD
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
			$cookie_timer_token_post_id = $this->get_session_token_object_id();

			if ( null === $cookie_timer_token_post_id ) {
				// The timer cannot be rendered.
				return;
			}

			[ $token, $post_id ] = $cookie_timer_token_post_id;
		}

		wp_enqueue_script( 'tec-tickets-seating-timer' );
		wp_enqueue_style( 'tec-tickets-seating-timer-style' );

		/** @noinspection UnusedFunctionResultInspection */
		$this->template->template(
			'seat-selection-timer',
			[
				'token'        => $token,
				'redirect_url' => get_post_permalink( $post_id ),
				'post_id'      => $post_id,
				'sync_on_load' => $sync_on_load,
			]
		);
	}

	/**
	 * Renders the seat selection timer HTML adding the attribute that will trigger its immediate
	 * synchronization with the backend.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_to_sync(): void {
		$this->render( null, null, true );
	}

	/**
	 * Returns the token and object ID couple with the earliest expiration time from the cookie.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $cookie_entries The entries from the cookie. A map from object ID to token.
	 *
	 * @return array{0: string, 1: int}|null The token and object ID from the cookie, or `null` if not found.
	 */
	public function pick_earliest_expiring_token_object_id( array $cookie_entries ): ?array {
		$tokens_interval_p     = implode( ',', array_fill( 0, count( $cookie_entries ), '%s' ) );
		$object_ids_interval_p = implode( ',', array_fill( 0, count( $cookie_entries ), '%d' ) );
		$tokens_interval       = DB::prepare( $tokens_interval_p, ...array_values( $cookie_entries ) );
		$object_ids_interval   = DB::prepare( $object_ids_interval_p, ...array_keys( $cookie_entries ) );
		$query                 = DB::prepare(
			"SELECT object_id, token FROM %i WHERE token IN ({$tokens_interval}) AND object_id IN ({$object_ids_interval}) ORDER BY expiration ASC LIMIT 1",
			Sessions::table_name()
		);

		$earliest = DB::get_row( $query );

		if ( ! $earliest ) {
			return null;
		}

		return [ $earliest->token, $earliest->object_id ];
	}

	/**
	 * Returns the token and object ID relevant to set up the timer from the cookie.
	 *
	 * This method will apply a default logic found in the `default_token_object_id_handler` method
	 * to pick the object ID with the earliest expiration time.
	 * Extensions can modify this logic by filtering the `tec_tickets_seating_timer_token_object_id_handler` filter.
	 *
	 * @since TBD
	 *
	 * @return array{0: string, 1: int}|null The token and object ID from the cookie, or `null` if not found.
	 */
	public function get_session_token_object_id(): ?array {
		$cookie = $_COOKIE[ self::COOKIE_NAME ] ?? null;

		if ( ! $cookie ) {
			return null;
		}

		$entries = $this->parse_cookie_string( $cookie );

		/**
		 * Filters the handler used to get the token and object ID from the cookie.
		 * The default handler will pick the object ID and token couple with the earliest expiration time.
		 *
		 * @since TBD
		 *
		 * @param callable             $handler The handler used to get the token and object ID from the cookie.
		 * @param array<string,string> $entries The entries from the cookie. A map from object ID to token.
		 */
		$handler = apply_filters(
			'tec_tickets_seating_timer_token_object_id_handler',
			[ $this, 'pick_earliest_expiring_token_object_id' ]
		);

		[ $token, $object_id ] = array_replace( [ '', '' ], (array) $handler( $entries ) );

		return $token && $object_id ? [ $token, $object_id ] : null;
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
		return apply_filters( 'tec_tickets_seating_selection_timeout', 15 * 60, $post_id );
	}

	/**
	 * Parses the cookie string into an array of object IDs and tokens.
	 *
	 * @since TBD
	 *
	 * @param string $current The current cookie string.
	 *
	 * @return array<string,string> The parsed cookie string, a map from object ID to token.
	 */
	private function parse_cookie_string( string $current ): array {
		$parsed = [];
		foreach ( explode( '|||', $current ) as $entry ) {
			[ $object_id, $token ] = array_replace( [ '', '' ], explode( '=', $entry, 2 ) );
			if ( empty( $object_id ) || empty( $token ) ) {
				continue;
			}
			$parsed[ $object_id ] = $token;
		}

		return $parsed;
	}

	/**
	 * Adds a new entry to the cookie string.
	 *
	 * @since TBD
	 *
	 * @param string $current   The current cookie string.
	 * @param int    $object_id The object ID that will become the new entry key.
	 * @param string $token     The token that will become the new entry value.
	 *
	 * @return string The updated cookie string.
	 */
	public function add_cookie_entry( string $current, int $object_id, string $token ): string {
		$entries               = $this->parse_cookie_string( $current );
		$entries[ $object_id ] = $token;

		return implode(
			'|||',
			array_map(
				static fn( $object_id, $token ) => $object_id . '=' . $token,
				array_keys( $entries ),
				$entries
			)
		);
	}

	/**
	 * Removes a cookie entry from the cookie string.
	 *
	 * @since TBD
	 *
	 * @param string $current The current cookie string.
	 * @param int    $post_id The post ID to remove the cookie entry for.
	 * @param string $token   The token to remove the cookie entry for.
	 *
	 * @return string The updated cookie string.
	 */
	private function remove_cookie_entry( string $current, int $post_id, string $token ): string {
		$entries = $this->parse_cookie_string( $current );

		if ( isset( $entries[ $post_id ] ) && $entries[ $post_id ] === $token ) {
			unset( $entries[ $post_id ] );
		}

		return implode(
			'|||',
			array_map(
				static fn( $object_id, $token ) => $object_id . '=' . $token,
				array_keys( $entries ),
				$entries
			)
		);
	}

	/**
	 * Unregisters the controller by unsubscribing from front-end hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_seating_seat_selection_timer', [ $this, 'render' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		remove_action( 'wp_ajax_' . self::ACTION_START, [ $this, 'ajax_start' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_START, [ $this, 'ajax_sync' ] );
		remove_action( 'wp_ajax_' . self::ACTION_START, [ $this, 'ajax_sync' ] );
		remove_action(
			'tribe_template_after_include:tickets/v2/commerce/checkout/cart/header',
			[ $this, 'render_to_sync' ]
		);
		remove_action( 'tribe_template_after_include:tickets-plus/v2/attendee-registration/content/event/summary/title',
			[ $this, 'render_to_sync' ],
		);
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_INTERRUPT_GET_DATA, [ $this, 'ajax_interrupt' ] );
		remove_action( 'wp_ajax_' . self::ACTION_INTERRUPT_GET_DATA, [ $this, 'ajax_interrupt' ] );
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
	 *     ACTION_REDIRECT: string,
	 *     ACTION_INTERRUPT_GET_DATA: string,
	 * } The data to be localized on the timer frontend.
	 */
	private function get_localized_data(): array {
		return [
			'ajaxUrl'                   => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'                 => wp_create_nonce( self::COOKIE_NAME ),
			'ACTION_START'              => self::ACTION_START,
			'ACTION_SYNC'               => self::ACTION_SYNC,
			'ACTION_INTERRUPT_GET_DATA' => self::ACTION_INTERRUPT_GET_DATA,
		];
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
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

		// Tickets Commerce checkout page: here the timer should be hydrated from the cookie, no arguments are needed.
		add_action(
			'tribe_template_after_include:tickets/v2/commerce/checkout/cart/header',
			[ $this, 'render_to_sync' ],
			10,
			0
		);

		// Attendee Registration page: here the timer should be hydrated from the cookie, no arguments are needed.
		add_action( 'tribe_template_after_include:tickets-plus/v2/attendee-registration/content/event/summary/title',
			[ $this, 'render_to_sync' ],
			10,
			0
		);

		Asset::add(
			'tec-tickets-seating-timer',
			$this->built_asset_url( 'frontend/timer.js' ),
			ET::VERSION
		)
		     ->set_dependencies(
			     'tribe-dialog-js',
			     'wp-hooks',
			     'wp-i18n',
			     'tec-tickets-seating-utils'
		     )
		     ->add_localize_script( 'tec.tickets.seating.frontend.timer', fn() => $this->get_localized_data() )
		     ->enqueue_on( 'tec_tickets_seating_seat_selection_timer' )
		     ->add_to_group( 'tec-tickets-seating-frontend' )
		     ->add_to_group( 'tec-tickets-seating' )
		     ->register();

		Asset::add(
			'tec-tickets-seating-timer-style',
			$this->built_asset_url( 'frontend/timer.css' ),
			ET::VERSION
		)
		     ->set_dependencies( 'tribe-dialog' )
		     ->enqueue_on( 'tec_tickets_seating_seat_selection_timer' )
		     ->add_to_group( 'tec-tickets-seating-frontend' )
		     ->add_to_group( 'tec-tickets-seating' )
		     ->register();
	}

	/**
	 * Checks the AJAX request parameters and returns them if they are valid.
	 *
	 * @since TBD
	 *
	 * @return array{0: string, 1: int}|false The token and post ID or `false` if the nonce verification failed.
	 */
	private function ajax_check_request() {
		if ( ! check_ajax_referer( self::COOKIE_NAME, '_ajaxNonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				401
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
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_start(): void {
		[ $token, $post_id ] = $this->ajax_check_request();

		$timeout = $this->get_timeout( $post_id );

		// When starting a new session, we need to remove the previous sessions for the same post.
		$this->cancel_previous_sessions( $post_id );

		// We're in the context of an XHR/AJAX request: the browser will set the cookie for us.
		$now          = microtime( true );
		$expiration   = (int) $now + $timeout;
		$cookie_value = $this->add_cookie_entry( $_COOKIE[ self::COOKIE_NAME ] ?? '', $post_id, $token );
		setcookie(
			self::COOKIE_NAME,
			$cookie_value,
			0, // Do not set the expiration here, there might be more than one element in the cookie.
			COOKIEPATH,
			COOKIE_DOMAIN,
			true,
			false
		);
		$_COOKIE[ self::COOKIE_NAME ] = $cookie_value;

		if ( ! $this->sessions->upsert( $token, $post_id, $expiration ) ) {
			wp_send_json_error(
				[
					'error' => 'Failed to start timer',
				],
				500
			);
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
	 * @since TBD
	 *
	 * @return void The AJAX response is sent back to the browser.
	 */
	public function ajax_sync(): void {
		[ $token, ] = $this->ajax_check_request();

		$seconds_left = $this->sessions->get_seconds_left( $token );

		wp_send_json_success(
			[
				'secondsLeft' => $seconds_left,
				'timestamp'   => microtime( true ),
			]
		);
	}

	/**
	 * Deletes the previous sessions reservations from the database.
	 *
	 * The token used for previous session reservations is read from the cookie.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The object ID to delete the sessions for.
	 *
	 * @return bool Whether the previous sessions were deleted or not.
	 */
	private function cancel_previous_sessions( int $object_id ): bool {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return true;
		}

		foreach ( $this->parse_cookie_string( $_COOKIE[ self::COOKIE_NAME ] ) as $entry_object_id => $entry_token ) {
			if ( $entry_object_id === $object_id ) {
				$reservations = $this->sessions->get_reservations_for_token( $entry_token );
				if ( ! $this->reservations->cancel( $entry_object_id, $reservations ) ) {
					return false;
				}

				return $this->sessions->delete_token_session( $entry_token );
			}
		}

		// Nothing to clear.
		return true;
	}

	/**
	 * Hnadles an AJAX requet to interrupt the user flow and clear the seat selection.
	 *
	 * @since TBD
	 *
	 * @return void  The AJAX response is sent back to the browser.
	 */
	public function ajax_interrupt(): void {
		if ( ! check_ajax_referer( self::COOKIE_NAME, '_ajaxNonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				401
			);
		}

		$post_id = tribe_get_request_var( 'postId', null );
		$token   = tribe_get_request_var( 'token', null );

		if ( ! ( $post_id && $token ) ) {
			wp_send_json_error(
				[
					'error' => 'Missing required parameters',
				],
				400
			);
		}

		$post_type_object      = get_post_type_object( get_post_type( $post_id ) );
		$has_tickets_available = tribe_tickets()->where( 'event', $post_id )->where( 'is_available', true )->count();

		if ( $has_tickets_available ) {
			$content      = _x( 'Your seat selections are no longer reserved, but tickets are still available.',
				'Seat selection expired timer content',
				'event-tickets' );
			$button_label = _x( 'Find Seats', 'Seat selection expired timer button label', 'event-tickets' );
			$redirect_url = get_post_permalink( $post_id );
		} else {
			if ( $post_type_object ) {
				$post_type_label = strtolower( get_post_type_labels( $post_type_object )['singular_name'] ) ?? 'event';
			} else {
				$post_type_label = 'event';
			}

			$content = sprintf(
			// Translators: %s: The post type singular name.
				_x( 'This %s is now sold out.', 'Seat selection expired timer content', 'event-tickets' ),
				$post_type_label
			);

			$button_label = sprintf(
			// Translators: %s: The post type singular name.
				_x( 'Find another %s', 'Seat selection expired timer button label', 'event-tickets' ),
				$post_type_label
			);

			$redirect_url = get_post_type_archive_link( $post_type_object->name );
		}

		$cookie_value = $this->remove_cookie_entry( $_COOKIE[ self::COOKIE_NAME ] ?? '', $post_id, $token );
		setcookie(
			self::COOKIE_NAME,
			$cookie_value,
			0, // Do not set the expiration here, there might be more than one element in the cookie.
			COOKIEPATH,
			COOKIE_DOMAIN,
			true,
			false
		);
		$_COOKIE[ self::COOKIE_NAME ] = $cookie_value;

		/**
		 * Fires when a seat selection session is interrupted due to the timer expiring or the seat selection session
		 * being otherwise interrupted.
		 *
		 * @since TBD
		 *
		 * @param int    $post_id The post ID the session is being interrupted for.
		 * @param string $token   The ephemeral token the session is being interrupted for.
		 */
		do_action( 'tec_tickets_seating_session_interrupt', $post_id, $token );

		wp_send_json_success( [
			'title'       => esc_html_x( 'Time limit expired', 'Seat selection expired timer title', 'event-tickets' ),
			'content'     => esc_html( $content ),
			'buttonLabel' => esc_html( $button_label ),
			'redirectUrl' => esc_url( $redirect_url ),
		] );
	}
}
