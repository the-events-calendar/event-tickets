<?php
/**
 * The main Event Assigned Controller plugin controllers, it bootstraps the ancillary controllers and binds the main
 * definitions.
 *
 * @since   1.0.0
 *
 * @package TEC/Controller
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Common\StellarWP\Assets\Config;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Class Controller
 *
 * @since 5.16.0
 *
 * @package TEC/Controller
 */
class Controller extends Controller_Contract {
	/**
	 * The name of the constant that will be used to disable the feature.
	 * Setting it to a truthy value will disable the feature.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_SEATING_DISABLED';
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_seating_registered';

	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Admin::class )->unregister();
		$this->container->get( Frontend::class )->unregister();
		$this->container->get( Editor::class )->unregister();
		$this->container->get( Frontend\Timer::class )->unregister();
		$this->container->get( Admin\Ajax::class )->unregister();
	}

	/**
	 * Determines if the feature is enabled or not.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the feature is enabled or not.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			// The constant to disable the feature is defined and it's truthy.
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			// The environment variable to disable the feature is truthy.
			return false;
		}

		// Finally read an option value to determine if the feature should be active or not.
		$active = (bool) get_option( 'tec_tickets_seating_active', true );

		/**
		 * Allows filtering whether the whole Seating feature
		 * should be activated or not.
		 *
		 * Note: this filter will only apply if the disable constant or env var
		 * are not set or are set to falsy values.
		 *
		 * @since 5.16.0
		 *
		 * @param bool $activate Defaults to `true`.
		 */
		return (bool) apply_filters( 'tec_tickets_seating_active', $active );
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		require_once __DIR__ . '/template-tags.php';

		// Add the group path for the seating assets.
		Config::add_group_path( 'tec-seating', Tickets_Plugin::instance()->plugin_path . 'build/', 'Seating/' );

		$this->container->singleton( Template::class );
		$this->container->singleton( Localization::class );
		$this->container->singleton( Session::class );
		$this->container->singleton( Service\Service::class, fn() => $this->build_service_facade() );
		$this->container->singleton(
			Service\Reservations::class,
			function () {
				$this->build_service_facade();

				return $this->container->make( Service\Reservations::class );
			}
		);
		$this->container->singleton( Meta::class );

		$this->container->register( Tables::class );
		$this->container->register( Assets::class );

		$this->container->register( Uplink::class );

		// Manage Order and Attendee data.
		$this->container->register( Orders\Controller::class );

		$this->container->register( Delete_Operations::class );

		/*
		 * The Timer will have to handle the AJAX and initial requests to handle and render the timer.
		 * For this reason, it's always registered.
		 */
		$this->container->register( Frontend\Timer::class );

		/*
		 * The Editor will have to handle initial state requests, AJAX requests and REST requests from the Block Editor.
		 * For this reason, it's always registered.
		 */
		$this->container->register( Editor::class );

		/*
		 * AJAX will power both frontend and backend, always register it.
		 */
		$this->container->register( Admin\Ajax::class );

		$this->container->register( Settings::class );

		if ( is_admin() ) {
			$this->container->register( Admin::class );
			$this->container->register( Admin\Events\Controller::class );
		} else {
			$this->container->register( Frontend::class );
		}

		if ( tec_tickets_commerce_is_enabled() ) {
			$this->container->register( Commerce\Controller::class );
		}

		$this->container->register( QR::class );
		$this->container->register( REST::class );

		$this->container->register( Health::class );
	}

	/**
	 * Builds and returns the Service facade class ready to use.
	 *
	 * @since 5.16.0
	 *
	 * @return Service\Service An instance of the Service facade class.
	 */
	private function build_service_facade(): Service\Service {
		$backend_base_url = defined( 'TEC_TICKETS_SEATING_SERVICE_BASE_URL' )
			? TEC_TICKETS_SEATING_SERVICE_BASE_URL
			: 'https://seating.theeventscalendar.com';

		/**
		 * Filters the base URL of the service for backend requests.
		 *
		 * @since 5.16.0
		 *
		 * @param string $backend_base_url The base URL of the service.
		 */
		$backend_base_url = apply_filters( 'tec_tickets_seating_service_base_url', $backend_base_url );

		$backend_base_url = rtrim( $backend_base_url, '/' );

		$frontend_base_url = defined( 'TEC_TICKETS_SEATING_SERVICE_BASE_URL' )
			? TEC_TICKETS_SEATING_SERVICE_BASE_URL
			: 'https://seating.theeventscalendar.com';

		/**
		 * Filters the base URL of the service for frontend requests.
		 *
		 * @since 5.16.0
		 *
		 * @param string $frontend_base_url The base URL of the service.
		 */
		$frontend_base_url = apply_filters( 'tec_tickets_seating_service_frontend_url', $frontend_base_url );

		$frontend_base_url = rtrim( $frontend_base_url, '/' );

		foreach (
			[
				Service\Service::class,
				Service\Ephemeral_Token::class,
				Service\Layouts::class,
				Service\Seat_Types::class,
				Service\Maps::class,
				Service\Reservations::class,
			] as $class
		) {
			$this->container->singleton( $class );
			$this->container->when( $class )
							->needs( '$backend_base_url' )
							->give( $backend_base_url );
			$this->container->when( $class )
							->needs( '$frontend_base_url' )
							->give( $frontend_base_url );
		}

		return $this->container->get( Service\Service::class );
	}
}
