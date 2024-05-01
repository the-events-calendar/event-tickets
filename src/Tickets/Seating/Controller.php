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
use TEC\Common\lucatume\DI52\Container;

/**
 * Class Controller
 *
 * @since   1.0.0
 *
 * @package TEC/Controller
 */
class Controller extends Controller_Contract {
	/**
	 * The slug used to identify the plugin in theme overrides, assets and the like.
	 *
	 * @since TBD
	 */
	public const SLUG = 'events-assigned-seating';

	/**
	 * The version of this plugin.
	 *
	 * @since TBD
	 */
	public const VERSION = '1.0.0';

	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_events_assigned_seating_registered';

	/**
	 * The plugin path, used by the Template to determine where to look for templates.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public string $plugin_path;

	/**
	 * The theme namespace that will be used to determine where to look for templates.
	 * Themes will be able to override template files using `tribe/events-assigned-seating`
	 * directory.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public string $template_namespace = self::SLUG;

	/**
	 * Controller constructor.
	 *
	 * since TBD
	 *
	 * @param Container $container A reference to the container object.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );
		$this->plugin_path = EVENTS_ASSIGNED_SEATING_DIR;
	}

	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Admin::class )->unregister();
		$this->container->get( Frontend::class )->unregister();
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Template::class );
		$this->container->singleton( Localization::class );
		$this->container->singleton( Service\Service::class, fn() => $this->build_service_facade() );
		$this->container->singleton( Meta::class );

		$this->register_common_assets();

		$this->container->register( Tables::class );

		if ( is_admin() ) {
			$this->container->register( Admin::class );
			$this->container->register( Editor::class );
		} else {
			$this->container->register( Frontend::class );
		}
	}

	/**
	 * Registers some common assets that will be used in Admin, Frontend and Blocks context.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_common_assets(): void {
		Asset::add(
			'tec-events-assigned-seating-vendor',
			'vendor.js',
			Plugin_Register::VERSION
		)
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->register();
	}

	/**
	 * Builds and returns the Service facade class ready to use.
	 *
	 * @since TBD
	 *
	 * @return Service\Service An instance of the Service facade class.
	 */
	private function build_service_facade(): Service\Service {
		$backend_base_url = defined( 'TEC_EVENTS_ASSIGNED_SEATING_SERVICE_BASE_URL' )
			? TEC_EVENTS_ASSIGNED_SEATING_SERVICE_BASE_URL
			: 'https://evnt.is';

		/**
		 * Filters the base URL of the service for backend requests.
		 *
		 * @since TBD
		 *
		 * @param string $backend_base_url The base URL of the service.
		 */
		$backend_base_url = apply_filters( 'tec_events_assigned_seating_service_base_url', $backend_base_url );

		$backend_base_url = rtrim( $backend_base_url, '/' );

		$frontend_base_url = defined( 'TEC_EVENTS_ASSIGNED_SEATING_SERVICE_BASE_URL' )
			? TEC_EVENTS_ASSIGNED_SEATING_SERVICE_BASE_URL
			: 'https://evnt.is';

		/**
		 * Filters the base URL of the service for frontend requests.
		 *
		 * @since TBD
		 *
		 * @param string $frontend_base_url The base URL of the service.
		 */
		$frontend_base_url = apply_filters( 'tec_events_assigned_seating_service_frontend_url', $frontend_base_url );

		$frontend_base_url = rtrim( $frontend_base_url, '/' );

		foreach (
			[
				Service\Service::class,
				Service\Ephemeral_Token::class,
				Service\Layouts::class,
				Service\Seat_Types ::class
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
