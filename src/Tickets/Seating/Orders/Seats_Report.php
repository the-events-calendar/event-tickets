<?php
/**
 * Seats Report class.
 */

namespace TEC\Tickets\Seating\Orders;

use TEC\Tickets\Commerce\Reports\Report_Abstract;
use TEC\Tickets\Commerce\Reports\Tabbed_View;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Service\Service;
use WP_Error;
use WP_Post;

/**
 * Class Seats_Tab.
 * 
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Seats_Report extends Report_Abstract {
	/**
	 * Slug of the admin page for orders
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $page_slug = 'tec-tickets-seats';
	
	/**
	 * @var string
	 */
	public static $tab_slug = 'tec-tickets-seats-report';
	
	/**
	 * The action to register the assets for the report.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $asset_action = 'tec-tickets-seats-report-assets';
	
	/**
	 * Order Pages ID on the menu.
	 *
	 * @since TBD
	 *
	 * @var string The menu slug of the orders page
	 */
	public $seats_page;
	
	/**
	 * Hooks the actions and filter required by the class.
	 *
	 * @since TBD
	 */
	public function hook() {
		// Register before the default priority of 10 to avoid submenu hook issues.
		add_action( 'admin_menu', [ $this, 'register_seats_page' ], 5 );
		
		// Register the tabbed view.
		$tc_tabbed_view = new Tabbed_View();
		$tc_tabbed_view->set_active( self::$tab_slug );
		$tc_tabbed_view->register();
	}
	
	/**
	 * Registers the Seats page among those the tabbed view should render.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_seats_page() {
		$page_title       = __( 'Seats', 'event-tickets' );
		$this->seats_page = add_submenu_page(
			'',
			$page_title,
			$page_title,
			'edit_posts',
			static::$page_slug,
			[ $this, 'render_page' ]
		);
		
		add_action( 'load-' . $this->seats_page, [ $this, 'screen_setup' ] );
	}
	
	/**
	 * Screen setup.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function screen_setup(): void {
		do_action( self::$asset_action );
	}
	
	/**
	 * Renders the order page
	 *
	 * @since TBD
	 */
	public function render_page() {
		$tc_tabbed_view = new Tabbed_View();
		$tc_tabbed_view->set_active( self::$tab_slug );
		$tc_tabbed_view->render();
		
		$this->get_template()->template( 'seats', $this->get_template_vars() );
	}
	
	/**
	 * Sets up the template variables used to render the Seats Report Page.
	 *
	 * @since TBD
	 *
	 * @return array<string, mixed> The template variables.
	 */
	public function setup_template_vars(): array {
		$post_id = tribe_get_request_var( 'post_id' );
		$post_id = tribe_get_request_var( 'event_id', $post_id );
		$post    = get_post( $post_id );
		
		$ephemeral_token     = tribe( Service::class )->get_ephemeral_token();
		$token               = is_string( $ephemeral_token ) ? $ephemeral_token : '';
		$this->template_vars = [
			'post'       => $post,
			'post_id'    => $post_id,
			'iframe_url' => tribe( Service::class )->get_seat_report_url( $post_id ),
			'token'      => $token,
			'error'      => $ephemeral_token instanceof WP_Error ? $ephemeral_token->get_error_message() : '',
		];
		
		return $this->template_vars;
	}
	
	/**
	 * Get the report link.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The Post object.
	 */
	public static function get_link( WP_Post $post ): string {
		return add_query_arg(
			[
				'post_type' => $post->post_type,
				'page'      => static::$page_slug,
				'post_id'   => $post->ID,
			],
			admin_url( 'edit.php' )
		);
	}
	
	/**
	 * Get the localized data for the report.
	 *
	 * @since TBD
	 *
	 * @param int|null $post_id The post ID.
	 *
	 * @return array<string, string> The localized data.
	 */
	public static function get_localized_data( ?int $post_id = null ): array {
		$post_id = $post_id ?: tribe_get_request_var( 'post_id' );
		
		if ( ! $post_id ) {
			return [];
		}
		
		return [
			'seatTypeMap' => tribe( Frontend::class )->build_seat_type_map( $post_id ),
		];
	}
}
