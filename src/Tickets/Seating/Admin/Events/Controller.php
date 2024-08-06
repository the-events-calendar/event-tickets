<?php
/**
 * Associated Events controller class.
 */

namespace TEC\Tickets\Seating\Admin\Events;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Events Controller.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	
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
			'tec-tickets-seating-events',
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
		
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">Events Associated with Layout: {{NAME}}</h1>';
		echo '<form method="post">';
		$events_table->search_box( 'search', 'search_id' );
		$events_table->display();
		echo '</form>';
		echo '</div>';
	}
}
