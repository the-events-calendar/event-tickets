<?php
/**
 * Associated Events controller class.
 */

namespace TEC\Tickets\Seating\Admin\Events;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Seating\Admin\Template;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Common\StellarWP\DB\DB;

/**
 * Class Events Controller.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	/**
	 * A reference to the template instance used to render the templates.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	private Template $template;
	
	/**
	 * Events Controller constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The container instance.
	 * @param Template  $template The template instance.
	 */
	public function __construct( Container $container, Template $template ) {
		parent::__construct( $container );
		$this->template = $template;
	}
	
	/**
	 * Register actions.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'admin_menu', [ $this, 'add_events_list_page' ], 20 );
	}
	
	/**
	 * Remove actions.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'admin_menu', [ $this, 'add_events_list_page' ], 20 );
	}
	
	/**
	 * Register Event listing page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function add_events_list_page() {
		add_submenu_page(
			'',
			__( 'Events', 'event-tickets' ),
			'',
			'manage_options',
			Associated_Events::get_slug(),
			[ $this, 'render' ]
		);
	}
	
	/**
	 * Render the Associated Events list table.
	 *
	 * @since TBD
	 *        
	 * @return void
	 */
	public function render() {
		$events_table = new Associated_Events();
		$events_table->prepare_items();
		
		$layout_id = tribe_get_request_var( 'layout', false );
		$layout    = DB::table( Layouts::table_name( false ) )->where( 'id', $layout_id )->get();
		
		if ( empty( $layout ) ) {
			echo esc_html__( 'Layout ID is not valid!', 'event-tickets' );
			return;
		}
		
		$header = sprintf(
			/* translators: %s: Layout name. */
			__( 'Associated Events for %s', 'event-tickets' ),
			$layout->name
		);
		
		$this->template->template(
			'events/list',
			[
				'header'       => $header,
				'events_table' => $events_table,
			]
		);
	}
}
