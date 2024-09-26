<?php
/**
 * Controller for registering and serving Site Health tests for the Seating functionality.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Seating\Service\Service;
use function TEC\Common\StellarWP\Uplink\get_resource;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */
class Health extends Controller_Contract {
	/**
	 * The tests to be registered with the Site Health component.
	 *
	 * @var array
	 */
	protected array $tests = [];

	/**
	 * The rate at which AJAX requests should be served.
	 *
	 * The value is in microseconds. 1 microsecond is 1 millionth of a second.
	 *
	 * 5000 microseconds is 5 milliseconds or 0.005 seconds and that's equivalent to 200 requests per second.
	 *
	 * @var int
	 */
	protected const AJAX_RATE = 5000;

	/**
	 * The amount of AJAX requests that should be served before considering the test successful.
	 *
	 * @var int
	 */
	protected const AJAX_AMOUNT_OF_TESTS = 30;

	/**
	 * Controller constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container A reference to the container object.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->define_tests();
	}

	/**
	 * Defines the tests to be registered with the Site Health component.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function define_tests() {
		$this->tests = [
			'slr_valid_license' => [
				'label'     => __( 'Has valid Seating license', 'event-tickets' ),
				'test'      => 'slr-valid-license',
				'completed' => false,
				'extra'     => [
					'success' => [
						'description' => __( 'Your Seating license is valid!', 'event-tickets' ),
						'actions'     => [
							// Translators: %1$s and %2$s are opening and closing tags respectively for HTML p and a elements.
							_x( '%1$sLearn more about Seating%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health is successful.', 'event-tickets' ),
							[
								// Learn more about Seating link.
								'<p><a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								'</a></p>',
							],
						],
					],
					'failure' => [
						'description' => __( 'Your Seating license is invalid! This is a critical requirement for the Seating functionality to work as expected!', 'event-tickets' ),
						'actions'     => [
							// Translators: %1$s Opening p element, %2$s closing a and p elements, %3$s %4$s and %5$s opening a elements.
							_x( '%1$sIf you don\'t have a license, you can purchase one %3$shere.%2$s%1$sIf you do have a license, make sure it is active for this site by visiting your %4$saccount.%2$s%1$sFinally, please try authorizing %5$sagain.%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health has failed.', 'event-tickets' ),
							[
								'<p>',
								'</a></p>',
								// Purchase Seating license link.
								'<a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								// Visit my account - Seating section link.
								'<a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								// Authorize Seating link.
								'<a href="">',
							],
						],
					],
				],
			],
			'slr_can_see_sass'  => [
				'label'     => __( 'Can communicate with the Seating App', 'event-tickets' ),
				'test'      => 'slr-can-see-sass',
				'completed' => false,
				'extra'     => [
					'success' => [
						'description' => __( 'Your site can communicate with the Seating App!', 'event-tickets' ),
						'actions'     => [
							// Translators: %1$s and %2$s are opening and closing tags respectively for HTML p and a elements.
							_x( '%1$sLearn more about Seating%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health is successful.', 'event-tickets' ),
							[
								// Learn more about Seating link.
								'<p><a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								'</a></p>',
							],
						],
					],
					'failure' => [
						'description' => __( 'Your site cannot communicate with the Seating App! This is a critical requirement for the Seating functionality to work as expected!', 'event-tickets' ),
						'actions'     => [
							// Translators: %1$s Opening p element, %2$s closing p element, %3$s and %4$s opening a elements, %5$s closing a element.
							_x( '%1$sEnsure the Seating App is online and health by checking the %3$sstatus page.%5$s%2$s%1$s%4$sGet help%5$s resolving this issue.%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health has failed.', 'event-tickets' ),
							[
								'<p>',
								'</p>',
								// Seating Status page link.
								'<a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								// Support link.
								'<a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								// Authorize Seating link.
								'</a>',
							],
						],
					],
				],
			],
			'slr_ajax_rate'     => [
				'label'     => __( 'Can serve AJAX requests in the rate required by the Seating functionality.', 'event-tickets' ),
				'test'      => 'slr-ajax-rate',
				'completed' => false,
				'extra'     => [
					'success' => [
						'description' => __( 'Your site can serve AJAX requests in the rate required by the Seating functionality!', 'event-tickets' ),
						'actions'     => [
							// Translators: %1$s and %2$s are opening and closing tags respectively for HTML p and a elements.
							_x( '%1$sLearn more about Seating%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health is successful.', 'event-tickets' ),
							[
								// Learn more about Seating link.
								'<p><a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								'</a></p>',
							],
						],
					],
					'failure' => [
						'description' => __( 'Your site cannot serve AJAX requests in the rate required for the Seating functionality to work as expected! ! This is a critical requirement.', 'event-tickets' ),
						'actions'     => [
							// Translators: %1$s Opening p element, %2$s closing p element, %3$s and %5$s opening a elements, %4$s closing a element.
							_x( '%1$sDo you have any security plugins that could be enforcing a rate limit?%2$s%1$sAsk your hosting provider why your site is not handling AJAX requests in the rate of %3$d requests per second.%2$s%1$s%4$sRead more%5$s or %6$sget help%5$s resolving this issue.%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health has failed.', 'event-tickets' ),
							[
								'<p>',
								'</p>',
								(int) ceil( 1000000 / self::AJAX_RATE ),
								// Read more about AJAX rate link.
								'<a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
								'</a>',
								// Support link.
								'<a href="https://theeventscalendar.com/product/seating/" target="_blank" rel="noopener noreferrer">',
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Returns the tests registered with the controller.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_tests(): array {
		return $this->tests;
	}

	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'site_status_tests', [ $this, 'add_site_status_tests' ] );

		foreach ( $this->get_tests() as $callback => $test ) {
			remove_action( 'wp_ajax_health-check-' . $test['test'], [ $this, 'check_' . $callback ] );
		}

		remove_action( 'wp_ajax_tec-site-health-test-' . $this->get_tests()['slr_ajax_rate']['test'], [ $this, 'test_ajax_rate' ] );
	}

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'site_status_tests', [ $this, 'add_site_status_tests' ] );

		foreach ( $this->get_tests() as $callback => $test ) {
			add_action( 'wp_ajax_health-check-' . $test['test'], [ $this, 'check_' . $callback ] );
		}

		add_action( 'wp_ajax_tec-site-health-test-' . $this->get_tests()['slr_ajax_rate']['test'], [ $this, 'test_ajax_rate' ] );
	}

	/**
	 * Adds the Seating tests to the Site Health component.
	 *
	 * @since TBD
	 *
	 * @param array $tests The tests to be added to the Site Health component.
	 *
	 * @return array
	 */
	public function add_site_status_tests( array $tests ): array {
		if ( ! isset( $tests['async'] ) || ! is_array( $tests['async'] ) ) {
			$tests['async'] = [];
		}

		foreach ( $this->get_tests() as $test ) {
			unset( $test['extra'] );
			$tests['async'][ $test['test'] ] = $test;
		}

		return $tests;
	}

