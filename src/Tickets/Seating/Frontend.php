<?php
/**
 * The main front-end controller. This controller will directly, or by delegation, subscribe to
 * front-end related hooks.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use Tribe__Template as Base_Template;
use TEC\Common\StellarWP\Assets\Asset;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */
class Frontend extends Controller_Contract {
	use Built_Assets;
	
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_seating_frontend_registered';

	/**
	 * A reference to the template object.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Controller constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container A reference to the container object.
	 * @param Template  $template  A reference to the template object.
	 */
	public function __construct( Container $container, Template $template ) {
		parent::__construct( $container );
		$this->template = $template;
	}

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tribe_template_pre_html:tickets/v2/tickets', [ $this, 'print_tickets_block' ], 10, 5 );

		// @todo regsiter front-end Assets here.
		
		// Register the front-end CSS.
		Asset::add(
			'tec-tickets-seating-frontend-css',
			$this->built_asset_url( 'frontend/form.css' ),
			Tickets::VERSION
		)
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tribe_template_pre_html:tickets/v2/tickets', [ $this, 'print_tickets_block' ] );
	}

	/**
	 * Replace the Tickets' block with the one starting the seat selection flow.
	 *
	 * @since TBD
	 *
	 * @param string              $html     The initial HTML.
	 * @param string              $file     Complete path to include the PHP File.
	 * @param array               $name     Template name.
	 * @param Base_Template       $template Current instance of the Tribe__Template.
	 * @param array<string,mixed> $context  The context data passed to the template.
	 *
	 * @return string|null The template HTML, or `null` to let the default template process it.
	 */
	public function print_tickets_block( $html, $file, $name, $template, $context ): ?string {
		$data    = $template->get_values();
		$post_id = $data['post_id'];
		
		if ( ! tec_tickets_is_assigned_seating_enabled( $post_id ) ) {
			return $html;
		}
		
		// Prepare the data to be passed to the template.
		$cost_range = '$12.00 - $25.00';
		$inventory  = 100;
		
		return $this->template->template(
			'tickets-block',
			[
				'cost_range' => $cost_range,
				'inventory'  => $inventory,
			],
			false
		);
	}
}
