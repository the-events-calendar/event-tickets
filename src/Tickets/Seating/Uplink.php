<?php
/**
 * The Controller to set up the Uplink library.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Uplink\Register;
use Tribe__Tickets__Main as Main;

/**
 * Controller for setting up the stellarwp/uplink library.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating
 */
class Uplink extends Controller_Contract {
	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected string $et_slr_plugin_slug = 'tec-seating';

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected string $et_slr_plugin_name;

	/**
	 * Event Tickets plugin main class instance.
	 *
	 * @var Main|null
	 */
	protected ?Main $et_main = null;

	/**
	 * Uplink Controller constructor.
	 *
	 * since TBD
	 *
	 * @param Container $container A reference to the DI container object.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );
		$this->et_main            = Main::instance();
		$this->et_slr_plugin_name = _x( 'Seating', 'Header of the connection controls', 'event-tickets' );
	}

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		add_action( 'init', [ $this, 'register_plugin' ] );
		add_filter(
			'stellarwp/uplink/tec/tec-seating/view/authorize_button/link_text',
			[ $this, 'get_connect_button_text' ],
			10,
			2
		);
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'register_plugin' ] );
		remove_filter(
			'stellarwp/uplink/tec/tec-seating/view/authorize_button/link_text',
			[ $this, 'get_connect_button_text' ]
		);
	}

	/**
	 * Register the plugin in the uplink library.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_plugin(): void {
		Register::plugin(
			$this->et_slr_plugin_slug,
			$this->et_slr_plugin_name,
			Main::VERSION,
			"{$this->et_main->plugin_dir}/event-tickets.php",
			Main::class,
			null,
			true
		);
	}

	/**
	 * Filters the text for the Seat Builder connection button to return one customized for the
	 * SLR feature.
	 *
	 * @since TBD
	 *
	 * @param string $label         The label for the button.
	 * @param bool   $authenticated Whether the user is authenticated or not.
	 *
	 * @return string The customized text for the Seat Builder connection button.
	 */
	public function get_connect_button_text( string $label, bool $authenticated ): string {
		return $authenticated ?
			_x( 'Disconnect from Seat Builder', 'Button text for the Seat Builder connection button', 'event-tickets' )
			: _x( 'Connect to Seat Builder', 'Button text for the Seat Builder connection button', 'event-tickets' );
	}
}