	/**
	 * Checks if the Seating license is valid.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function check_slr_valid_license() {
		check_ajax_referer( 'health-check-site-status' );
		$test = $this->get_tests()['slr_valid_license'];

		$seating = get_resource( 'tec-seating' );

		if ( $seating->has_valid_license() ) {
			wp_send_json_success( $this->get_test_result( $test ) );
			// Return helps with testing, since we 'll mock wp_send_json functions.
			return;
		}

		wp_send_json_error( $this->get_failed_test_result( $test ) );
	}

	/**
	 * Checks if the Seating license can communicate with the Seating App.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function check_slr_can_see_sass() {
		check_ajax_referer( 'health-check-site-status' );
		$test = $this->get_tests()['slr_can_see_sass'];

		$service = $this->container->get( Service::class );

		if ( $service->check_connection() ) {
			wp_send_json_success( $this->get_test_result( $test ) );
			// Return helps with testing, since we 'll mock wp_send_json functions.
			return;
		}

		wp_send_json_error( $this->get_failed_test_result( $test ) );
	}

	/**
	 * Checks if the site can serve AJAX requests in the rate required by the Seating functionality.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function check_slr_ajax_rate() {
		check_ajax_referer( 'health-check-site-status' );
		$test = $this->get_tests()['slr_ajax_rate'];

		$action = 'tec-site-health-test-' . $test['test'];
		$nonce  = wp_create_nonce( $action );
		for ( $i = 0; $i < self::AJAX_AMOUNT_OF_TESTS; $i++ ) {
			$start = microtime( true );

			$response = wp_safe_remote_get(
				add_query_arg(
					[
						'action' => rawurlencode( $action ),
						'nonce'  => $nonce,
					],
					admin_url( '/admin-ajax.php' )
				),
				[
					'timeout' => 1,
					'headers' => [
						'Cookie' => $_SERVER['HTTP_COOKIE'] ?? '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					],
				]
			);

			if ( microtime( true ) - $start < self::AJAX_RATE ) {
				// Sleep for the remaining time.
				usleep( self::AJAX_RATE - ( microtime( true ) - $start ) );
			}

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				wp_send_json_error( $this->get_failed_test_result( $test ) );
				// Return helps with testing, since we 'll mock wp_send_json functions.
				return;
			}
		}

		wp_send_json_success( $this->get_test_result( $test ) );
	}

	/**
	 * Serves the AJAX request for the AJAX rate test.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function test_ajax_rate() {
		if ( ! wp_verify_nonce( wp_unslash( $_GET )['nonce'] ?? '', 'tec-site-health-test-' . $this->get_tests()['slr_ajax_rate']['test'] ) ) {
			wp_send_json( [], 400, 0 );
			// Return helps with testing, since we 'll mock wp_send_json functions.
			return;
		}

		wp_send_json( [], 200, 0 );
	}

	/**
	 * Returns the test result for a successful test.
	 *
	 * @since TBD
	 *
	 * @param array $test The test to be processed.
	 *
	 * @return array
	 */
	protected function get_test_result( array $test ): array {
		return [
			'label'       => esc_html( $test['label'] ),
			'completed'   => true,
			'status'      => 'good',
			'badge'       => [
				'label' => esc_html__( 'Seating', 'event-tickets' ),
				'color' => 'blue',
			],
			'description' => esc_html( $test['extra']['success']['description'] ),
			'actions'     => sprintf( $test['extra']['success']['actions']['0'], ...$test['extra']['success']['actions']['1'] ),
			'test'        => $test['test'],
		];
	}

	/**
	 * Returns the test result for a failed test.
	 *
	 * @since TBD
	 *
	 * @param array $test The test to be processed.
	 *
	 * @return array
	 */
	protected function get_failed_test_result( array $test ): array {
		$result = $this->get_test_result( $test );

		$result['status']         = 'critical';
		$result['badge']['color'] = 'red';
		$result['description']    = $test['extra']['failure']['description'];
		$result['actions']        = sprintf( esc_html( $test['extra']['failure']['actions']['0'] ), ...$test['extra']['failure']['actions']['1'] );
		$result['completed']      = false;

		return $result;
	}
}
