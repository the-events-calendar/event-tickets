<?php
/**
 * Controller for registering and serving Site Health tests for the Seating functionality.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Seating\Service\Service;
use Tribe\Tickets\Admin\Settings;
use function TEC\Common\StellarWP\Uplink\get_resource;

/**
 * Class Controller.
 *
 * @since   5.16.0
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
	 * @since 5.16.0
	 *
	 * @param Container $container A reference to the container object.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );
	}

	/**
	 * Defines the tests to be registered with the Site Health component.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function define_tests() {
		$set_license_url = tribe( Settings::class )->get_url( [ 'tab' => 'licenses' ] );

		$this->tests = [
			'slr_valid_license' => [
				'label'     => __( 'Seating license is valid', 'event-tickets' ),
				'test'      => 'slr-valid-license',
				'completed' => false,
				'extra'     => [
					'success' => [
						'description' => __( 'Your license is valid.', 'event-tickets' ),
						'actions'     => [
							// Translators: 1 is opening a element, 2 is closing of p element, 3 is opening of a element and 4 is closing of a element.
							_x( '%1$sYou can check your license status on your account at %3$stheeventscalendar.com%4$s.%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health is successful.', 'event-tickets' ),
							[
								'<p>',
								'</p>',
								// Visit my account.
								'<a href="https://evnt.is/1be1" target="_blank" rel="noopener noreferrer">',
								'</a>',
							],
						],
					],
					'failure' => [
						'label'       => __( 'Seating license is invalid', 'event-tickets' ),
						'description' => __( 'You must have a valid license to use Seating for Event Tickets.', 'event-tickets' ),
						'actions'     => [
							// Translators: 1 Opening p element, 2 closing p element, 3 is closing a element, 4, 5, 6 and 7 opening a elements.
							_x( '%1$sYou may need to %4$spurchase a license%3$s or %5$srenew an existing license%3$s.%2$s%2$sOnce you have a valid license, enter it under %6$sTickets > Settings > Licenses%3$s and connect to the Seating Builder service.%2$s%1$s%7$sLearn more about about setting up Seating%3$s%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health has failed.', 'event-tickets' ),
							[
								'<p>',
								'</p>',
								'</a>',
								// Purchase Seating license link.
								'<a href="https://evnt.is/1be0" target="_blank" rel="noopener noreferrer">',
								// Visit my account - Seating section link.
								'<a href="https://evnt.is/1be1" target="_blank" rel="noopener noreferrer">',
								// Authorize Seating link.
								'<a href="' . esc_url( $set_license_url ) . '">',
								// Learn more about setting up Seating link.
								'<a href="https://evnt.is/1be2" target="_blank" rel="noopener noreferrer">',
							],
						],
					],
				],
			],
			'slr_can_see_sass'  => [
				'label'     => __( 'Your site communicates with the Seating Builder SaaS', 'event-tickets' ),
				'test'      => 'slr-can-see-sass',
				'completed' => false,
				'extra'     => [
					'success' => [
						'description' => __( 'Your site is able to connect to the Seating Builder SaaS.', 'event-tickets' ),
						'actions'     => [
							// Translators: 1 is opening p element, 2 is closing p element, 3 is opening a element and 4 is closing a element.
							_x( '%1$sYou\'re ready to %3$suse Seating for Event Tickets%4$s.%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health is successful.', 'event-tickets' ),
							[
								'<p>',
								'</p>',
								// Learn more about Seating link.
								'<a href="https://evnt.is/1be3" target="_blank" rel="noopener noreferrer">',
								'</a>',
							],
						],
					],
					'failure' => [
						'label'       => __( 'Your site cannot communicate with the Seating Builder SaaS', 'event-tickets' ),
						'description' => __( 'The Seating Builder is a SaaS tool that provides the functionality for creating Seating Maps, Seat Layouts, and tickets with assigned seating. Your site must be connected to the Seating Builder in order to use these features.', 'event-tickets' ),
						'actions'     => [
							// Translators: 1 Opening p element, 2 closing p element, 3 is closing a element, 4 and 5 opening a elements.
							_x( '%1$s%4$sLearn more about connecting to the Seating Builder%3$s or %5$scontact support%3$s.%2$s', 'Shown as an action result, when the test regarding Seating license in Site Health has failed.', 'event-tickets' ),
							[
								'<p>',
								'</p>',
								'</a>',
								// Learn more about Seating link.
								'<a href="https://evnt.is/1be2" target="_blank" rel="noopener noreferrer">',
								// Support link.
								'<a href="https://evnt.is/1be4" target="_blank" rel="noopener noreferrer">',
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
	 * @since 5.16.0
	 *
	 * @return array
	 */
	public function get_tests(): array {
		return $this->tests;
	}

	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'define_tests' ] );
		remove_filter( 'site_status_tests', [ $this, 'add_site_status_tests' ] );

		foreach ( $this->get_tests() as $callback => $test ) {
			remove_action( 'wp_ajax_health-check-' . $test['test'], [ $this, 'check_' . $callback ] );
		}
	}

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'init', [ $this, 'define_tests' ] );
		add_filter( 'site_status_tests', [ $this, 'add_site_status_tests' ] );

		foreach ( $this->get_tests() as $callback => $test ) {
			add_action( 'wp_ajax_health-check-' . $test['test'], [ $this, 'check_' . $callback ] );
		}
	}

	/**
	 * Adds the Seating tests to the Site Health component.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * Returns the test result for a successful test.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
	 *
	 * @param array $test The test to be processed.
	 *
	 * @return array
	 */
	protected function get_failed_test_result( array $test ): array {
		$result = $this->get_test_result( $test );

		$result['status']         = 'critical';
		$result['badge']['color'] = 'red';
		$result['label']          = $test['extra']['failure']['label'];
		$result['description']    = $test['extra']['failure']['description'];
		$result['actions']        = sprintf( esc_html( $test['extra']['failure']['actions']['0'] ), ...$test['extra']['failure']['actions']['1'] );
		$result['completed']      = false;

		return $result;
	}
}
